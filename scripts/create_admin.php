<?php
// create_admin.php
// Usage:
// 1) Edit the $username, $email, $password variables below.
// 2) Run from CLI: php create_admin.php
//    or place in your browser at: http://localhost/andes/scripts/create_admin.php
// 3) Remove this file after creating the admin for security.

$servername = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "andes_db"; // change if needed

$username = 'admin';
$email = 'admin@example.com';
$password = 'ChangeMe123!';

// If you want the script to overwrite the password for an existing user,
// either pass ?overwrite=1 in the browser URL or run the script with
// the CLI flag `--overwrite`:
//   php create_admin.php --overwrite


// --- DO NOT leave this script on a public server after use ---

$conn = new mysqli($servername, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Ensure admin_users table exists
$createTable = "CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (! $conn->query($createTable)) {
        die('Create table failed: ' . $conn->error);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

// Check for existing username or email
$check = $conn->prepare('SELECT id, username, email FROM admin_users WHERE username = ? OR email = ? LIMIT 1');
if (!$check) {
    die('Prepare failed: ' . $conn->error);
}
$check->bind_param('ss', $username, $email);
$check->execute();
$res = $check->get_result();
if ($existing = $res->fetch_assoc()) {
    // detect overwrite flag (browser ?overwrite=1 or CLI --overwrite)
    $overwrite = false;
    if (isset($_GET['overwrite']) && $_GET['overwrite']) $overwrite = true;
    if (php_sapi_name() === 'cli') {
        global $argv;
        if (!empty($argv) && in_array('--overwrite', $argv)) $overwrite = true;
    }

    if ($overwrite) {
        // update password for existing user
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $conn->prepare('UPDATE admin_users SET password = ? WHERE id = ?');
        if (!$upd) {
            die('Prepare failed (update): ' . $conn->error);
        }
        $upd->bind_param('si', $newHash, $existing['id']);
        if ($upd->execute()) {
            echo "Admin user password updated. ID: " . $existing['id'] . ", username: " . $existing['username'] . "\n";
        } else {
            echo "Error updating password: " . $upd->error . "\n";
        }
        $upd->close();
        $check->close();
        $conn->close();
        exit;
    }

    echo "Admin user already exists. ID: " . $existing['id'] . ", username: " . $existing['username'] . "\n";
    $check->close();
    $conn->close();
    exit;
}
$check->close();

$stmt = $conn->prepare('INSERT INTO admin_users (username, email, password) VALUES (?, ?, ?)');
if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}
$stmt->bind_param('sss', $username, $email, $hash);
if ($stmt->execute()) {
    echo "Admin user created. ID: " . $conn->insert_id . "\n";
} else {
    echo "Error creating user: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();

?>