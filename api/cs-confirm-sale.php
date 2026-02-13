<?php
// Suppress PHP warnings/notices from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$cart_items = $input['items'] ?? [];
$amount_paid = floatval($input['amount_paid'] ?? 0);

if (empty($cart_items)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

if ($amount_paid <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please enter the amount paid']);
    exit;
}

// Validate cart items
foreach ($cart_items as $item) {
    if (empty($item['product_id']) || intval($item['quantity']) <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
        exit;
    }
}

$staff_id = $_SESSION['user_id'];

$conn->begin_transaction();

try {
    // Generate sale ID (SL-XXXX)
    $sale_id = null;
    $result = $conn->query("SELECT sale_id FROM sales ORDER BY sale_id DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (preg_match('/SL-(\d+)/', $row['sale_id'], $matches)) {
            $sale_id = 'SL-' . (intval($matches[1]) + 1);
        }
    }
    if (!$sale_id) {
        $sale_id = 'SL-1001';
    }

    // Verify unique
    $check = $conn->prepare("SELECT sale_id FROM sales WHERE sale_id = ?");
    $check->bind_param("s", $sale_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        throw new Exception('Failed to generate unique sale ID. Please try again.');
    }

    // Generate sale detail IDs starting point
    $sd_result = $conn->query("SELECT sale_detail_id FROM sale_details ORDER BY sale_detail_id DESC LIMIT 1");
    $next_sd_num = 1001;
    if ($sd_result && $sd_result->num_rows > 0) {
        $sd_row = $sd_result->fetch_assoc();
        if (preg_match('/SD-(\d+)/', $sd_row['sale_detail_id'], $matches)) {
            $next_sd_num = intval($matches[1]) + 1;
        }
    }

    $total_price = 0;
    $total_cost = 0;
    $sale_details = [];

    // Insert sales record FIRST (with placeholder totals) so sale_details FK is satisfied
    $sale_date = date('Y-m-d');
    $sale_time = date('H:i:s');
    $zero = 0.00;

    $sale_stmt = $conn->prepare("
        INSERT INTO sales (sale_id, staff_id, sale_date, sale_time, total_price, total_cost, profit, amount_paid, change_amount)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $sale_stmt->bind_param("ssssddddd", $sale_id, $staff_id, $sale_date, $sale_time, $zero, $zero, $zero, $amount_paid, $zero);
    if (!$sale_stmt->execute()) {
        throw new Exception("Failed to create sale record");
    }

    foreach ($cart_items as $item) {
        $product_id = $item['product_id'];
        $quantity = intval($item['quantity']);
        $has_custom_price = isset($item['custom_price']);
        $has_custom_ingredients = isset($item['custom_ingredients']) && is_array($item['custom_ingredients']);
        $is_manual = ($has_custom_price || $has_custom_ingredients) ? 1 : 0;

        // Get product (must exist and be available)
        $prod_stmt = $conn->prepare("SELECT product_id, product_name, price FROM products WHERE product_id = ? AND status = 'Available'");
        $prod_stmt->bind_param("s", $product_id);
        $prod_stmt->execute();
        $prod_result = $prod_stmt->get_result();

        if ($prod_result->num_rows === 0) {
            throw new Exception("Product $product_id is no longer available");
        }

        $product = $prod_result->fetch_assoc();

        // Determine effective price
        if ($has_custom_price) {
            $price_per_unit = floatval($item['custom_price']);
            if ($price_per_unit < 0) {
                throw new Exception("Invalid custom price for " . $product['product_name']);
            }
        } else {
            $price_per_unit = floatval($product['price']);
        }
        $subtotal = $price_per_unit * $quantity;

        if ($has_custom_ingredients) {
            // ====== MANUAL INGREDIENTS PATH ======
            $ingredients = [];
            foreach ($item['custom_ingredients'] as $ci) {
                $inv_id = $ci['inventory_id'] ?? '';
                $qty_used = floatval($ci['quantity_used'] ?? 0);
                if (empty($inv_id) || $qty_used <= 0) continue;

                // Validate inventory item exists
                $inv_check = $conn->prepare("SELECT item_id, item_name FROM inventory_items WHERE item_id = ?");
                $inv_check->bind_param("s", $inv_id);
                $inv_check->execute();
                $inv_result = $inv_check->get_result();
                if ($inv_result->num_rows === 0) {
                    throw new Exception("Invalid inventory item: $inv_id");
                }
                $inv_item = $inv_result->fetch_assoc();

                $ingredients[] = [
                    'inventory_id' => $inv_id,
                    'item_name' => $inv_item['item_name'],
                    'quantity_used' => $qty_used
                ];
            }

            // Calculate cost from custom ingredients
            $cost_per_unit = 0;
            foreach ($ingredients as $ing) {
                $cost_q = $conn->prepare("
                    SELECT CASE WHEN SUM(current_quantity) > 0
                        THEN SUM((current_quantity / initial_quantity) * total_cost) / SUM(current_quantity)
                        ELSE 0 END as avg_cost
                    FROM inventory_batches
                    WHERE inventory_id = ? AND (expiration_date IS NULL OR expiration_date > CURDATE())
                ");
                $cost_q->bind_param("s", $ing['inventory_id']);
                $cost_q->execute();
                $avg_cost = floatval($cost_q->get_result()->fetch_assoc()['avg_cost']);
                $cost_per_unit += $ing['quantity_used'] * $avg_cost;
            }

            // Deduct inventory for custom ingredients
            foreach ($ingredients as $ing) {
                $total_deduct = $ing['quantity_used'] * $quantity;

                $stock_stmt = $conn->prepare("
                    SELECT SUM(current_quantity) as available
                    FROM inventory_batches
                    WHERE inventory_id = ? AND (expiration_date IS NULL OR expiration_date > CURDATE()) AND current_quantity > 0
                ");
                $stock_stmt->bind_param("s", $ing['inventory_id']);
                $stock_stmt->execute();
                $available = floatval($stock_stmt->get_result()->fetch_assoc()['available']);

                if ($available < $total_deduct) {
                    throw new Exception("Insufficient stock for ingredient: " . $ing['item_name'] .
                        " (need " . number_format($total_deduct, 2) . ", have " . number_format($available, 2) . ")");
                }

                $batch_stmt = $conn->prepare("
                    SELECT batch_id, current_quantity, initial_quantity, total_cost
                    FROM inventory_batches
                    WHERE inventory_id = ? AND (expiration_date IS NULL OR expiration_date > CURDATE()) AND current_quantity > 0
                    ORDER BY batch_order ASC, expiration_date ASC, created_at ASC
                ");
                $batch_stmt->bind_param("s", $ing['inventory_id']);
                $batch_stmt->execute();
                $batch_result = $batch_stmt->get_result();

                $remaining_to_deduct = $total_deduct;
                while ($batch = $batch_result->fetch_assoc()) {
                    if ($remaining_to_deduct <= 0) break;
                    $batch_qty = floatval($batch['current_quantity']);
                    $deduct_from_this = min($batch_qty, $remaining_to_deduct);
                    $new_qty = $batch_qty - $deduct_from_this;
                    $update_stmt = $conn->prepare("UPDATE inventory_batches SET current_quantity = ? WHERE batch_id = ?");
                    $update_stmt->bind_param("ds", $new_qty, $batch['batch_id']);
                    if (!$update_stmt->execute()) {
                        throw new Exception("Failed to deduct inventory batch");
                    }

                    // Log sale movement
                    try {
                        $mv_id = 'MV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
                        $mv_qty = -$deduct_from_this;
                        $mv_unit_cost = floatval($batch['initial_quantity']) > 0 ? (floatval($batch['total_cost']) / floatval($batch['initial_quantity'])) : 0;
                        $mv_value = -($deduct_from_this * $mv_unit_cost);
                        $mv_ref_type = 'sale';

                        $mv_stmt = $conn->prepare("
                            INSERT INTO inventory_movements (
                                movement_id, inventory_id, batch_id, movement_type, quantity,
                                old_quantity, new_quantity, unit_cost, total_value,
                                reference_type, reference_id, staff_id, movement_date, reason
                            ) VALUES (?, ?, ?, 'sale', ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                        ");
                        $mv_reason = 'Sale deduction for ' . $product['product_name'];
                        $mv_stmt->bind_param(
                            'sssdddddssss',
                            $mv_id, $ing['inventory_id'], $batch['batch_id'],
                            $mv_qty, $batch_qty, $new_qty, $mv_unit_cost, $mv_value,
                            $mv_ref_type, $sale_id, $staff_id, $mv_reason
                        );
                        $mv_stmt->execute();
                        $mv_stmt->close();
                    } catch (Exception $e) {
                        // Movement logging is non-critical
                    }

                    $remaining_to_deduct -= $deduct_from_this;
                }

                if ($remaining_to_deduct > 0.001) {
                    throw new Exception("Could not fully deduct inventory for: " . $ing['item_name']);
                }
            }
        } else {
            // ====== DEFAULT PATH (unchanged) ======
            // Calculate cost per unit from inventory batches
            $cost_query = "
                SELECT
                    COALESCE(SUM(pr.quantity_used * COALESCE(batch_avg.avg_cost_per_unit, 0)), 0) as unit_cost
                FROM product_requirements pr
                LEFT JOIN (
                    SELECT
                        inventory_id,
                        CASE
                            WHEN SUM(current_quantity) > 0
                            THEN SUM((current_quantity / initial_quantity) * total_cost) / SUM(current_quantity)
                            ELSE 0
                        END as avg_cost_per_unit
                    FROM inventory_batches
                    WHERE expiration_date IS NULL OR expiration_date > CURDATE()
                    GROUP BY inventory_id
                ) batch_avg ON pr.inventory_id = batch_avg.inventory_id
                WHERE pr.product_id = ?
            ";
            $cost_stmt = $conn->prepare($cost_query);
            $cost_stmt->bind_param("s", $product_id);
            $cost_stmt->execute();
            $cost_result = $cost_stmt->get_result();
            $cost_per_unit = floatval($cost_result->fetch_assoc()['unit_cost']);

            // Deduct inventory for this product
            $req_stmt = $conn->prepare("
                SELECT pr.inventory_id, pr.quantity_used, ii.item_name
                FROM product_requirements pr
                JOIN inventory_items ii ON pr.inventory_id = ii.item_id
                WHERE pr.product_id = ?
            ");
            $req_stmt->bind_param("s", $product_id);
            $req_stmt->execute();
            $req_result = $req_stmt->get_result();

            while ($req = $req_result->fetch_assoc()) {
                $total_deduct = floatval($req['quantity_used']) * $quantity;

                $stock_stmt = $conn->prepare("
                    SELECT SUM(current_quantity) as available
                    FROM inventory_batches
                    WHERE inventory_id = ? AND (expiration_date IS NULL OR expiration_date > CURDATE()) AND current_quantity > 0
                ");
                $stock_stmt->bind_param("s", $req['inventory_id']);
                $stock_stmt->execute();
                $available = floatval($stock_stmt->get_result()->fetch_assoc()['available']);

                if ($available < $total_deduct) {
                    throw new Exception("Insufficient stock for ingredient: " . $req['item_name'] . " (need " . number_format($total_deduct, 2) . ", have " . number_format($available, 2) . ")");
                }

                $batch_stmt = $conn->prepare("
                    SELECT batch_id, current_quantity, initial_quantity, total_cost
                    FROM inventory_batches
                    WHERE inventory_id = ? AND (expiration_date IS NULL OR expiration_date > CURDATE()) AND current_quantity > 0
                    ORDER BY batch_order ASC, expiration_date ASC, created_at ASC
                ");
                $batch_stmt->bind_param("s", $req['inventory_id']);
                $batch_stmt->execute();
                $batch_result = $batch_stmt->get_result();

                $remaining_to_deduct = $total_deduct;
                while ($batch = $batch_result->fetch_assoc()) {
                    if ($remaining_to_deduct <= 0) break;

                    $batch_qty = floatval($batch['current_quantity']);
                    $deduct_from_this = min($batch_qty, $remaining_to_deduct);

                    $new_qty = $batch_qty - $deduct_from_this;
                    $update_stmt = $conn->prepare("UPDATE inventory_batches SET current_quantity = ? WHERE batch_id = ?");
                    $update_stmt->bind_param("ds", $new_qty, $batch['batch_id']);
                    if (!$update_stmt->execute()) {
                        throw new Exception("Failed to deduct inventory batch");
                    }

                    // Log sale movement
                    try {
                        $mv_id = 'MV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
                        $mv_qty = -$deduct_from_this;
                        $mv_unit_cost = floatval($batch['initial_quantity']) > 0 ? (floatval($batch['total_cost']) / floatval($batch['initial_quantity'])) : 0;
                        $mv_value = -($deduct_from_this * $mv_unit_cost);
                        $mv_ref_type = 'sale';

                        $mv_stmt = $conn->prepare("
                            INSERT INTO inventory_movements (
                                movement_id, inventory_id, batch_id, movement_type, quantity,
                                old_quantity, new_quantity, unit_cost, total_value,
                                reference_type, reference_id, staff_id, movement_date, reason
                            ) VALUES (?, ?, ?, 'sale', ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                        ");
                        $mv_reason = 'Sale deduction for ' . $product['product_name'];
                        $mv_stmt->bind_param(
                            'sssdddddssss',
                            $mv_id, $req['inventory_id'], $batch['batch_id'],
                            $mv_qty, $batch_qty, $new_qty, $mv_unit_cost, $mv_value,
                            $mv_ref_type, $sale_id, $staff_id, $mv_reason
                        );
                        $mv_stmt->execute();
                        $mv_stmt->close();
                    } catch (Exception $e) {
                        // Movement logging is non-critical
                    }

                    $remaining_to_deduct -= $deduct_from_this;
                }

                if ($remaining_to_deduct > 0.001) {
                    throw new Exception("Could not fully deduct inventory for: " . $req['item_name']);
                }
            }
        }

        // Create sale detail (with is_manual flag)
        $sd_id = 'SD-' . $next_sd_num;
        $next_sd_num++;

        $sd_stmt = $conn->prepare("
            INSERT INTO sale_details (sale_detail_id, sale_id, product_id, quantity, price_per_unit, cost_per_unit, subtotal, is_manual)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $sd_stmt->bind_param("sssidddi", $sd_id, $sale_id, $product_id, $quantity, $price_per_unit, $cost_per_unit, $subtotal, $is_manual);
        if (!$sd_stmt->execute()) {
            throw new Exception("Failed to insert sale detail");
        }

        $total_price += $subtotal;
        $total_cost += ($cost_per_unit * $quantity);
    }

    $profit = $total_price - $total_cost;
    $change_amount = $amount_paid - $total_price;

    if ($amount_paid < $total_price) {
        throw new Exception('Insufficient payment. Total is ₱' . number_format($total_price, 2) . ' but only ₱' . number_format($amount_paid, 2) . ' was paid.');
    }

    // Update sales record with final calculated totals
    $update_sale = $conn->prepare("
        UPDATE sales SET total_price = ?, total_cost = ?, profit = ?, change_amount = ?
        WHERE sale_id = ?
    ");
    $update_sale->bind_param("dddds", $total_price, $total_cost, $profit, $change_amount, $sale_id);
    if (!$update_sale->execute()) {
        throw new Exception("Failed to update sale totals");
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Sale confirmed successfully',
        'sale_id' => $sale_id,
        'total_price' => $total_price,
        'change_amount' => $change_amount
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
