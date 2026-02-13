<?php
session_start();

// If already logged in, redirect to main dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ../pages/main.php?page=create-sales');
    exit();
}

$error = '';

// Handle login form submission
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/db.php';

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        // Query user from database
        $stmt = $conn->prepare("SELECT staff_id, user_name, password, is_super_admin FROM users WHERE user_name = ? AND status = 'Active'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['staff_id'];
                $_SESSION['username'] = $user['user_name'];
                $_SESSION['is_super_admin'] = $user['is_super_admin'];

                // Redirect to main wrapper with create-sales page
                header('Location: ../pages/main.php?page=create-sales');
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UR Foodhub + Café</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        input::placeholder {
            color: #9ca3af;
        }

        input:focus {
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-white">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="flex justify-center mb-4">
                <img
                    src="../assets/images/darklogo.png"
                    alt="UR Foodhub + Café Logo"
                    class="h-[200px] w-auto object-contain"
                />
            </div>

            <!-- Title -->
            <h1 class="text-center mb-2 text-3xl font-semibold">
                UR Foodhub + Café
            </h1>

            <!-- Subtitle -->
            <p class="text-center text-gray-700 mb-8 text-[15px]">
                Sign in to manage the inventory, products and sales
            </p>

            <!-- Error Message -->
            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-sm text-red-600"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="" class="space-y-5" autocomplete="on" id="loginForm">
                <!-- Username Field -->
                <div>
                    <label for="username" class="block text-sm font-medium mb-2">
                        Username
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="e.g SystemAdmin"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-all"
                        autocomplete="username"
                        required
                    />
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-sm font-medium mb-2">
                        Password
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="••••••••••••"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-all pr-12"
                            autocomplete="current-password"
                            required
                        />
                        <button
                            type="button"
                            onclick="togglePassword()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-600 hover:text-black transition-colors"
                            aria-label="Toggle password visibility"
                        >
                            <!-- Eye icon (password hidden) -->
                            <svg
                                id="eyeIcon"
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <!-- Eye slash icon (password visible) - hidden by default -->
                            <svg
                                id="eyeSlashIcon"
                                class="hidden"
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                <line x1="1" y1="1" x2="23" y2="23" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Sign In Button -->
                <button
                    type="submit"
                    class="w-full bg-black text-white py-2.5 rounded-md font-semibold hover:bg-gray-800 transition-colors duration-200 mt-6"
                >
                    Sign In
                </button>
            </form>

            <!-- Forgot Password Link -->
            <div class="text-center mt-5">
                <a
                    href="#"
                    class="text-[13px] font-regular text-blue-600 hover:text-blue-800 hover:underline transition-colors"
                >
                    forgot password?
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeSlashIcon = document.getElementById('eyeSlashIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeSlashIcon.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeSlashIcon.classList.add('hidden');
            }
        }
    </script>
</body>
</html>