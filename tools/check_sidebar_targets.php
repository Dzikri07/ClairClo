<?php
$root = 'c:/xampp/htdocs/ClairoCloud-fdb78ea278ebf602e9626d6a2e26712ef4955628/app/public';
$files = [
    $root . '/index.php',
    $root . '/semuafile.php',
    $root . '/favorit.php',
    $root . '/sampah.php',
    $root . '/request_storage.php',
    $root . '/admin/kelola_user.php',
    $root . '/admin/permintaan_storage.php'
];
foreach ($files as $f) {
    echo basename($f) . ": " . (file_exists($f) ? 'exists' : 'missing') . PHP_EOL;
}
