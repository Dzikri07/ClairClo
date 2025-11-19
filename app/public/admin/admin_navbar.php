<?php
// admin_navbar.php - Top navigation bar for admin pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Compute a base URL that points to the public folder
$baseUrl = '/app/public';
if (!function_exists('fetchOne')) {
    require_once __DIR__ . '/../connection.php';
}

$userId = $_SESSION['user_id'] ?? null;
$user = null;
if ($userId) {
    $user = fetchOne('SELECT username, email, full_name, avatar FROM users WHERE id = ?', [$userId]);
}

$current = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Clario</title>
    <link href="https://fonts.googleapis.com/css2?family=Krona+One&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
    /* Admin Navbar Styles */
    .admin-navbar {
        background: linear-gradient(135deg, #007364 0%, #005544 100%);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        padding: 0;
        height: 70px;
        display: flex;
        align-items: center;
        color: white;
    }

    .admin-navbar .navbar-brand {
        margin: 0;
        padding: 0 20px;
        font-family: 'Krona One', sans-serif;
        font-size: 24px;
        font-weight: bold;
        color: white;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .admin-navbar .navbar-brand img {
        width: 50px;
        height: 50px;
    }

    .admin-navbar .nav {
        flex: 1;
        display: flex;
        margin-left: 40px;
        gap: 0;
    }

    .admin-navbar .nav-link {
        color: rgba(255, 255, 255, 0.8);
        padding: 25px 20px;
        border-bottom: 4px solid transparent;
        transition: all 0.3s ease;
        font-size: 14px;
        font-weight: 500;
    }

    .admin-navbar .nav-link:hover {
        color: white;
        background-color: rgba(255, 255, 255, 0.1);
        border-bottom-color: #ffc107;
    }

    .admin-navbar .nav-link.active {
        color: white;
        border-bottom-color: #ffc107;
        background-color: rgba(0, 0, 0, 0.2);
    }

    .admin-navbar .nav-link i {
        margin-right: 8px;
    }

    .admin-navbar .navbar-end {
        margin-left: auto;
        padding: 0 20px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .admin-navbar .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
        padding-left: 15px;
        border-left: 1px solid rgba(255, 255, 255, 0.2);
    }

    .admin-navbar .user-info .username {
        font-size: 14px;
        color: white;
    }

    .admin-navbar .btn-logout {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 6px 12px;
        border-radius: 4px;
        transition: all 0.3s ease;
        font-size: 12px;
    }

    .admin-navbar .btn-logout:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: white;
    }

    .admin-navbar .badge-admin {
        background: #ffc107;
        color: #333;
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: 600;
    }

    /* Admin Container */
    .admin-container {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .admin-content {
        flex: 1;
        background-color: #f9f9f9;
        padding: 30px;
    }

    @media (max-width: 768px) {
        .admin-navbar {
            height: auto;
            flex-wrap: wrap;
            padding: 10px 0;
        }

        .admin-navbar .navbar-brand {
            width: 100%;
            font-size: 18px;
            padding: 10px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-navbar .nav {
            width: 100%;
            margin-left: 0;
            flex-wrap: wrap;
            padding: 0 20px;
        }

        .admin-navbar .nav-link {
            padding: 10px 15px;
            font-size: 13px;
        }

        .admin-navbar .navbar-end {
            width: 100%;
            margin-left: 0;
            padding: 10px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-content {
            padding: 15px;
        }
    }
    </style>
</head>
<body>
<div class="admin-container">
    <!-- Admin Navbar -->
    <nav class="admin-navbar">
        <div class="navbar-brand">
            <img src="../assets/image/clairo.png" alt="Clario Logo">
            <span>Clario <span class="badge-admin">ADMIN</span></span>
        </div>

        <ul class="nav">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo ($current === 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fa fa-chart-line"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="management_user.php" class="nav-link <?php echo ($current === 'management_user.php') ? 'active' : ''; ?>">
                    <i class="fa fa-users"></i> Manajemen User
                </a>
            </li>
            <li class="nav-item">
                <a href="permintaan_storage.php" class="nav-link <?php echo ($current === 'permintaan_storage.php') ? 'active' : ''; ?>">
                    <i class="fa fa-database"></i> Permintaan Storage
                </a>
            </li>
        </ul>

        <div class="navbar-end">
            <div class="user-info">
                <span class="username">
                    <?php echo htmlspecialchars($user ? ($user['full_name'] ?: $user['username']) : 'Admin'); ?>
                </span>
            </div>
            <a href="../logout.php" class="btn-logout" title="Logout">
                <i class="fa fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <!-- Admin Content Area (to be filled by including pages) -->
    <div class="admin-content">
