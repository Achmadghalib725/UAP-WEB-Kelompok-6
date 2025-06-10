<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Initialize variables for form fields
$task_name = $description = $due_date = $priority = $status = "";
$tags = $comments = $recurrence = $parent_task_id = $completion_date = "";
$error = "";
$success = "";

$priority_options = ['Low', 'Medium', 'High'];
$status_options = ['Pending', 'In Progress', 'Completed', 'Overdue'];

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
    $due_date = trim($_POST['due_date'] ?? '');
    $priority = $_POST['priority'] ?? 'Medium';
    $status = $_POST['status'] ?? 'Pending';
    $tags = trim($_POST['tags'] ?? '');
    $comments = trim($_POST['comments'] ?? '');
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

        $due_date = $due_date === '' ? null : $due_date;
        $tags = $tags === '' ? null : $tags;
        $comments = $comments === '' ? null : $comments;
        $recurrence = $recurrence === '' ? null : $recurrence;
        $parent_task_id = $parent_task_id === '' ? null : $parent_task_id;
        $completion_date = $completion_date === '' ? null : $completion_date;

        $stmt = $conn->prepare("INSERT INTO tasks (user_id, task_name, description, due_date, priority, status, tags, comments, recurrence, parent_task_id, completion_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "issssssssis",
            $user_id,
            $task_name,
            $description,
            $due_date,
            $priority,
            $status,
            $tags,
            $comments,
            $recurrence,
            $parent_task_id,
            $completion_date
        );

        if ($stmt->execute()) {
            $success = "Tugas berhasil ditambahkan.";
            $task_name = $description = $due_date = $priority = $status = "";
            $tags = $comments = $recurrence = $parent_task_id = $completion_date = "";
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
      --color-primary: #5cb85c;
      --color-primary-dark: #4cae4c;
      --color-error: #ef4444;
      --color-success: #10b981;
      --border-radius: 0.75rem;
      --shadow-light: rgba(0,0,0,0.07);
      --max-width: 1200px;
      --gap: 2rem;
      --transition-speed: 0.3s;
      --font-family: 'Poppins', sans-serif;
      --input-bg: #fafafa;
      --input-border: #d1d5db;
      --input-focus-border: var(--color-primary);
      --input-focus-shadow: 0 0 8px var(--color-primary);
    }

    html, body {
      margin: 0;
      padding: 0;
      font-family: var(--font-family);
      background: var(--color-bg);
      color: var(--color-text-primary);
      font-size: 18px;
      line-height: 1.6;
      min-height: 100vh;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    main {
      max-width: var(--max-width);
      margin: 3rem auto 4rem;
      padding: 0 2rem;
      box-sizing: border-box;
    }

    h1 {
      font-weight: 700;
      font-size: 48px;
      margin-bottom: 2rem;
      color: var(--color-primary);
      user-select: none;
    }

    form {
      background: var(--input-bg);
      padding: 3rem 3.5rem;
      border-radius: var(--border-radius);
      box-shadow: 0 6px 16px var(--shadow-light);
      max-width: 900px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: var(--gap);
      box-sizing: border-box;
    }

    label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.6rem;
      color: var(--color-text-secondary);
      user-select: none;
    }

    input[type="text"],
    input[type="date"],
    select,
    textarea {
      width: 100%;
      padding: 12px 14px;
      font-size: 1rem;
      font-family: var(--font-family);
      border: 1.5px solid var(--input-border);
      border-radius: 0.5rem;
      background-color: white;
      resize: vertical;
      transition: border-color var(--transition-speed) ease, box-shadow var(--transition-speed) ease;
      outline-offset: 2px;
    }

    input[type="text"]:focus,
    input[type="date"]:focus,
    select:focus,
    textarea:focus {
      border-color: var(--input-focus-border);
      box-shadow: var(--input-focus-shadow);
      outline: none;
    }

    textarea {
      min-height: 80px;
    }

    .full-width {
      grid-column: 1 / -1;
    }

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
      transition: background-color var(--transition-speed) ease;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      background-color: var(--color-primary);
      color: white;
      box-sizing: border-box;
    }

    button:hover,
    button:focus {
      background-color: var(--color-primary-dark);
      outline: none;
      box-shadow: 0 4px 12px rgba(76,174,76,0.5);
    }

    button[type="reset"] {
      background-color: #f3f4f6;
      color: var(--color-text-secondary);
      box-shadow: none;
    }

    button[type="reset"]:hover,
    button[type="reset"]:focus {
      background-color: #e5e7eb;
      color: var(--color-text-primary);
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .message {
      font-weight: 600;
      font-size: 1rem;
      margin-top: 1rem;
      grid-column: 1 / -1;
      text-align: center;
      user-select: none;
    }

    .error {
      color: var(--color-error);
    }

    .success {
      color: var(--color-success);
    }

    .back-link {
      display: inline-block;
      margin: 2rem auto 0;
      color: var(--color-primary);
      font-weight: 600;
      font-size: 1.1rem;
      text-decoration: none;
      border-radius: 0.5rem;
      padding: 0.4rem 1rem;
      box-shadow: 0 3px 8px rgba(92,184,92,0.3);
      user-select: none;
      transition: background-color 0.3s ease, color 0.3s ease;
      text-align: center;
      max-width: 900px;
    }

    .back-link:hover,
    .back-link:focus {
      background-color: var(--color-primary);
      color: white;
      outline: none;
    }

    @media (max-width: 600px) {
      form {
        grid-template-columns: 1fr;
        padding: 2rem 2rem;
      }
      .buttons {
        justify-content: center;
      }
      .back-link {
        margin: 1.5rem auto 0;
        display: block;
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

