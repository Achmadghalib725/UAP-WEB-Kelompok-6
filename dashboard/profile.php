<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/db.php';

$user_id = $_SESSION['user_id'];
$success = $error = "";

// Fetch existing user data including phone
$stmt = $conn->prepare("SELECT username, profile_pic, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username_db, $profile_pic, $phone_db);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = trim($_POST["username"]);
    $new_phone = trim($_POST["phone"]);

    if (empty($new_username)) {
        $error = "Username tidak boleh kosong.";
    } elseif (empty($new_phone)) {
        $error = "Nomor HP tidak boleh kosong.";
    } else {
        // Check username uniqueness except current user
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $checkStmt->bind_param("si", $new_username, $user_id);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $error = "Username sudah digunakan.";
        } else {
            $checkStmt->close();

            // File upload handling
            $upload_ok = true;
            $new_profile_pic = $profile_pic;

            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['profile_pic'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024;

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
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $new_filename = "profile_$user_id_" . uniqid() . "." . $ext;
                    $upload_dir = __DIR__ . '/../uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $destination = $upload_dir . $new_filename;

                    if (move_uploaded_file($file['tmp_name'], $destination)) {
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
                $updateStmt = $conn->prepare("UPDATE users SET username = ?, phone = ?, profile_pic = ? WHERE id = ?");
                $updateStmt->bind_param("sssi", $new_username, $new_phone, $new_profile_pic, $user_id);

                if ($updateStmt->execute()) {
                    $success = "Profil berhasil diperbarui.";
                    $_SESSION['username'] = $new_username;
                    $username_db = $new_username;
                    $phone_db = $new_phone;
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
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Edit Profil - DoTask</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

  :root {
    --color-bg: #ffffff;
    --color-text-primary: #1f2937;
    --color-text-secondary: #6b7280;
    --color-success: #10b981;
    --color-error: #ef4444;
    --color-primary: #2563eb;
    --color-primary-dark: #1e40af;
    --border-radius: 0.75rem;
    --max-width: 1200px;
  }

  html, body {
    margin: 0;
    padding: 0;
    font-family: 'Poppins', sans-serif;
    background: var(--color-bg);
    color: var(--color-text-primary);
    font-size: 18px;
    line-height: 1.6;
    min-height: 100vh;
  }

  main {
    max-width: var(--max-width);
    margin: 0 auto;
    padding: 4rem 3rem 5rem;
  }

  h1 {
    font-weight: 700;
    font-size: 48px;
    margin-bottom: 2.5rem;
    color: var(--color-primary);
  }

  form {
    background: #f9fafb;
    padding: 3rem 3.5rem;
    border-radius: var(--border-radius);
    box-shadow: 0 6px 12px rgba(0,0,0,0.07);
    max-width: 600px;
    margin: 0 auto;
  }

  label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.7rem;
    color: var(--color-text-secondary);
  }

  input[type="text"],
  input[type="file"] {
    width: 100%;
    padding: 14px 16px;
    font-size: 16px;
    border: 1.5px solid #d1d5db;
    border-radius: 0.5rem;
    transition: border-color 0.3s ease;
    outline-offset: 2px;
  }

  input[type="text"]:focus,
  input[type="file"]:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 8px var(--color-primary);
    outline: none;
  }

  .profile-pic-preview {
    display: block;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 1rem;
    border: 3px solid var(--color-primary);
  }

  button {
    width: 100%;
    background-color: var(--color-primary);
    color: white;
    font-weight: 700;
    font-size: 20px;
    padding: 16px 0;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: background-color 0.3s ease;
    user-select: none;
    margin-top: 2rem;
  }

  button:hover,
  button:focus {
    background-color: var(--color-primary-dark);
    outline: none;
  }

  .message {
    font-weight: 600;
    text-align: center;
    font-size: 16px;
    margin-top: 1.5rem;
  }

  .message.success {
    color: var(--color-success);
  }

  .message.error {
    color: var(--color-error);
  }

  .back-menu-btn {
    display: inline-block;
    margin: 3rem auto 0;
    text-align: center;
    font-weight: 600;
    font-size: 18px;
    color: var(--color-primary);
    text-decoration: none;
    border-radius: 0.5rem;
    padding: 12px 28px;
    box-shadow: 0 4px 8px rgba(37, 99, 235, 0.1);
    transition: background-color 0.3s ease, color 0.3s ease;
    user-select: none;
    max-width: 600px;
  }

  .back-menu-btn:hover,
  .back-menu-btn:focus {
    background-color: var(--color-primary);
    color: white;
    outline: none;
    box-shadow: 0 6px 12px rgba(37, 99, 235, 0.3);
  }
</style>
</head>
<body>
<main role="main" aria-labelledby="pageTitle">
  <h1 id="pageTitle">Edit Profil</h1>

  <?php if ($success): ?>
    <p class="message success" role="alert"><?php echo htmlspecialchars($success); ?></p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="message error" role="alert"><?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" novalidate>
    <label for="username">Username</label>
    <input
      type="text"
      id="username"
      name="username"
      value="<?php echo htmlspecialchars($username_db, ENT_QUOTES); ?>"
      required
      minlength="1"
      maxlength="50"
      autocomplete="username"
      aria-required="true"
    />

    <label for="phone">Nomor HP</label>
    <input
      type="text"
      id="phone"
      name="phone"
      value="<?php echo htmlspecialchars($phone_db, ENT_QUOTES); ?>"
      required
      minlength="10"
      maxlength="15"
      autocomplete="tel"
      aria-required="true"
    />

    <label for="profile_pic" style="margin-top: 2rem;">Foto Profil</label>
    <?php if ($profile_pic && file_exists(__DIR__ . '/../uploads/' . $profile_pic)): ?>
      <img
        src="../uploads/<?php echo htmlspecialchars($profile_pic); ?>"
        alt="Foto Profil"
        class="profile-pic-preview"
        width="150" height="150"
      />
    <?php else: ?>
      <img
        src="../assets/default-profile.png"
        alt="Default Foto Profil"
        class="profile-pic-preview"
        width="150" height="150"
      />
    <?php endif; ?>
    <input
      type="file"
      id="profile_pic"
      name="profile_pic"
      accept="image/png, image/jpeg, image/gif"
      aria-describedby="profile-pic-help"
    />
    <small id="profile-pic-help" style="display: block; text-align: center; color: #6b7280; margin-top: 0.3rem;">Format JPG, PNG, GIF. Maksimal 5MB.</small>

    <button type="submit">Simpan Perubahan</button>
  </form>

  <a href="user_dashboard.php" class="back-menu-btn" aria-label="Kembali ke menu">
    &larr; Kembali ke Menu
  </a>
</main>
</body>
</html>
