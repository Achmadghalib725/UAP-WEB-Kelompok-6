<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/db.php';

$user_id = $_SESSION['user_id'];

// Fetch all tasks for the user with all relevant details
$stmt = $conn->prepare("SELECT id, task_name, description, assigned_user_id, due_date, priority, status, tags, comments, recurrence, parent_task_id, completion_date FROM tasks WHERE user_id = ? ORDER BY due_date ASC, id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);

// Map user ids to usernames for assigned_user_id display (optional)
$user_map = [];
$user_result = $conn->query("SELECT id, username FROM users");
if ($user_result) {
  while ($row = $user_result->fetch_assoc()) {
    $user_map[$row['id']] = $row['username'];
  }
}

// Map parent task ids to task names for subtasks
$task_map = [];
$task_result = $conn->query("SELECT id, task_name FROM tasks");
if ($task_result) {
  while ($row = $task_result->fetch_assoc()) {
    $task_map[$row['id']] = $row['task_name'];
  }
}

// Profile picture for navbar
$stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($profile_pic_filename);
$stmt->fetch();
$stmt->close();

$upload_dir = __DIR__ . '/../uploads/';
$default_profile_pic = '../assets/default-profile.png';
$profile_pic_path = ($profile_pic_filename && file_exists($upload_dir . $profile_pic_filename))
    ? '../uploads/' . htmlspecialchars($profile_pic_filename, ENT_QUOTES, 'UTF-8')
    : $default_profile_pic;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Daftar Tugas Lengkap - DoTask</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

  :root {
    --color-bg: #ffffff;
    --color-text-primary: #1f2937;
    --color-text-secondary: #6b7280;
    --color-primary: #5cb85c;
    --color-primary-dark: #4cae4c;
    --color-card-bg: #f9fafb;
    --color-shadow: rgba(0, 0, 0, 0.05);
    --border-radius: 0.75rem;
    --nav-height: 64px;
    --max-width: 1200px;
    --gap: 1.8rem;
    --font-family: 'Poppins', sans-serif;
    --transition-speed: 0.3s;
  }

  /* Reset */
  *, *::before, *::after {
    box-sizing: border-box;
  }

  body, html {
    margin: 0; padding: 0;
    font-family: var(--font-family);
    background-color: var(--color-bg);
    color: var(--color-text-primary);
    font-size: 18px;
    line-height: 1.6;
    min-height: 100vh;
  }

  a {
    color: inherit;
    text-decoration: none;
  }
  a:hover,
  a:focus {
    text-decoration: underline;
    outline: none;
  }

  nav.topnav {
    position: sticky;
    top: 0; left: 0; right: 0;
    height: var(--nav-height);
    background-color: var(--color-primary);
    display: flex;
    align-items: center;
    gap: 2rem;
    padding: 0 1.5rem;
    box-shadow: 0 2px 10px var(--color-shadow);
    z-index: 1000;
  }

  .profile-dropdown {
    position: relative;
    margin-left: auto;
    user-select: none;
    cursor: pointer;
  }

  .profile-dropdown-inner {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
  }

  .profile-dropdown-inner img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid white;
  }

  .dropdown-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: none;
    flex-direction: column;
    min-width: 160px;
    font-weight: 600;
    color: var(--color-text-primary);
    padding: 0.5rem 0;
    z-index: 1100;
  }
  .dropdown-menu.show {
    display: flex;
  }
  .dropdown-menu a {
    padding: 0.6rem 1.25rem;
    transition: background-color 0.2s ease;
  }
  .dropdown-menu a:hover,
  .dropdown-menu a:focus {
    background-color: var(--color-primary);
    color: white;
    outline: none;
  }

  .menu {
    display: flex;
    gap: 1.5rem;
    font-weight: 600;
    font-size: 1.1rem;
  }
  .menu a {
    padding: 0.5rem 0.75rem;
    border-radius: var(--border-radius);
    transition: background-color var(--transition-speed) ease, color var(--transition-speed) ease;
  }
  .menu a:hover,
  .menu a:focus {
    background-color: rgba(255 255 255 / 0.2);
    color: white;
    outline: none;
  }

  main.main {
    max-width: var(--max-width);
    margin: 2rem auto 3rem;
    padding: 0 1.5rem;
  }

  header.main-header {
    margin-bottom: 2rem;
  }

  header.main-header h1 {
    font-weight: 800;
    font-size: 48px;
    margin: 0;
    color: var(--color-text-primary);
  }

  .tasks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill,minmax(320px,1fr));
    gap: var(--gap);
  }

  .task-card {
    background: var(--color-card-bg);
    border-radius: var(--border-radius);
    box-shadow: 0 6px 12px var(--color-shadow);
    padding: 1.5rem 2rem;
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
    cursor: pointer;
    transition: box-shadow 0.3s ease;
    outline-offset: 2px;
  }

  .task-card:hover,
  .task-card:focus-within {
    box-shadow: 0 10px 30px rgba(92,184,92,0.25);
    outline: none;
  }

  .task-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: var(--color-text-primary);
  }

  .task-meta {
    font-size: 0.95rem;
    color: var(--color-text-secondary);
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
    display: inline-block;
    margin-bottom: 2rem;
    font-weight: 600;
    font-size: 1.1rem;
    color: var(--color-primary);
    cursor: pointer;
    text-decoration: none;
    border-radius: var(--border-radius);
    padding: 0.5rem 1rem;
    box-shadow: 0 4px 8px rgba(92,184,92,0.15);
    transition: background-color 0.3s ease, color 0.3s ease;
  }
  .back-btn:hover,
  .back-btn:focus {
    background-color: var(--color-primary);
    color: white;
    outline: none;
  }

  .edit-btn {
    display: inline-block;
    margin-top: 1rem;
    font-weight: 600;
    font-size: 1rem;
    color: var(--color-primary);
    cursor: pointer;
    text-decoration: none;
    border-radius: var(--border-radius);
    padding: 0.5rem 1rem;
    box-shadow: 0 2px 4px rgba(92,184,92,0.15);
    transition: background-color 0.3s ease, color 0.3s ease;
  }
  .edit-btn:hover,
  .edit-btn:focus {
    background-color: var(--color-primary);
    color: white;
    outline: none;
  }

  @media (max-width: 600px) {
    main.main {
      padding: 0 1rem;
      margin-top: 1rem;
      margin-bottom: 2rem;
    }
    .tasks-grid {
      grid-template-columns: 1fr;
      gap: 1.5rem;
    }
  }
</style>
</head>
<body>
<nav class="topnav" role="navigation" aria-label="Main Navigation">
  <div class="menu" role="menubar" aria-label="Navigation menu">
    <a href="user_dashboard.php" role="menuitem" tabindex="0"> Kembali ke Dashboard</a>
    <a href="add_task.php" role="menuitem" tabindex="0">Tambah Tugas</a>
    <a href="settings.php" role="menuitem" tabindex="0">Settings</a>
  </div>

  <div class="profile-dropdown" id="profileDropdown" tabindex="0" aria-haspopup="true" aria-expanded="false" aria-label="User  profile menu">
    <div class="profile-dropdown-inner" id="profileToggle" tabindex="0">
      <img src="<?php echo $profile_pic_path; ?>" alt="Foto Profil" id="profileImage" />
      <span><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <div class="dropdown-menu" id="dropdownMenu" role="menu" aria-label="Profile and logout options">
      <a href="profile.php" role="menuitem" tabindex="-1">Profile</a>
      <a href="../auth/logout.php" role="menuitem" tabindex="-1">Logout</a>
    </div>
  </div>
</nav>

<main class="main" role="main" aria-labelledby="pageTitle">
  <header class="main-header">
    <h1 id="pageTitle">Daftar Tugas Lengkap</h1>
  </header>

  <?php if (empty($tasks)): ?>
    <p class="no-tasks" role="status">Belum ada tugas yang dibuat.</p>
  <?php else: ?>
    <section class="tasks-grid" aria-live="polite" aria-label="Daftar tugas">
      <?php foreach ($tasks as $task): ?>
        <article class="task-card" tabindex="0" aria-label="Tugas <?= htmlspecialchars($task['task_name']); ?>" role="button" onclick="location.href='lihat_tugas_detail.php?id=<?= $task['id']; ?>'" onkeypress="if(event.key==='Enter') location.href='lihat_tugas_detail.php?id=<?= $task['id']; ?>'">
          <h2 class="task-title"><?= htmlspecialchars($task['task_name'], ENT_QUOTES); ?></h2>

          <p class="task-meta"><span class="meta-label">Status:</span> <?= htmlspecialchars($task['status'], ENT_QUOTES); ?></p>
          <p class="task-meta"><span class="meta-label">Prioritas:</span> <?= htmlspecialchars($task['priority'], ENT_QUOTES); ?></p>

          <?php if (!empty($task['due_date'])): ?>
            <p class="task-meta"><span class="meta-label">Jatuh Tempo:</span> <?= htmlspecialchars($task['due_date'], ENT_QUOTES); ?></p>
          <?php endif; ?>

          <?php if (!empty($task['completion_date'])): ?>
            <p class="task-meta"><span class="meta-label">Tanggal Selesai:</span> <?= htmlspecialchars($task['completion_date'], ENT_QUOTES); ?></p>
          <?php endif; ?>

          <?php if (!empty($task['description'])): ?>
            <p class="task-meta"><span class="meta-label">Deskripsi:</span> <?= nl2br(htmlspecialchars($task['description'], ENT_QUOTES)); ?></p>
          <?php endif; ?>

          <?php if (!empty($task['tags'])): ?>
            <p class="task-meta"><span class="meta-label">Tags:</span> <?= htmlspecialchars($task['tags'], ENT_QUOTES); ?></p>
          <?php endif; ?>

          <?php if (!empty($task['comments'])): ?>
            <p class="task-meta"><span class="meta-label">Catatan:</span> <?= nl2br(htmlspecialchars($task['comments'], ENT_QUOTES)); ?></p>
          <?php endif; ?>

          <?php if (!empty($task['recurrence'])): ?>
            <p class="task-meta"><span class="meta-label">Rekurensi:</span> <?= htmlspecialchars($task['recurrence'], ENT_QUOTES); ?></p>
          <?php endif; ?>

          <?php if (!empty($task['parent_task_id']) && isset($task_map[$task['parent_task_id']])): ?>
            <p class="task-meta"><span class="meta-label">Subtugas Dari:</span> <?= htmlspecialchars($task_map[$task['parent_task_id']], ENT_QUOTES); ?></p>
          <?php endif; ?>

          <?php if (!empty($task['assigned_user_id']) && isset($user_map[$task['assigned_user_id']])): ?>
            <p class="task-meta"><span class="meta-label">Ditugaskan ke:</span> <?= htmlspecialchars($user_map[$task['assigned_user_id']], ENT_QUOTES); ?></p>
          <?php endif; ?>

          <!-- Edit Button -->
          <a href="edit_tugas.php?id=<?= $task['id']; ?>" class="edit-btn" role="button" aria-label="Edit tugas <?= htmlspecialchars($task['task_name']); ?>">
            Edit Tugas
          </a>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</main>

<script>
  (function(){
    const profileDropdown = document.getElementById('profileDropdown');
    const dropdownMenu = document.getElementById('dropdownMenu');
    const profileToggle = document.getElementById('profileToggle');

    function closeDropdown() {
      dropdownMenu.classList.remove('show');
      profileDropdown.setAttribute('aria-expanded', 'false');
    }

    function toggleDropdown() {
      const isOpen = dropdownMenu.classList.contains('show');
      if (isOpen) {
        closeDropdown();
      } else {
        dropdownMenu.classList.add('show');
        profileDropdown.setAttribute('aria-expanded', 'true');
      }
    }

    profileToggle.addEventListener('click', e => {
      e.stopPropagation();
      toggleDropdown();
    });

    profileDropdown.addEventListener('keydown', e => {
      if (e.key === 'Escape' && dropdownMenu.classList.contains('show')) {
        closeDropdown();
        profileDropdown.focus();
      }
      if ((e.key === 'Enter' || e.key === ' ') && e.target === profileDropdown) {
        e.preventDefault();
        toggleDropdown();
      }
    });

    document.addEventListener('click', () => {
      if (dropdownMenu.classList.contains('show')) {
        closeDropdown();
      }
    });

    profileDropdown.addEventListener('focusout', e => {
      const relatedTarget = e.relatedTarget;
      if (!profileDropdown.contains(relatedTarget)) {
        closeDropdown();
      }
    });
  })();
</script>
</body>
</html>
