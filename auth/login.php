<?php
session_start();
include '../config/db.php';

// Cegah akses ke halaman login jika sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: ../dashboard/admin_dashboard.php");
    } else {
        header("Location: ../dashboard/user_dashboard.php");
    }
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;

            if ($role == 'admin') {
                header("Location: ../dashboard/admin_dashboard.php");
            } else {
                header("Location: ../dashboard/user_dashboard.php");
            }
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Akun tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - DoTask</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
    :root {
      --main-green: #5cb85c;
      --main-green-dark: #4cae4c;
      --text-dark: #333;
      --text-light: #666;
      --shadow-color: rgba(0, 0, 0, 0.07);
    }
    body, html {
      margin: 0;
      padding: 0;
      height: 100%;
      font-family: 'Poppins', sans-serif;
      overflow: hidden;
      background: linear-gradient(270deg, #70e1f5, #ffd194, #70e1f5, #ffd194);
      background-size: 800% 800%;
      animation: gradientShift 20s ease infinite;
      display: flex;
      justify-content: center;
      align-items: center;
      color: var(--text-dark);
    }
    @keyframes gradientShift {
      0% {
        background-position: 0% 50%;
      }
      50% {
        background-position: 100% 50%;
      }
      100% {
        background-position: 0% 50%;
      }
    }

    .container {
      position: relative;
      background: rgba(255 255 255 / 0.95);
      padding: 2.5rem 3rem;
      border-radius: 12px;
      width: 360px;
      max-width: 90vw;
      text-align: center;
      z-index: 1;
      /* subtle elegant shadow for gentle shading */
      box-shadow:
        0 1px 3px var(--shadow-color),
        0 4px 6px rgba(0,0,0,0.08),
        inset 0 0 12px rgba(255,255,255,0.6);
    }
    h1 {
      font-weight: 700;
      font-size: 2.8rem;
      margin-bottom: 0.5rem;
      color: var(--main-green);
    }
    h2 {
      font-weight: 600;
      font-size: 1.8rem;
      margin-bottom: 1rem;
    }
    p.intro {
      font-weight: 400;
      font-size: 1rem;
      color: var(--text-light);
      margin-bottom: 1.8rem;
      line-height: 1.4;
    }
    input[type="text"],
    input[type="password"] {
      width: 92%;
      padding: 12px 14px;
      margin: 0.5rem 0 1.2rem 0;
      border: 1.6px solid #ccc;
      border-radius: 6px;
      font-size: 1rem;
      transition: border-color 0.3s ease;
      outline-offset: 2px;
    }
    input[type="text"]:focus,
    input[type="password"]:focus {
      border-color: var(--main-green);
      outline: none;
      box-shadow: 0 0 6px var(--main-green);
    }
    button {
      width: 100%;
      padding: 12px;
      background-color: var(--main-green);
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 1.1rem;
      font-weight: 600;
      transition: background-color 0.3s ease;
    }
    button:hover {
      background-color: var(--main-green-dark);
    }
    .error {
      color: #e74c3c;
      margin-top: 1rem;
      font-weight: 600;
      font-size: 0.95rem;
      min-height: 1.2rem;
    }
    .register-link {
      margin-top: 1.5rem;
      display: block;
      color: #007bff;
      text-decoration: none;
      font-size: 0.95rem;
      font-weight: 500;
    }
    .register-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <main class="container" role="main">
    <h1>DoTask</h1>
    <h2>Login</h2>
    <p class="intro">Kelola tugas pribadi Anda dengan mudah dan efisien. Masuk untuk melanjutkan.</p>
    <form method="POST" novalidate>
      <input type="text" name="username" placeholder="Username" required autocomplete="username" aria-label="Username" />
      <input type="password" name="password" placeholder="Password" required autocomplete="current-password" aria-label="Password" />
      <button type="submit">Login</button>
      <p class="error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
      <p>Belum punya akun? <a class="register-link" href="register.php">Daftar di sini</a></p>
    </form>
  </main>
</body>
</html>
