<?php
// header.php - central header partial
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Compute base URL for asset references (root-relative to public folder)
$baseUrl = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if (basename($baseUrl) === 'admin') {
    $baseUrl = dirname($baseUrl);
}
$baseUrl = $baseUrl === '/' ? '' : $baseUrl;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clario Cloud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<!-- page starts -->
<!-- App bar (shared across pages): search, settings, user -->
<div class="app-bar">
    <div class="app-bar-inner d-flex align-items-center">
        <div class="app-bar-left d-flex align-items-center">
            <button id="menu-toggle-top" class="btn btn-link d-md-none me-2" style="color:inherit;"><i class="fa fa-bars"></i></button>
            <img src="<?php echo $baseUrl; ?>/assets/image/clairo.png" alt="logo" style="width:36px; height:auto; margin-right:8px;">
            <div class="app-title d-none d-md-block">Clario</div>
        </div>

        <div class="app-bar-center flex-grow-1 d-flex justify-content-center">
            <div class="search-bar" style="max-width:640px; width:100%;">
                <input id="global-search-input" type="text" class="form-control rounded-pill" placeholder="Telusuri file...">
                <button id="global-list-toggle" class="list-toggle btn" title="Ganti tampilan" aria-label="toggle view"><span class="list-icon"><i class="fa fa-th-large"></i></span></button>
            </div>
        </div>

        <div class="app-bar-right d-flex align-items-center">
            <button class="btn btn-link me-2 gear-icon" title="Pengaturan"><i class="fa fa-cog"></i></button>
            <button class="btn btn-link p-0 user-icon" data-bs-toggle="modal" data-bs-target="#profileModal" title="Akun"><i class="fa fa-user"></i></button>
        </div>
    </div>
</div>
