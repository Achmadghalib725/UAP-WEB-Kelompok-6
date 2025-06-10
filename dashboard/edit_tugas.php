<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/db.php';

$user_id = $_SESSION['user_id'];
$task_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($task_id === 0) {
    header("Location: daftar_tugas.php");
    exit;
}

// Initialize variables for form values and errors
$task_name = $description = $due_date = $priority = $status = $tags = $comments = $recurrence = "";
$error = "";
$success = "";

// Valid options
$priority_options = ['Low', 'Medium', 'High'];
$status_options = ['Belum selesai', 'Sedang dikerjakan', 'Selesai'];

// Fetch current task data to pre-fill the form
$stmt = $conn->prepare("SELECT task_name, description, due_date, priority, status, tags, comments, recurrence FROM tasks WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $task_id, $user_id);
$stmt->execute();
$stmt->bind_result($task_name_db, $description_db, $due_date_db, $priority_db, $status_db, $tags_db, $comments_db, $recurrence_db);
if (!$stmt->fetch()) {
    $stmt->close();
    header("Location: daftar_tugas.php");
    exit;
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        $task_name = trim($_POST['task_name']);
        $description = trim($_POST['description']);
        $due_date = trim($_POST['due_date']);
        $priority = trim($_POST['priority']);
        $status_raw = trim($_POST['status']);
        $tags = trim($_POST['tags']);
        $comments = trim($_POST['comments']);
        $recurrence = trim($_POST['recurrence']);

        // Validate task_name
        if ($task_name === "") {
            $error = "Nama tugas wajib diisi.";
        } else {
            // Validate priority: allow empty or predefined options only
            if ($priority !== "" && !in_array($priority, $priority_options, true)) {
                $priority = "";
            }
            // Validate status: must be in options; else set to default 'Belum selesai'
            if (!in_array($status_raw, $status_options, true)) {
                $status = 'Belum selesai';
            } else {
                $status = $status_raw;
            }

            // Prepare update statement
            $stmt = $conn->prepare("UPDATE tasks SET task_name=?, description=?, due_date=?, priority=?, status=?, tags=?, comments=?, recurrence=? WHERE id=? AND user_id=?");
            $stmt->bind_param("ssssssssii", $task_name, $description, $due_date, $priority, $status, $tags, $comments, $recurrence, $task_id, $user_id);
            if ($stmt->execute()) {
                $success = "Tugas berhasil diperbarui.";
            } else {
                $error = "Gagal memperbarui tugas. Silakan coba lagi.";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete'])) {
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $task_id, $user_id);
        if ($stmt->execute()) {
            $stmt->close();
            // Redirect to detail page after deletion - detail page should handle missing task gracefully
            header("Location: lihat_tugas_detail.php?id=$task_id&deleted=1");
            exit;
        } else {
            $error = "Gagal menghapus tugas. Silakan coba lagi.";
        }
        $stmt->close();
    }
} else {
    // Set initial form values from db
    $task_name = $task_name_db;
    $description = $description_db;
    $due_date = $due_date_db;
    $priority = $priority_db;
    $status = $status_db;
    $tags = $tags_db;
    $comments = $comments_db;
    $recurrence = $recurrence_db;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Edit Tugas - <?= htmlspecialchars($task_name) ?> - DoTask</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

  :root {
    --color-bg: #ffffff;
    --color-text-primary: #1f2937;
    --color-text-secondary: #6b7280;
    --color-primary: #111827;
    --color-primary-hover: #374151;
    --color-error: #dc2626;
    --color-success: #16a34a;
    --color-input-border: #d1d5db;
    --color-shadow: rgba(0, 0, 0, 0.05);
    --border-radius: 0.75rem;
    --max-width: 720px;
    --font-family: 'Poppins', sans-serif;
  }

  body {
    margin: 0;
    background: var(--color-bg);
    color: var(--color-text-primary);
    font-family: var(--font-family);
    line-height: 1.6;
    padding: 3rem 1.5rem 4rem;
    display: flex;
    justify-content: center;
  }

  main.container {
    width: 100%;
    max-width: var(--max-width);
    background: var(--color-bg);
    border-radius: var(--border-radius);
    box-shadow: 0 8px 24px var(--color-shadow);
    padding: 3rem 4rem;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }

  h1 {
    font-weight: 700;
    font-size: 3rem;
    margin: 0 0 1rem 0;
    color: var(--color-text-primary);
    line-height: 1.1;
  }

  form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  label {
    font-weight: 600;
    color: var(--color-text-primary);
    margin-bottom: 0.4rem;
    display: inline-block;
  }

  input[type="text"],
  input[type="date"],
  select,
  textarea {
    width: 100%;
    padding: 0.65rem 1rem;
    font-size: 1rem;
    color: var(--color-text-primary);
    background: #f9fafb;
    border: 1.5px solid var(--color-input-border);
    border-radius: var(--border-radius);
    transition: border-color 0.25s ease;
    font-family: var(--font-family);
    resize: vertical;
    min-height: 40px;
  }

  input[type="text"]:focus,
  input[type="date"]:focus,
  select:focus,
  textarea:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(17,24,39,0.15);
    background: white;
  }

  textarea {
    min-height: 120px;
  }

  .btn-group {
    display: flex;
    gap: 1rem;
    margin-top: 1.25rem;
    flex-wrap: wrap;
  }

  button,
  .btn-back {
    flex: 1;
    padding: 0.75rem 1.5rem;
    font-weight: 700;
    font-size: 1.125rem;
    border-radius: var(--border-radius);
    cursor: pointer;
    border: none;
    transition: background-color 0.3s ease, color 0.3s ease, transform 0.25s ease;
    font-family: var(--font-family);
    user-select: none;
    text-align: center;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  button.save-btn {
    background-color: var(--color-primary);
    color: white;
    border: none;
  }

  button.save-btn:hover,
  button.save-btn:focus-visible {
    background-color: var(--color-primary-hover);
    transform: scale(1.05);
    outline: none;
  }

  button.delete-btn {
    background-color: var(--color-error);
    color: white;
    border: none;
  }

  button.delete-btn:hover,
  button.delete-btn:focus-visible {
    background-color: #b91c1c;
    transform: scale(1.05);
    outline: none;
  }

  .btn-back {
    background-color: #6b7280;
    color: white;
    text-decoration: none;
  }

  .btn-back:hover,
  .btn-back:focus-visible {
    background-color: #4b5563;
    transform: scale(1.05);
    outline: none;
  }

  .message {
    padding: 0.75rem 1rem;
    border-radius: var(--border-radius);
    font-weight: 600;
    font-size: 1rem;
  }

  .message.error {
    background-color: #fee2e2;
    color: var(--color-error);
  }

  .message.success {
    background-color: #dcfce7;
    color: var(--color-success);
  }

  @media (max-width: 600px) {
    main.container {
      padding: 2rem 1.5rem;
    }
    .btn-group {
      flex-direction: column;
    }
    button,
    .btn-back {
      width: 100%;
      flex: none;
    }
  }
</style>
</head>
<body>
<main class="container" role="main" aria-labelledby="pageTitle">
  <h1 id="pageTitle">Edit Tugas</h1>

  <?php if ($error): ?>
    <div class="message error" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="message success" role="status"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="post" novalidate>
    <label for="task_name">Nama Tugas <span aria-hidden="true">*</span></label>
    <input type="text" id="task_name" name="task_name" required value="<?= htmlspecialchars($task_name) ?>" aria-required="true" />

    <label for="description">Deskripsi</label>
    <textarea id="description" name="description" rows="4"><?= htmlspecialchars($description) ?></textarea>

    <label for="due_date">Jatuh Tempo</label>
    <input type="date" id="due_date" name="due_date" value="<?= htmlspecialchars($due_date) ?>" />

    <label for="priority">Prioritas</label>
    <select id="priority" name="priority">
      <option value="">Pilih prioritas</option>
      <?php foreach ($priority_options as $opt): ?>
        <option value="<?= $opt ?>" <?= $opt === $priority ? 'selected' : '' ?>><?= $opt ?></option>
      <?php endforeach; ?>
    </select>

    <label for="status">Status</label>
    <select id="status" name="status">
      <option value="">Pilih status</option>
      <?php foreach ($status_options as $opt): ?>
        <option value="<?= $opt ?>" <?= $opt === $status ? 'selected' : '' ?>><?= $opt ?></option>
      <?php endforeach; ?>
    </select>

    <label for="tags">Tags (pisahkan dengan koma)</label>
    <input type="text" id="tags" name="tags" value="<?= htmlspecialchars($tags) ?>" />

    <label for="comments">Catatan</label>
    <textarea id="comments" name="comments" rows="3"><?= htmlspecialchars($comments) ?></textarea>

    <label for="recurrence">Rekurensi (misal: Harian, Mingguan)</label>
    <input type="text" id="recurrence" name="recurrence" value="<?= htmlspecialchars($recurrence) ?>" />

    <div class="btn-group">
      <a href="lihat_tugas_detail.php?id=<?= $task_id ?>" class="btn-back" aria-label="Kembali ke detail tugas">Kembali ke Detail</a>
      <button type="submit" name="save" class="save-btn" aria-label="Simpan perubahan tugas">Simpan</button>
      <button 
        type="submit" 
        name="delete" 
        class="delete-btn"
        aria-label="Hapus tugas"
        onclick="return confirm('Yakin ingin menghapus tugas ini? Tindakan ini tidak dapat dibatalkan.')"
      >Hapus Tugas</button>
    </div>
  </form>
</main>
</body>
</html>

