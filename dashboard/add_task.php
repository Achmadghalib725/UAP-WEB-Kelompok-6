<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Initialize variables for form fields
$task_name = $description = $assigned_user_id = $due_date = $priority = $status = "";
$tags = $comments = $attachments = $estimated_time = $actual_time = $recurrence = $parent_task_id = $completion_date = "";
$error = "";
$success = "";

// Priority and status options for select inputs
$priority_options = ['Low', 'Medium', 'High'];
$status_options = ['Pending', 'In Progress', 'Completed', 'Overdue'];

// Fetch users list for assigned_user_id dropdown
$users = [];
$user_result = $conn->query("SELECT id, username FROM users ORDER BY username");
if ($user_result) {
    while ($row = $user_result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Fetch tasks list for parent_task_id dropdown (optional subtasks)
$tasks = [];
$task_result = $conn->query("SELECT id, task_name FROM tasks ORDER BY task_name");
if ($task_result) {
    while ($row = $task_result->fetch_assoc()) {
        $tasks[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_name = trim($_POST['task_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $assigned_user_id = trim($_POST['assigned_user_id'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');
    $priority = $_POST['priority'] ?? 'Medium';
    $status = $_POST['status'] ?? 'Pending';
    $tags = trim($_POST['tags'] ?? '');
    $comments = trim($_POST['comments'] ?? '');
    $estimated_time = trim($_POST['estimated_time'] ?? '');
    $actual_time = trim($_POST['actual_time'] ?? '');
    $recurrence = trim($_POST['recurrence'] ?? '');
    $parent_task_id = trim($_POST['parent_task_id'] ?? '');
    $completion_date = trim($_POST['completion_date'] ?? '');

    if (empty($task_name)) {
        $error = "Nama tugas wajib diisi.";
    } elseif (strlen($task_name) > 255) {
        $error = "Nama tugas maksimal 255 karakter.";
    } elseif (!in_array($priority, $priority_options)) {
        $error = "Prioritas tidak valid.";
    } elseif (!in_array($status, $status_options)) {
        $error = "Status tidak valid.";
    } else {
        $user_id = $_SESSION['user_id'];

        // For nullables: convert empty strings to null
        $assigned_user_id = $assigned_user_id === '' ? null : $assigned_user_id;
        $due_date = $due_date === '' ? null : $due_date;
        $tags = $tags === '' ? null : $tags;
        $comments = $comments === '' ? null : $comments;
        $estimated_time = $estimated_time === '' ? null : $estimated_time;
        $actual_time = $actual_time === '' ? null : $actual_time;
        $recurrence = $recurrence === '' ? null : $recurrence;
        $parent_task_id = $parent_task_id === '' ? null : $parent_task_id;
        $completion_date = $completion_date === '' ? null : $completion_date;

        $stmt = $conn->prepare("INSERT INTO tasks (user_id, task_name, description, assigned_user_id, due_date, priority, status, tags, comments, estimated_time, actual_time, recurrence, parent_task_id, completion_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "ississssssssis",
            $user_id,
            $task_name,
            $description,
            $assigned_user_id,
            $due_date,
            $priority,
            $status,
            $tags,
            $comments,
            $estimated_time,
            $actual_time,
            $recurrence,
            $parent_task_id,
            $completion_date
        );

        if ($stmt->execute()) {
            $success = "Tugas berhasil ditambahkan.";
            // Reset form fields except user_id
            $task_name = $description = $assigned_user_id = $due_date = $priority = $status = "";
            $tags = $comments = $attachments = $estimated_time = $actual_time = $recurrence = $parent_task_id = $completion_date = "";
        } else {
            $error = "Gagal menyimpan tugas ke database.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Tambah Tugas - DoTask</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

    :root {
      --color-bg: #ffffff;
      --color-text-primary: #1f2937;
      --color-text-secondary: #6b7280;
      --color-primary: #2563eb;
      --color-primary-dark: #1e40af;
      --color-error: #ef4444;
      --color-success: #10b981;
      --border-radius: 0.75rem;
      --shadow-light: rgba(0,0,0,0.07);
      --max-width: 1200px;
      --gap: 1.8rem;
    }
    html, body {
      margin: 0; padding: 0;
      font-family: 'Poppins', sans-serif;
      background: var(--color-bg);
      color: var(--color-text-primary);
      min-height: 100vh;
    }
    main {
      max-width: var(--max-width);
      margin: 0 auto;
      padding: 4rem 3rem 5rem;
      display: flex;
      flex-direction: column;
      gap: var(--gap);
    }
    h1 {
      font-size: 48px;
      font-weight: 700;
      margin-bottom: 2rem;
      color: var(--color-primary);
    }
    form {
      background: #f9fafb;
      padding: 3rem 3.5rem;
      border-radius: var(--border-radius);
      box-shadow: 0 6px 12px var(--shadow-light);
      max-width: 900px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: var(--gap);
      box-sizing: border-box;
    }
    label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.6rem;
      color: var(--color-text-secondary);
    }
    input[type="text"],
    input[type="date"],
    input[type="number"],
    select,
    textarea {
      width: 100%;
      padding: 12px 14px;
      font-size: 1rem;
      border: 1.5px solid #d1d5db;
      border-radius: 0.5rem;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
      outline-offset: 2px;
      font-family: inherit;
      resize: vertical;
    }
    input[type="text"]:focus,
    input[type="date"]:focus,
    input[type="number"]:focus,
    select:focus,
    textarea:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 8px var(--color-primary);
      outline: none;
    }
    textarea {
      min-height: 80px;
    }
    /* Full width on all columns for some inputs */
    .full-width {
      grid-column: 1 / -1;
    }
    /* Buttons container */
    .buttons {
      grid-column: 1 / -1;
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
    }
    button {
      font-weight: 600;
      font-size: 1.1rem;
      padding: 14px 36px;
      border-radius: var(--border-radius);
      cursor: pointer;
      user-select: none;
      border: none;
      transition: background-color 0.3s ease;
    }
    button[type="submit"] {
      background-color: var(--color-primary);
      color: white;
    }
    button[type="submit"]:hover,
    button[type="submit"]:focus {
      background-color: var(--color-primary-dark);
      outline: none;
    }
    button[type="reset"] {
      background-color: #ededed;
      color: #555;
    }
    button[type="reset"]:hover,
    button[type="reset"]:focus {
      background-color: #d6d6d6;
      outline: none;
    }
    .message {
      font-weight: 600;
      font-size: 1rem;
      margin-top: 1rem;
      text-align: center;
      grid-column: 1 / -1;
    }
    .error {
      color: var(--color-error);
    }
    .success {
      color: var(--color-success);
    }
    @media (max-width: 800px) {
      form {
        display: flex;
        flex-direction: column;
      }
      .full-width {
        grid-column: auto;
      }
      .buttons {
        justify-content: center;
        gap: 1rem;
      }
    }
  </style>
</head>
<body>
  <main role="main" aria-labelledby="pageTitle">
    <h1 id="pageTitle">Tambah Tugas Baru</h1>

    <?php if ($error): ?>
      <p class="message error" role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
      <p class="message success" role="alert"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div>
        <label for="task_name">Nama Tugas *</label>
        <input type="text" id="task_name" name="task_name" maxlength="255" required aria-required="true" value="<?= htmlspecialchars($task_name) ?>" />
      </div>

      <div>
        <label for="assigned_user_id">Ditugaskan ke</label>
        <select id="assigned_user_id" name="assigned_user_id" aria-label="Ditugaskan ke user">
          <option value="">-- Pilih User --</option>
          <?php foreach ($users as $user): ?>
            <option value="<?= $user['id'] ?>" <?= ($assigned_user_id == $user['id']) ? 'selected' : '' ?>><?= htmlspecialchars($user['username']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="priority">Prioritas</label>
        <select id="priority" name="priority">
          <?php foreach ($priority_options as $p): ?>
            <option value="<?= $p ?>" <?= ($priority === $p) ? 'selected' : '' ?>><?= $p ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="status">Status</label>
        <select id="status" name="status">
          <?php foreach ($status_options as $s): ?>
            <option value="<?= $s ?>" <?= ($status === $s) ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="due_date">Tanggal Jatuh Tempo</label>
        <input type="date" id="due_date" name="due_date" value="<?= htmlspecialchars($due_date) ?>" />
      </div>

      <div>
        <label for="estimated_time">Perkiraan Waktu (menit)</label>
        <input type="number" id="estimated_time" name="estimated_time" min="0" step="1" value="<?= htmlspecialchars($estimated_time) ?>" />
      </div>

      <div>
        <label for="actual_time">Waktu Sebenarnya (menit)</label>
        <input type="number" id="actual_time" name="actual_time" min="0" step="1" value="<?= htmlspecialchars($actual_time) ?>" />
      </div>

      <div>
        <label for="recurrence">Rekurensi</label>
        <input type="text" id="recurrence" name="recurrence" placeholder="Contoh: daily, weekly, monthly" value="<?= htmlspecialchars($recurrence) ?>" />
      </div>

      <div>
        <label for="parent_task_id">Subtugas Dari</label>
        <select id="parent_task_id" name="parent_task_id">
          <option value="">-- Tidak ada --</option>
          <?php foreach ($tasks as $t): ?>
            <option value="<?= $t['id'] ?>" <?= ($parent_task_id == $t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['task_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="full-width">
        <label for="description">Deskripsi</label>
        <textarea id="description" name="description" rows="4"><?= htmlspecialchars($description) ?></textarea>
      </div>

      <div class="full-width">
        <label for="tags">Tags (pisahkan dengan koma)</label>
        <input type="text" id="tags" name="tags" value="<?= htmlspecialchars($tags) ?>" />
      </div>

      <div class="full-width">
        <label for="comments">Komentar / Catatan</label>
        <textarea id="comments" name="comments" rows="3"><?= htmlspecialchars($comments) ?></textarea>
      </div>

      <div>
        <label for="completion_date">Tanggal Selesai</label>
        <input type="date" id="completion_date" name="completion_date" value="<?= htmlspecialchars($completion_date) ?>" />
      </div>

      <div class="buttons full-width">
        <button type="reset" aria-label="Reset form data">Reset</button>
        <button type="submit" aria-label="Simpan tugas baru">Simpan</button>
      </div>
    </form>

    <a href="user_dashboard.php" class="back-link" aria-label="Kembali ke Dashboard">
      &larr; Kembali ke Dashboard
    </a>
  </main>
</body>
</html>

