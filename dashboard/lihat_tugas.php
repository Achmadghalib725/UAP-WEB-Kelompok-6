<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/db.php';

$user_id = $_SESSION['user_id'];

// Fetch all tasks for the logged-in user with all columns needed
$stmt = $conn->prepare("SELECT id, task_name, description, assigned_user_id, due_date, priority, status, tags, comments, estimated_time, actual_time, recurrence, parent_task_id, completion_date FROM tasks WHERE user_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);

// Fetch map of user IDs to usernames for assigned_user_id
$user_map = [];
$user_result = $conn->query("SELECT id, username FROM users");
if ($user_result) {
    while ($row = $user_result->fetch_assoc()) {
        $user_map[$row['id']] = $row['username'];
    }
}

// Fetch map of task IDs to task names for parent_task_id
$task_map = [];
$task_result = $conn->query("SELECT id, task_name FROM tasks");
if ($task_result) {
    while ($row = $task_result->fetch_assoc()) {
        $task_map[$row['id']] = $row['task_name'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Daftar Tugas - DoTask</title>
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
      --gap: 2rem;
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
      margin-bottom: 1.5rem;
      color: var(--color-primary);
    }

    .tasks-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill,minmax(320px,1fr));
      gap: 2rem;
    }

    .task-card {
      background: #f9fafb;
      border-radius: var(--border-radius);
      box-shadow: 0 6px 12px var(--shadow-light);
      padding: 1.8rem 2rem;
      display: flex;
      flex-direction: column;
      gap: 0.8rem;
      transition: box-shadow 0.3s ease;
      user-select: none;
      cursor: pointer;
    }

    .task-card:hover,
    .task-card:focus-within {
      box-shadow: 0 10px 25px rgba(37, 99, 235, 0.15);
      outline: none;
    }

    .task-title {
      font-size: 1.5rem;
      font-weight: 700;
      margin: 0;
      color: var(--color-primary-dark);
    }

    .task-meta {
      font-size: 0.95rem;
      color: var(--color-text-secondary);
      margin: 0;
      line-height: 1.3;
    }

    .meta-label {
      font-weight: 600;
      color: var(--color-text-primary);
    }

    .no-tasks {
      font-size: 1.25rem;
      color: var(--color-text-secondary);
      text-align: center;
      margin-top: 4rem;
      user-select: none;
    }

    .back-btn {
      align-self: flex-start;
      background: var(--color-primary);
      color: white;
      font-weight: 600;
      padding: 12px 28px;
      border: none;
      border-radius: var(--border-radius);
      cursor: pointer;
      text-decoration: none;
      font-size: 1.1rem;
      transition: background-color 0.3s ease;
      user-select: none;
      margin-bottom: 1rem;
    }
    .back-btn:hover,
    .back-btn:focus {
      background: var(--color-primary-dark);
      outline: none;
    }
  </style>
</head>
<body>
  <main role="main" aria-labelledby="pageTitle">
    <a href="user_dashboard.php" class="back-btn" aria-label="Kembali ke dashboard">&larr; Kembali ke Dashboard</a>
    <h1 id="pageTitle">Daftar Tugas Saya</h1>

    <?php if (empty($tasks)): ?>
      <p class="no-tasks" role="status">Belum ada tugas yang dibuat.</p>
    <?php else: ?>
      <section class="tasks-grid" aria-live="polite" aria-label="Daftar tugas">
        <?php foreach ($tasks as $task): ?>
          <article class="task-card" tabindex="0" aria-label="Tugas <?= htmlspecialchars($task['task_name']); ?>">
            <h2 class="task-title"><?= htmlspecialchars($task['task_name']); ?></h2>

            <p class="task-meta"><span class="meta-label">Status:</span> <?= htmlspecialchars($task['status']); ?></p>
            <p class="task-meta"><span class="meta-label">Prioritas:</span> <?= htmlspecialchars($task['priority']); ?></p>
            <?php if ($task['assigned_user_id'] && isset($user_map[$task['assigned_user_id']])): ?>
              <p class="task-meta"><span class="meta-label">Ditugaskan ke:</span> <?= htmlspecialchars($user_map[$task['assigned_user_id']]); ?></p>
            <?php endif; ?>
            <?php if ($task['due_date']): ?>
              <p class="task-meta"><span class="meta-label">Jatuh Tempo:</span> <?= htmlspecialchars($task['due_date']); ?></p>
            <?php endif; ?>
            <?php if ($task['completion_date']): ?>
              <p class="task-meta"><span class="meta-label">Tanggal Selesai:</span> <?= htmlspecialchars($task['completion_date']); ?></p>
            <?php endif; ?>
            <?php if ($task['description']): ?>
              <p class="task-meta"><span class="meta-label">Deskripsi:</span> <?= nl2br(htmlspecialchars($task['description'])); ?></p>
            <?php endif; ?>
            <?php if ($task['tags']): ?>
              <p class="task-meta"><span class="meta-label">Tags:</span> <?= htmlspecialchars($task['tags']); ?></p>
            <?php endif; ?>
            <?php if ($task['comments']): ?>
              <p class="task-meta"><span class="meta-label">Catatan:</span> <?= nl2br(htmlspecialchars($task['comments'])); ?></p>
            <?php endif; ?>
            <?php if ($task['estimated_time'] !== null): ?>
              <p class="task-meta"><span class="meta-label">Perkiraan Waktu:</span> <?= (int)$task['estimated_time']; ?> menit</p>
            <?php endif; ?>
            <?php if ($task['actual_time'] !== null): ?>
              <p class="task-meta"><span class="meta-label">Waktu Sebenarnya:</span> <?= (int)$task['actual_time']; ?> menit</p>
            <?php endif; ?>
            <?php if ($task['recurrence']): ?>
              <p class="task-meta"><span class="meta-label">Rekurensi:</span> <?= htmlspecialchars($task['recurrence']); ?></p>
            <?php endif; ?>
            <?php if ($task['parent_task_id'] && isset($task_map[$task['parent_task_id']])): ?>
              <p class="task-meta"><span class="meta-label">Subtugas Dari:</span> <?= htmlspecialchars($task_map[$task['parent_task_id']]); ?></p>
            <?php endif; ?>
            <a href="lihat_tugas_detail.php?id=<?= $task['id']; ?>" class="task-detail-link" aria-label="Lihat detail tugas">Lihat Detail</a>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
