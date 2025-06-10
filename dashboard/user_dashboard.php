<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/db.php';

$user_id = $_SESSION['user_id'];

// Fetch tasks for the logged-in user
$stmt = $conn->prepare("SELECT id, task_name, status FROM tasks WHERE user_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);

// Fetch profile picture filename for navbar display
$stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($profile_pic_filename);
$stmt->fetch();
$stmt->close();

$upload_dir = __DIR__ . '/../uploads/';
$default_profile_pic = '../assets/default-profile.png';
$profile_pic_path = ($profile_pic_filename && file_exists($upload_dir . $profile_pic_filename)) ? '../uploads/' . htmlspecialchars($profile_pic_filename, ENT_QUOTES, 'UTF-8') : $default_profile_pic;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Dashboard - DoTask</title>
<style>
    :root {
        --color-main-green: #5cb85c;
        --color-main-green-dark: #4cae4c;
        --color-bg: #ffffff;
        --color-text-primary: #111827;
        --color-text-secondary: #6b7280;
        --color-card-bg: #f9fafb;
        --color-shadow: rgba(0, 0, 0, 0.05);
        --border-radius: 0.75rem;
        --nav-height: 64px;
        --max-width: 1200px;
        --transition-speed: 0.3s;
        --font-heading: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    /* Reset */
    * {
        box-sizing: border-box;
    }
    body, html {
        margin: 0;
        height: 100%;
        font-family: var(--font-heading);
        background: var(--color-bg);
        color: var(--color-text-primary);
        font-size: 18px;
        line-height: 1.5;
        overflow-x: hidden;
    }
    a {
        color: inherit;
        text-decoration: none;
    }
    a:hover, a:focus {
        color: var(--color-main-green-dark);
        outline: none;
        text-decoration: underline;
    }

    /* Top Navigation Bar */
    .topnav {
        position: sticky;
        top: 0;
        left: 0;
        width: 100%;
        height: var(--nav-height);
        background-color: var(--color-main-green);
        color: white;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        padding: 0 1rem 0 1.5rem;
        box-shadow: 0 2px 10px var(--color-shadow);
        user-select: none;
        z-index: 1000;
        gap: 2rem;
    }
    /* Profile dropdown container */
    .profile-dropdown {
        position: relative;
        display: flex;
        align-items: center;
        cursor: pointer;
        white-space: nowrap;
        user-select: none;
    }
    .profile-dropdown-inner {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: white;
        font-weight: 700;
        font-size: 1.1rem;
        line-height: 1;
        user-select: text;
    }
    .profile-dropdown-inner img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid white;
        display: block;
    }
    /* Dropdown menu */
    .dropdown-menu {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        background: white;
        color: var(--color-text-primary);
        border-radius: var(--border-radius);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        min-width: 140px;
        font-size: 1rem;
        font-weight: 600;
        display: none;
        flex-direction: column;
        padding: 0.5rem 0;
        z-index: 1100;
    }
    .dropdown-menu.show {
        display: flex;
    }
    .dropdown-menu a {
        padding: 0.6rem 1.25rem;
        color: var(--color-text-primary);
        text-decoration: none;
        transition: background-color 0.2s ease;
    }
    .dropdown-menu a:hover,
    .dropdown-menu a:focus {
        background-color: var(--color-main-green);
        color: white;
        outline: none;
    }

    .menu {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-left: auto;
        font-weight: 600;
        font-size: 1.1rem;
    }
    .menu a {
        padding: 0.5rem 0.75rem;
        border-radius: var(--border-radius);
        transition: background-color var(--transition-speed), color var(--transition-speed);
    }
    .menu a:hover,
    .menu a:focus {
        background-color: rgba(255 255 255 / 0.2);
        outline: none;
        color: white;
        text-decoration: none;
    }

    /* Main content */
    main.main {
        margin: 1.5rem auto 3rem;
        max-width: var(--max-width);
        padding: 0 1rem;
        min-height: calc(100vh - var(--nav-height) - 4rem);
        display: flex;
        flex-direction: column;
    }
    main.main header.main-header {
        margin-bottom: 2rem;
    }
    main.main header.main-header h1 {
        font-weight: 800;
        font-size: 3rem;
        margin: 0;
        color: var(--color-text-primary);
    }

    /* Add Task button */
    .add-task-link {
        background-color: var(--color-main-green);
        color: white;
        border: none;
        padding: 1rem 2.5rem;
        font-size: 1.3rem;
        font-weight: 700;
        border-radius: var(--border-radius);
        cursor: pointer;
        user-select: none;
        transition: background-color 0.3s ease;
        text-decoration: none;
        display: inline-block;
        margin-bottom: 2rem;
        max-width: 280px;
        text-align: center;
        align-self: flex-start;
    }
    .add-task-link:hover,
    .add-task-link:focus {
        background-color: var(--color-main-green-dark);
        outline: none;
    }

    /* Tasks grid */
    .tasks {
        display: grid;
        grid-template-columns: repeat(auto-fill,minmax(280px,1fr));
        gap: 1.5rem;
        flex-grow: 1;
    }
    .task-card {
        background: var(--color-card-bg);
        box-shadow: 0 6px 15px var(--color-shadow);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        transition: box-shadow 0.3s ease;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        cursor: default;
    }
    .task-card:hover, .task-card:focus-within {
        box-shadow: 0 10px 30px rgba(92,184,92,0.25);
        outline: none;
    }
    .task-title {
        font-weight: 700;
        font-size: 1.3rem;
        margin: 0;
        color: var(--color-text-primary);
    }
    .task-status {
        font-size: 1rem;
        color: var(--color-text-secondary);
        margin: 0;
    }

    /* No tasks message */
    .no-tasks {
        font-size: 1.2rem;
        color: var(--color-text-secondary);
        text-align: center;
        padding: 3rem 1rem;
        user-select: none;
        flex-grow: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @media (max-width: 600px) {
        .topnav {
            flex-wrap: wrap;
            height: auto;
            padding: 0.5rem 1rem;
            gap: 0.5rem 1rem;
        }
        .menu {
            width: 100%;
            justify-content: center;
            order: 2;
            margin-left: 0;
            gap: 0.75rem;
        }
        .profile-dropdown {
            order: 1;
            flex-grow: 1;
        }
        .add-task-link {
            width: 100%;
            max-width: none;
            text-align: center;
            padding: 1rem 0;
        }
        main.main {
            margin-top: calc(var(--nav-height) + 1rem);
        }
    }
</style>
</head>
<body>
    <nav class="topnav" role="navigation" aria-label="Main Navigation">
        <div class="profile-dropdown" id="profileDropdown" tabindex="0" aria-haspopup="true" aria-expanded="false" aria-label="User profile menu">
            <div class="profile-dropdown-inner" id="profileToggle">
                <img src="<?php echo $profile_pic_path; ?>" alt="Foto Profil" id="profileImage" />
                <span><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="dropdown-menu" id="dropdownMenu" role="menu" aria-label="Profile and logout options">
                <a href="profile.php" role="menuitem" tabindex="-1">Profile</a>
                <a href="../auth/logout.php" role="menuitem" tabindex="-1">Logout</a>
            </div>
        </div>

        <div class="menu" role="menubar" aria-label="Navigation menu">
            <a href="add_task.php" role="menuitem" tabindex="0">Tambah Tugas</a>
            <a href="settings.php" role="menuitem" tabindex="0">Settings</a>
        </div>
    </nav>

    <main class="main" tabindex="main" role="main" aria-labelledby="pageTitle">
        <header class="main-header" aria-label="Website name">
            <h1 id="pageTitle">DoTask</h1>
        </header>

        <a href="add_task.php" class="add-task-link" aria-label="Tambah Tugas Baru" role="button">
            + Tambah Tugas
        </a>

        <?php if (count($tasks) > 0): ?>
            <section class="tasks" aria-live="polite" aria-label="Daftar tugas pengguna">
                <?php foreach ($tasks as $task): ?>
                    <article class="task-card" tabindex="0" aria-label="Tugas <?= htmlspecialchars($task['task_name']); ?>">
                        <h3 class="task-title"><?= htmlspecialchars($task['task_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="task-status">Status: <?= htmlspecialchars($task['status'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php else: ?>
            <div class="no-tasks" role="status">
                Belum ada tugas. Silakan tambahkan tugas baru!
            </div>
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
            // Close dropdown on Escape
            if (e.key === 'Escape' && dropdownMenu.classList.contains('show')) {
                closeDropdown();
                profileDropdown.focus();
            }
            // Toggle dropdown on Enter or Space key
            if ((e.key === 'Enter' || e.key === ' ') && e.target === profileDropdown) {
                e.preventDefault();
                toggleDropdown();
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', () => {
            if (dropdownMenu.classList.contains('show')) {
                closeDropdown();
            }
        });

        // Close dropdown on focusout (when focus moves outside)
        profileDropdown.addEventListener('focusout', e => {
            // Check if new focused element outside dropdown
            const relatedTarget = e.relatedTarget;
            if (!profileDropdown.contains(relatedTarget)) {
                closeDropdown();
            }
        });
    })();
</script>
</body>
</html>

