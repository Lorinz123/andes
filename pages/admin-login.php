<?php
// Simple API for admin login and password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // read JSON body
    ini_set('display_errors', '0');
    error_reporting(0);
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;

    $conn = new mysqli("localhost", "root", "", "andes_db");
    if ($conn->connect_error) {
        echo json_encode(['error' => 'DB connect error']);
        exit;
    }

    if ($action === 'login') {
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        $stmt = $conn->prepare('SELECT id, username, password FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        if ($user && password_verify($password, $user['password'])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
        }
        $stmt->close();
        $conn->close();
        exit;
    }

    if ($action === 'reset_password') {
        $email = trim(strtolower($input['email'] ?? ''));
        $newPassword = $input['newPassword'] ?? '';

        if (empty($email) || empty($newPassword)) {
            echo json_encode(['success' => false, 'error' => 'Email and password required']);
            exit;
        }
      // Only allow reset for the configured admin email (case-insensitive)
      $allowed_admin_email = 'raine0311@gmail.com'; // CHANGE THIS to your admin email (lowercase)
      if ($email !== $allowed_admin_email) {
        echo json_encode(['success' => false, 'error' => 'Email not allowed']);
        $conn->close();
        exit;
      }

      // hash new password
      $hash = password_hash($newPassword, PASSWORD_DEFAULT);

      // Try update first (overwrite)
      // update matching email case-insensitively
      $stmt = $conn->prepare('UPDATE admin_users SET password = ? WHERE LOWER(email) = ?');
      if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'DB prepare error']);
        $conn->close();
        exit;
      }
      $stmt->bind_param('ss', $hash, $email);
      if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
          echo json_encode(['success' => true, 'message' => 'Password updated']);
          $stmt->close();
          $conn->close();
          exit;
        }
      } else {
        echo json_encode(['success' => false, 'error' => 'DB error']);
        $stmt->close();
        $conn->close();
        exit;
      }
      $stmt->close();

      // If no rows were updated, create the admin record (overwrite semantics)
      $username_for_insert = 'admin';
      $ins = $conn->prepare('INSERT INTO admin_users (username, email, password) VALUES (?, ?, ?)');
      if (!$ins) {
        echo json_encode(['success' => false, 'error' => 'DB prepare error (insert)']);
        $conn->close();
        exit;
      }
      $ins->bind_param('sss', $username_for_insert, $email, $hash);
      if ($ins->execute()) {
        echo json_encode(['success' => true, 'message' => 'Admin created and password set']);
      } else {
        // If insert failed because the username already exists, update that user's password instead
        if ($ins->errno === 1062) { // duplicate entry
          $ins->close();
          $upd2 = $conn->prepare('UPDATE admin_users SET password = ? WHERE username = ?');
          if ($upd2) {
            $upd2->bind_param('ss', $hash, $username_for_insert);
            if ($upd2->execute()) {
              echo json_encode(['success' => true, 'message' => 'Existing admin password updated']);
            } else {
              echo json_encode(['success' => false, 'error' => 'DB update error: ' . $upd2->error]);
            }
            $upd2->close();
          } else {
            echo json_encode(['success' => false, 'error' => 'DB prepare error (update existing): ' . $conn->error]);
          }
        } else {
          echo json_encode(['success' => false, 'error' => 'DB insert error: ' . $ins->error]);
        }
      }
      $ins->close();
        $conn->close();
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Flower Puff</title>
  <link rel="stylesheet" href="../assets/style/admin-login.css">
  <script>
    // helper to POST JSON to this file
    async function postJson(payload) {
      const res = await fetch('admin-login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      return res.json();
    }
  </script>
</head>
<body>

  <nav class="navbar">
    <div class="nav-left">
      <a href="../index.php">
        <img src="../assets/image/logo.png" alt="Flower Puff Logo">
        <p>FLOWER PUFF</p>
      </a>
    </div>
  </nav>

  <main class="login-container">
    <a href="../index.php" class="back-btn">Back</a>

    <div class="login-box">
      <div class="icon-container">
        <i class="user-icon">👤</i>
      </div>

      <form id="loginForm">
        <div class="input-group">
          <label for="username"><i class="fa fa-user"></i></label>
          <input type="text" id="username" placeholder="Admin" required>
        </div>

        <div class="input-group">
          <label for="password"><i class="fa fa-lock"></i></label>
          <input type="password" id="password" placeholder="Password" required>
        </div>

        <a href="../pages/forgot-password.html" class="forgot-password">Forgot Password?</a>

        <button type="submit" class="login-btn">LOGIN</button>
      </form>
    </div>
  </main>

  <footer>
    <p>Copyrights 2025. Flower Puff’s Management. All Rights Reserved.</p>
  </footer>

  <script src="../assets/script/admin.js"></script>
  <script>
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value.trim();

      try {
        const result = await postJson({ action: 'login', username, password });
        if (result.success) {
          alert('Login Successful!');
          window.location.href = 'admin.php';
        } else {
          alert('Invalid Username or Password!');
        }
      } catch (err) {
        console.error(err);
        alert('Login failed');
      }
    });
  </script>
</body>
</html>