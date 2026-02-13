<?php
/**
 * Password Hash Generator
 * Usage: php generate-hash.php YOUR_PASSWORD
 */

if ($argc < 2) {
    echo "\n=================================\n";
    echo "Password Hash Generator\n";
    echo "=================================\n";
    echo "Usage: php generate-hash.php YOUR_PASSWORD\n";
    echo "Example: php generate-hash.php password123\n\n";
    exit(1);
}

$password = $argv[1];
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "\n=================================\n";
echo "Password Hash Generator\n";
echo "=================================\n";
echo "Password: $password\n";
echo "Hash: $hash\n";
echo "=================================\n\n";
echo "Use this hash in your SQL INSERT statement:\n";
echo "'$hash'\n\n";
?>