<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Pengaturan Aplikasi - DoTask</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
  :root {
    --color-bg: #ffffff;
    --color-text-primary: #1f2937;
    --color-text-secondary: #6b7280;
    --color-primary: #2563eb;
    --color-primary-dark: #1e40af;
    --color-success: #10b981;
    --color-error: #ef4444;
    --color-shadow-light: rgba(0,0,0,0.05);
    --border-radius: 0.75rem;
    --max-width: 1200px;
    --font-family: 'Poppins', sans-serif;
    --gap-vertical: 4rem;
  }

  /* Dark mode variables */
  .dark-mode {
    --color-bg: #121212;
    --color-text-primary: #e0e0e0;
    --color-text-secondary: #a3a3a3;
    --color-shadow-light: rgba(0,0,0,0.75);
    background-color: var(--color-bg);
    color: var(--color-text-primary);
  }
  .dark-mode section.settings-section {
    background-color: #1e1e1e;
    box-shadow: 0 6px 12px rgba(0,0,0,0.75);
  }
  .dark-mode select {
    background-color: #2c2c2c;
    color: var(--color-text-primary);
    border: 1.5px solid #444;
  }
  .dark-mode select:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 8px var(--color-primary);
  }

  html, body {
    margin: 0; padding: 0;
    font-family: var(--font-family);
    background: var(--color-bg);
    color: var(--color-text-primary);
    font-size: 18px;
    line-height: 1.6;
    min-height: 100vh;
    transition: background-color 0.4s ease, color 0.4s ease;
  }
  main {
    max-width: var(--max-width);
    margin: 0 auto;
    padding: var(--gap-vertical) 1.5rem 6rem;
    box-sizing: border-box;
    transition: background-color 0.4s ease, color 0.4s ease;
  }
  header.page-header {
    margin-bottom: 3rem;
  }
  header.page-header h1 {
    font-weight: 800;
    font-size: 48px;
    margin: 0;
    color: var(--color-text-primary);
    transition: color 0.4s ease;
  }
  section.settings-section {
    background: #f9fafb;
    border-radius: var(--border-radius);
    box-shadow: 0 6px 12px var(--color-shadow-light);
    padding: 2.5rem 3rem;
    margin-bottom: 3rem;
    transition: background-color 0.4s ease, box-shadow 0.4s ease;
  }
  section.settings-section h2 {
    font-weight: 600;
    font-size: 1.8rem;
    margin-top: 0;
    margin-bottom: 1.5rem;
    color: var(--color-text-primary);
    transition: color 0.4s ease;
  }
  .setting-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.4rem;
  }
  .setting-item:last-child {
    margin-bottom: 0;
  }
  .setting-label {
    font-weight: 500;
    color: var(--color-text-secondary);
    font-size: 1.1rem;
    flex-grow: 1;
    user-select: none;
    transition: color 0.4s ease;
  }
  /* Toggle Switch Styles */
  .switch {
    position: relative;
    display: inline-block;
    width: 52px;
    height: 28px;
  }
  .switch input {
    opacity: 0;
    width: 0;
    height: 0;
  }
  .slider {
    position: absolute;
    cursor: pointer;
    background-color: #ccc;
    border-radius: 34px;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    transition: background-color 0.3s ease;
  }
  .slider::before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
  }
  input:checked + .slider {
    background-color: var(--color-primary);
  }
  input:checked + .slider::before {
    transform: translateX(24px);
  }
  input:focus + .slider {
    box-shadow: 0 0 2px var(--color-primary);
  }

  /* Select Dropdown */
  select {
    font-size: 1rem;
    padding: 8px 12px;
    border-radius: var(--border-radius);
    border: 1.5px solid #d1d5db;
    font-family: var(--font-family);
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
    background-color: white;
    color: var(--color-text-primary);
  }
  select:hover {
    border-color: var(--color-primary);
  }
  select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 8px var(--color-primary);
  }

  /* Reset Button */
  .btn-reset {
    display: inline-block;
    background-color: transparent;
    border: 2px solid var(--color-primary);
    color: var(--color-primary);
    padding: 0.6rem 1.6rem;
    border-radius: var(--border-radius);
    font-weight: 600;
    font-size: 1.1rem;
    cursor: pointer;
    transition: background-color 0.3s ease, color 0.3s ease;
    user-select: none;
  }
  .btn-reset:hover,
  .btn-reset:focus {
    background-color: var(--color-primary);
    color: white;
    outline: none;
  }

  /* Description text */
  .desc-text {
    font-size: 1rem;
    color: var(--color-text-secondary);
    margin-top: 0.3rem;
    user-select: none;
  }

  /* Back button */
  .btn-back {
    display: inline-block;
    margin-bottom: 2rem;
    font-weight: 600;
    font-size: 1.1rem;
    color: var(--color-primary);
    cursor: pointer;
    user-select: none;
    border: none;
    background: none;
    padding: 0;
    text-decoration: underline;
    transition: color 0.3s ease;
  }
  .btn-back:hover,
  .btn-back:focus {
    color: var(--color-primary-dark);
    outline: none;
  }

  @media (max-width: 600px) {
    .setting-item {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.4rem;
    }
  }
</style>
</head>
<body>
<main role="main" aria-labelledby="pageTitle">
  <button class="btn-back" id="btnBack" aria-label="Kembali ke dashboard utama">&larr; Kembali ke Dashboard</button>
  
  <header class="page-header">
    <h1 id="pageTitle">Pengaturan Aplikasi</h1>
  </header>

  <section class="settings-section" aria-label="Tema aplikasi">
    <h2>Tema</h2>
    <div class="setting-item">
      <label for="darkModeToggle" class="setting-label">Mode Gelap</label>
      <label class="switch" aria-label="Toggle mode gelap">
        <input type="checkbox" id="darkModeToggle" aria-checked="false" />
        <span class="slider"></span>
      </label>
    </div>
    <p class="desc-text">Aktifkan mode gelap untuk pengalaman yang nyaman di lingkungan dengan cahaya rendah.</p>
  </section>

  <section class="settings-section" aria-label="Notifikasi">
    <h2>Notifikasi</h2>
    <div class="setting-item">
      <label for="notifyTasksToggle" class="setting-label">Notifikasi Tugas</label>
      <label class="switch" aria-label="Toggle notifikasi tugas">
        <input type="checkbox" id="notifyTasksToggle" aria-checked="false" />
        <span class="slider"></span>
      </label>
    </div>
    <p class="desc-text">Terima pemberitahuan saat ada pembaruan tugas penting.</p>
  </section>

  <section class="settings-section" aria-label="Bahasa aplikasi">
    <h2>Bahasa</h2>
    <div class="setting-item">
      <label for="languageSelect" class="setting-label">Pilih Bahasa</label>
      <select id="languageSelect" aria-describedby="languageDesc" aria-label="Pilih bahasa aplikasi">
        <option value="id">Bahasa Indonesia</option>
        <option value="en">English</option>
      </select>
    </div>
    <p id="languageDesc" class="desc-text">Pilih bahasa yang digunakan dalam aplikasi.</p>
  </section>

  <section class="settings-section" aria-label="Pengaturan Umum">
    <h2>Pengaturan Umum</h2>
    <button type="button" class="btn-reset" id="resetSettingsBtn" aria-label="Reset pengaturan ke default">Reset ke Pengaturan Default</button>
    <p class="desc-text">Reset semua pengaturan aplikasi ke nilai bawaan pabrik.</p>
  </section>
</main>

<script>
  // Define keys for localStorage
  const STORAGE_KEYS = {
    darkMode: 'DoTask_darkMode',
    notifyTasks: 'DoTask_notifyTasks',
    language: 'DoTask_language'
  };

  const darkModeToggle = document.getElementById('darkModeToggle');
  const notifyTasksToggle = document.getElementById('notifyTasksToggle');
  const languageSelect = document.getElementById('languageSelect');
  const resetBtn = document.getElementById('resetSettingsBtn');
  const btnBack = document.getElementById('btnBack');

  // Apply settings from localStorage
  function applySettings() {
    const darkModeEnabled = localStorage.getItem(STORAGE_KEYS.darkMode) === 'true';
    const notifyTasksEnabled = localStorage.getItem(STORAGE_KEYS.notifyTasks) === 'true';
    const language = localStorage.getItem(STORAGE_KEYS.language) || 'id';

    darkModeToggle.checked = darkModeEnabled;
    notifyTasksToggle.checked = notifyTasksEnabled;
    languageSelect.value = language;

    updateDarkMode(darkModeEnabled);
  }

  // Update dark mode UI and document class
  function updateDarkMode(enabled) {
    if (enabled) {
      document.documentElement.classList.add('dark-mode');
      darkModeToggle.setAttribute('aria-checked', 'true');
    } else {
      document.documentElement.classList.remove('dark-mode');
      darkModeToggle.setAttribute('aria-checked', 'false');
    }
  }

  // Event listeners to update settings and storage
  darkModeToggle.addEventListener('change', () => {
    const isChecked = darkModeToggle.checked;
    localStorage.setItem(STORAGE_KEYS.darkMode, isChecked);
    updateDarkMode(isChecked);
  });

  notifyTasksToggle.addEventListener('change', () => {
    const isChecked = notifyTasksToggle.checked;
    localStorage.setItem(STORAGE_KEYS.notifyTasks, isChecked);
    alert('Notifikasi tugas ' + (isChecked ? 'diaktifkan' : 'dinonaktifkan') + ' (demo fitur)');
  });

  languageSelect.addEventListener('change', () => {
    const selectedLang = languageSelect.value;
    localStorage.setItem(STORAGE_KEYS.language, selectedLang);
    alert('Bahasa diubah ke: ' + languageSelect.options[languageSelect.selectedIndex].text + ' (demo fitur)');
  });

  resetBtn.addEventListener('click', () => {
    // Reset all to default false/ 'id'
    localStorage.setItem(STORAGE_KEYS.darkMode, false);
    localStorage.setItem(STORAGE_KEYS.notifyTasks, false);
    localStorage.setItem(STORAGE_KEYS.language, 'id');

    applySettings();
    alert('Pengaturan telah direset ke default (demo fitur)');
  });

  btnBack.addEventListener('click', () => {
    window.location.href = 'user_dashboard.php';
  });

  // Initialize settings on page load
  applySettings();

</script>
</body>
</html>

