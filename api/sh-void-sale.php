<?php
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

$sale_id = isset($input['sale_id']) ? trim($input['sale_id']) : '';
$inventory_action = isset($input['inventory_action']) ? strtolower(trim($input['inventory_action'])) : 'lost';

if (empty($sale_id)) {
    echo json_encode(['success' => false, 'message' => 'Sale ID is required']);
    exit;
}

if (!in_array($inventory_action, ['lost', 'restore'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid inventory action']);
    exit;
}
$staff_id = $_SESSION['user_id'];

$conn->begin_transaction();

try {
    // Verify sale exists and is not already voided (lock row to avoid race)
    $check_stmt = $conn->prepare("SELECT sale_id, status FROM sales WHERE sale_id = ? FOR UPDATE");
    if (!$check_stmt) {
        throw new Exception('Query error: ' . $conn->error);
    }
    $check_stmt->bind_param("s", $sale_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        $check_stmt->close();
        throw new Exception('Sale not found');
    }

    $sale = $check_result->fetch_assoc();
    $check_stmt->close();

    if ($sale['status'] === 'voided') {
        throw new Exception('This sale has already been voided');
    }

    $restored_batches = 0;

    // Optional: restore deducted inventory back to the original batches
    if ($inventory_action === 'restore') {
        $mv_stmt = $conn->prepare("
            SELECT batch_id, inventory_id, SUM(quantity) AS total_qty
            FROM inventory_movements
            WHERE reference_type = 'sale' AND reference_id = ? AND movement_type = 'sale'
            GROUP BY batch_id, inventory_id
            HAVING SUM(quantity) < 0
        ");
        if (!$mv_stmt) {
            throw new Exception('Failed to read sale movement records: ' . $conn->error);
        }
        $mv_stmt->bind_param("s", $sale_id);
        $mv_stmt->execute();
        $mv_result = $mv_stmt->get_result();

        while ($mv = $mv_result->fetch_assoc()) {
            $batch_id = $mv['batch_id'];
            $inventory_id = $mv['inventory_id'];
            $restore_qty = abs(floatval($mv['total_qty']));

            if (empty($batch_id) || $restore_qty <= 0) {
                continue;
            }

            $batch_stmt = $conn->prepare("
                SELECT current_quantity, initial_quantity, total_cost
                FROM inventory_batches
                WHERE batch_id = ? AND inventory_id = ?
                FOR UPDATE
            ");
            if (!$batch_stmt) {
                throw new Exception('Failed to lock inventory batch: ' . $conn->error);
            }
            $batch_stmt->bind_param("ss", $batch_id, $inventory_id);
            $batch_stmt->execute();
            $batch_result = $batch_stmt->get_result();

            if ($batch_result->num_rows === 0) {
                $batch_stmt->close();
                throw new Exception('Cannot restore inventory: batch ' . $batch_id . ' not found');
            }

            $batch = $batch_result->fetch_assoc();
            $batch_stmt->close();

            $old_qty = floatval($batch['current_quantity']);
            $new_qty = $old_qty + $restore_qty;

            $upd_stmt = $conn->prepare("UPDATE inventory_batches SET current_quantity = ? WHERE batch_id = ?");
            if (!$upd_stmt) {
                throw new Exception('Failed to prepare batch restore update: ' . $conn->error);
            }
            $upd_stmt->bind_param("ds", $new_qty, $batch_id);
            if (!$upd_stmt->execute()) {
                $upd_stmt->close();
                throw new Exception('Failed to restore inventory batch ' . $batch_id . ': ' . $upd_stmt->error);
            }
            $upd_stmt->close();

            // Log restore movement
            $movement_id = 'MV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $unit_cost = floatval($batch['initial_quantity']) > 0
                ? (floatval($batch['total_cost']) / floatval($batch['initial_quantity']))
                : 0;
            $total_value = $restore_qty * $unit_cost;
            $reference_type = 'void_sale';
            $reason = 'Void sale ' . $sale_id
                . ': returned ' . number_format($restore_qty, 2)
                . ' unit(s) to batch ' . $batch_id
                . ' for inventory item ' . $inventory_id
                . ' (selected: Return to inventory).';

            $log_stmt = $conn->prepare("
                INSERT INTO inventory_movements (
                    movement_id, inventory_id, batch_id, movement_type, quantity,
                    old_quantity, new_quantity, unit_cost, total_value,
                    reference_type, reference_id, staff_id, movement_date, reason
                ) VALUES (?, ?, ?, 'adjustment', ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            if ($log_stmt) {
                $log_stmt->bind_param(
                    'sssdddddssss',
                    $movement_id,
                    $inventory_id,
                    $batch_id,
                    $restore_qty,
                    $old_qty,
                    $new_qty,
                    $unit_cost,
                    $total_value,
                    $reference_type,
                    $sale_id,
                    $staff_id,
                    $reason
                );
                $log_stmt->execute();
                $log_stmt->close();
            }

            $restored_batches++;
        }

        $mv_stmt->close();
    }

    // Void the sale
    $void_stmt = $conn->prepare("
        UPDATE sales
        SET status = 'voided', voided_at = NOW(), voided_by = ?
        WHERE sale_id = ?
    ");
    if (!$void_stmt) {
        throw new Exception('Void query error: ' . $conn->error);
    }
    $void_stmt->bind_param("ss", $staff_id, $sale_id);
    if (!$void_stmt->execute()) {
        $void_stmt->close();
        throw new Exception('Failed to void sale: ' . $void_stmt->error);
    }
    $void_stmt->close();

    $conn->commit();

    if ($inventory_action === 'restore') {
        $message = 'Sale ' . $sale_id . ' has been voided. Inventory was restored to ' . $restored_batches . ' batch(es).';
    } else {
        $message = 'Sale ' . $sale_id . ' has been voided. Inventory was marked as lost (not restored).';
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'inventory_action' => $inventory_action
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
