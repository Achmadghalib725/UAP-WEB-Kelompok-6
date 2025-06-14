<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/db.php';

$user_id = $_SESSION['user_id'];

// Fetch tasks for logged-in user with needed fields
$stmt = $conn->prepare("SELECT id, task_name, description, due_date, priority, comments FROM tasks WHERE user_id = ? ORDER BY due_date ASC, id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);

// Fetch profile pic filename for navbar display
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
<title>Dashboard - DoTask</title>
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
    --gap: 2rem;
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

  /* Top navigation */
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

  /* Main menu links */
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

  /* Main content */
  main.main {
    max-width: var(--max-width);
    margin: 2rem auto 3rem;
    padding: 0 1.5rem;
    display: flex;
    flex-direction: column;
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

  /* Button to add task */
  .add-task-link {
    background-color: var(--color-primary);
    color: white;
    font-weight: 700;
    font-size: 1.3rem;
    padding: 1rem 2.5rem;
    border-radius: var(--border-radius);
    width: max-content;
    align-self: flex-start;
    user-select: none;
    transition: background-color 0.3s ease;
    text-align: center;
    margin-bottom: 2rem;
    text-decoration: none;
  }
  .add-task-link:hover,
  .add-task-link:focus {
    background-color: var(--color-primary-dark);
    outline: none;
  }

  /* Tasks grid */
  .tasks {
    display: grid;
    grid-template-columns: repeat(auto-fill,minmax(320px,1fr));
    gap: var(--gap);
  }

  /* Task card */
  .task-card {
    background: var(--color-card-bg);
    border-radius: var(--border-radius);
    box-shadow: 0 6px 15px var(--color-shadow);
    padding: 1.5rem 2rem;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    transition: box-shadow 0.3s ease;
    outline-offset: 2px;
  }
  .task-card:hover,
  .task-card:focus-within {
    box-shadow: 0 10px 30px rgba(92,184,92,0.3);
    outline: none;
  }

  /* Title */
  .task-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: var(--color-text-primary);
  }

  /* Deadline */
  .task-deadline {
    font-size: 1rem;
    color: var(--color-text-secondary);
    margin: 0;
  }

  /* Description - limit lines and fade overflow */
  .task-description {
    color: var(--color-text-secondary);
    font-size: 1rem;
    margin: 0;
    line-height: 1.3;
    flex-grow: 1;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
  }

  /* Priority and Notes */
  .task-meta {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--color-primary);
  }
  .task-notes {
    font-size: 0.9rem;
    color: var(--color-text-secondary);
    font-style: italic;
    white-space: pre-wrap;
  }

  /* No tasks message */
  .no-tasks {
    font-size: 1.25rem;
    color: var(--color-text-secondary);
    margin-top: 4rem;
    text-align: center;
    user-select: none;
  }

  @media (max-width: 600px) {
    main.main {
      padding: 0 1rem;
      margin-top: 1rem;
      margin-bottom: 2rem;
    }
    .tasks {
      grid-template-columns: 1fr;
      gap: 1.5rem;
    }
  }
</style>
</head>
<body>
<nav class="topnav" role="navigation" aria-label="Main Navigation">
  <div class="menu" role="menubar" aria-label="Navigation menu">
    <a href="settings.php" role="menuitem" tabindex="0">Settings</a>
  </div>

  <div class="profile-dropdown" id="profileDropdown" tabindex="0" aria-haspopup="true" aria-expanded="false" aria-label="User profile menu">
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
    <h1 id="pageTitle">DoTask</h1>
  </header>

  <a href="add_task.php" class="add-task-link" aria-label="Tambah Tugas Baru" role="button">+ Tambah Tugas</a>

  <?php if (count($tasks) > 0): ?>
    <section class="tasks" aria-live="polite" aria-label="Daftar tugas pengguna">
      <?php foreach ($tasks as $task): ?>
        <article class="task-card" tabindex="0" aria-label="Tugas <?= htmlspecialchars($task['task_name']); ?>" role="button" onclick="location.href='lihat_tugas_detail.php?id=<?= $task['id']; ?>'" onkeypress="if(event.key==='Enter') location.href='lihat_tugas_detail.php?id=<?= $task['id']; ?>'">
          <h2 class="task-title"><?= htmlspecialchars($task['task_name'], ENT_QUOTES); ?></h2>
          <?php if (!empty($task['due_date'])): ?>
            <p class="task-deadline" aria-label="Tanggal jatuh tempo">Deadline: <?= htmlspecialchars($task['due_date'], ENT_QUOTES); ?></p>
          <?php endif; ?>
          <?php if (!empty($task['description'])): ?>
            <p class="task-description"><?= nl2br(htmlspecialchars($task['description'], ENT_QUOTES)); ?></p>
          <?php endif; ?>
          <?php if (!empty($task['priority'])): ?>
            <p class="task-meta">Prioritas: <?= htmlspecialchars($task['priority'], ENT_QUOTES); ?></p>
          <?php endif; ?>
          <?php if (!empty($task['comments'])): ?>
            <p class="task-notes" aria-label="Catatan tugas"><?= nl2br(htmlspecialchars($task['comments'], ENT_QUOTES)); ?></p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </section>
  <?php else: ?>
    <p class="no-tasks" role="status">Belum ada tugas. Silakan tambahkan tugas baru!</p>
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
