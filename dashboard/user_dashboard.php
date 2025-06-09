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

// Fetch profile picture filename for sidebar display
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
        --sidebar-width: 250px;
        --sidebar-width-collapsed: 64px;
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
        display: flex;
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

    /* Sidebar */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: var(--sidebar-width);
        background-color: var(--color-main-green);
        color: white;
        padding-top: 1.5rem;
        display: flex;
        flex-direction: column;
        transition: width var(--transition-speed) ease;
        box-shadow: 2px 0 12px var(--color-shadow);
        user-select: none;
        z-index: 1000;
        align-items: stretch;
    }
    .sidebar.collapsed {
        width: var(--sidebar-width-collapsed);
    }
    .sidebar a.menu-item.profile {
        padding: 1rem 1.5rem;
        font-size: 1.05rem;
        display: flex;
        align-items: center;
        cursor: pointer;
        user-select: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        transition: background-color var(--transition-speed), color var(--transition-speed);
        border: none;
        background: transparent;
        color: white;
        text-align: left;
        font-weight: 700;
        font-size: 1.2rem;
        border-bottom: 1px solid rgba(255 255 255 / 0.25);
        margin-bottom: 1rem;
        outline-offset: 2px;
    }
    .sidebar a.menu-item.profile:hover,
    .sidebar a.menu-item.profile:focus {
        background-color: var(--color-main-green-dark);
        color: white;
        outline: none;
    }
    .sidebar a.menu-item.profile img {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 10px;
        flex-shrink: 0;
    }
    .sidebar.collapsed a.menu-item.profile span {
        display: none;
    }
    .sidebar.collapsed a.menu-item.profile img {
        margin: 0 auto;
    }
    .menu {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    /* Toggle button */
    .toggle-btn {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0.75rem 1.5rem;
        user-select: none;
        transition: background-color var(--transition-speed);
        align-self: flex-start;
    }
    .toggle-btn:hover, .toggle-btn:focus {
        background-color: var(--color-main-green-dark);
        outline: none;
    }
    .sidebar.collapsed .toggle-btn {
        padding: 0.75rem 0;
        text-align: center;
        align-self: center;
    }

    /* Settings and Logout below toggle */
    .bottom-menu {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    .bottom-menu a.menu-item {
        padding: 1rem 1.5rem;
        font-size: 1.05rem;
        display: flex;
        align-items: center;
        cursor: pointer;
        user-select: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        transition: background-color var(--transition-speed), color var(--transition-speed);
        border: none;
        background: transparent;
        color: white;
        text-align: left;
    }
    .bottom-menu a.menu-item:hover,
    .bottom-menu a.menu-item:focus {
        background-color: var(--color-main-green-dark);
        color: white;
        outline: none;
    }
    .bottom-menu a.menu-item i {
        margin-right: 1rem;
        min-width: 20px;
        text-align: center;
        font-style: normal;
        font-weight: bold;
        user-select: none;
    }
    .sidebar.collapsed .bottom-menu a.menu-item span {
        display: none;
    }
    .sidebar.collapsed .bottom-menu a.menu-item i {
        margin: 0 auto;
    }

    /* Main content */
    .main {
        margin-left: var(--sidebar-width);
        padding: 3rem 3rem 4rem;
        flex-grow: 1;
        min-height: 100vh;
        transition: margin-left var(--transition-speed) ease;
        display: flex;
        flex-direction: column;
        max-width: 1200px;
        width: 100%;
    }
    .sidebar.collapsed ~ .main {
        margin-left: var(--sidebar-width-collapsed);
    }
    .main-header {
        margin-bottom: 2rem;
    }
    .main-header h1 {
        font-weight: 800;
        font-size: 3rem;
        margin: 0;
        color: var(--color-text-primary);
    }

    /* Add Task button as link */
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
</style>
</head>
<body>
    <aside class="sidebar" id="sidebar" aria-label="Sidebar menu">
        <a href="profile.php" class="menu-item profile" tabindex="0" aria-current="page" aria-label="Profile Menu">
            <img src="<?php echo $profile_pic_path; ?>" alt="Foto Profil" />
            <span><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <button class="toggle-btn" id="toggle-btn" aria-label="Toggle sidebar" title="Toggle sidebar">&#9776;</button>
        <div class="bottom-menu">
            <a href="settings.php" class="menu-item" role="menuitem" tabindex="0"><i>⚙️</i><span>Settings</span></a>
            <a href="../auth/logout.php" class="menu-item" role="menuitem" tabindex="0"><i>🚪</i><span>Logout</span></a>
        </div>
    </aside>

    <main class="main" tabindex="main">
        <header class="main-header" aria-label="Website name">
            <h1>DoTask</h1>
        </header>

        <a href="add_task.php" class="add-task-link" aria-label="Tambah Tugas Baru" role="button">
            + Tambah Tugas
        </a>

        <?php if (count($tasks) > 0): ?>
            <section class="tasks" aria-live="polite" aria-label="Daftar tugas pengguna">
                <?php foreach ($tasks as $task): ?>
                    <article class="task-card" tabindex="0">
                        <h3 class="task-title"><?php echo htmlspecialchars($task['task_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="task-status">Status: <?php echo htmlspecialchars($task['status'], ENT_QUOTES, 'UTF-8'); ?></p>
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
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggle-btn');

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
    });
</script>
</body>
</html>

