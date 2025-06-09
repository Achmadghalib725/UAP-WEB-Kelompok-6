<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/db.php';

$user_id = $_SESSION['user_id']; // Ensure this is defined correctly
$success = $error = "";

// Fetch current user data
$stmt = $conn->prepare("SELECT username, profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username_db, $profile_pic);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = trim($_POST["username"]);

    // Validate username
    if (empty($new_username)) {
        $error = "Username tidak boleh kosong.";
    } else {
        // Check if username is taken by others
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $checkStmt->bind_param("si", $new_username, $user_id);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $error = "Username sudah digunakan.";
        } else {
            $checkStmt->close();
            // Handle profile picture upload, if file is selected
            $upload_ok = true;
            $new_profile_pic = $profile_pic; // Default keep old pic

            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['profile_pic'];

                // Validate image file
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $upload_ok = false;
                    $error = "Error saat upload file.";
                } elseif (!in_array(mime_content_type($file['tmp_name']), $allowed_types)) {
                    $upload_ok = false;
                    $error = "Format file tidak didukung. Gunakan JPG, PNG, atau GIF.";
                } elseif ($file['size'] > $max_size) {
                    $upload_ok = false;
                    $error = "Ukuran file terlalu besar (maks 5MB).";
                } else {
                    // Safe filename
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $new_filename = "profile_$user_id_" . uniqid() . "." . $ext;
                    $upload_dir = __DIR__ . '/../uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $destination = $upload_dir . $new_filename;

                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        // Delete old profile pic if exists and different
                        if ($profile_pic && file_exists($upload_dir . $profile_pic)) {
                            unlink($upload_dir . $profile_pic);
                        }
                        $new_profile_pic = $new_filename;
                    } else {
                        $upload_ok = false;
                        $error = "Gagal menyimpan file upload.";
                    }
                }
            }

            if ($upload_ok) {
                // Update user data in DB
                $updateStmt = $conn->prepare("UPDATE users SET username = ?, profile_pic = ? WHERE id = ?");
                $updateStmt->bind_param("ssi", $new_username, $new_profile_pic, $user_id);

                if ($updateStmt->execute()) {
                    $success = "Profil berhasil diperbarui.";
                    $_SESSION['username'] = $new_username;
                    $username_db = $new_username;
                    $profile_pic = $new_profile_pic;
                } else {
                    $error = "Gagal menyimpan perubahan data.";
                }
                $updateStmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Edit Profil - DoTask</title>
<style>
    body {
        margin: 0;
        background: #fff;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #374151;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: 100vh;
        padding: 2rem 1rem;
    }
    .container {
        max-width: 480px;
        width: 100%;
        background: #f9fafb;
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 6px 15px rgba(0,0,0,0.05);
    }
    h1 {
        font-size: 2.7rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: #2563eb;
    }
    label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    input[type="text"], input[type="file"] {
        width: 100%;
        padding: 0.6rem 0.8rem;
        border: 1.5px solid #d1d5db;
        border-radius: 8px;
        font-size: 1rem;
        margin-bottom: 1.25rem;
        transition: border-color 0.3s;
    }
    input[type="text"]:focus, input[type="file"]:focus {
        border-color: #2563eb;
        outline: none;
        box-shadow: 0 0 8px #2563eb;
    }
    button {
        background-color: #2563eb;
        color: white;
        border: none;
        padding: 0.8rem 1.6rem;
        font-weight: 600;
        font-size: 1.1rem;
        border-radius: 10px;
        cursor: pointer;
        transition: background-color 0.3s;
        user-select: none;
    }
    button:hover, button:focus {
        background-color: #1e40af;
        outline: none;
    }
    .msg {
        margin: 1rem 0;
        font-weight: 600;
    }
    .msg.success {
        color: #10b981;
    }
    .msg.error {
        color: #ef4444;
    }
    .profile-pic-preview {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 1rem;
        border: 2px solid #2563eb;
        display: block;
    }
    .back-menu-btn {
    display: inline-block;
    margin-bottom: 2rem;
    font-weight: 600;
    font-size: 1.2rem;
    color: #2563eb;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 0.75rem;
    background: #e0e7ff;
    box-shadow: 0 4px 6px rgba(37, 99, 235, 0.1);
    transition: background-color 0.3s ease, color 0.3s ease;
    user-select: none;
    }
    .back-menu-btn:hover,
    .back-menu-btn:focus {
    color: white;
    background-color: #2563eb;
    outline: none;
    box-shadow: 0 6px 12px rgba(37, 99, 235, 0.3);
    }

</style>
</head>
<body>
<div class="container" role="main">
    <h1>Edit Profil</h1>

    <?php if ($success): ?>
        <p class="msg success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="msg error" role="alert"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" novalidate>
        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username_db, ENT_QUOTES); ?>" required minlength="1" maxlength="50" autocomplete="username" aria-required="true" />

        <label for="profile_pic">Foto Profil</label>
        <?php if ($profile_pic && file_exists(__DIR__ . '/../uploads/' . $profile_pic)): ?>
            <img src="../uploads/<?php echo htmlspecialchars($profile_pic); ?>" alt="Foto Profil" class="profile-pic-preview" />
        <?php else: ?>
            <img src="../assets/default-profile.png" alt="Default Foto Profil" class="profile-pic-preview" />
        <?php endif; ?>
        <input type="file" id="profile_pic" name="profile_pic" accept="image/png, image/jpeg, image/gif" aria-describedby="profile-pic-help" />
        <small id="profile-pic-help" style="color: #6b7280;">Format JPG, PNG, GIF. Maksimal 5MB.</small>

        <button type="submit">Simpan Perubahan</button>
        <a href="user_dashboard.php" class="back-menu-btn" aria-label="Back to menu">Kembali ke Menu</a>
    </form>
</div>
</body>
</html>
