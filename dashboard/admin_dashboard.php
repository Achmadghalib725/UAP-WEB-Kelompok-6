<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>
    <h2>Halo, Admin <?php echo $_SESSION['username']; ?>!</h2>
    <p><a href="../auth/logout.php">Logout</a></p>
</body>
</html>
