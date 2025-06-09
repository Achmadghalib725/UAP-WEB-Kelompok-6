<?php
include '../config/db.php';

$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
    $stmt->bind_param("ss", $username, $hashed_password);

    if ($stmt->execute()) {
        $success = "Registrasi berhasil! Silakan login.";
    } else {
        $error = "Username sudah digunakan.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registrasi</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <h2>Daftar Akun</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button type="submit">Daftar</button>
        <p style="color:green;"><?php echo $success; ?></p>
        <p style="color:red;"><?php echo $error; ?></p>
        <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </form>
</body>
</html>
