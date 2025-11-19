<?php
/**
 * Seed default users for testing
 */

require_once __DIR__ . '/../app/public/connection.php';

try {
    // Create admin user
    $admin_username = 'admin';
    $admin_password = 'admin123';
    $admin_email = 'admin@clairocloud.local';
    $admin_hash = password_hash($admin_password, PASSWORD_BCRYPT);

    // Create regular user
    $user_username = 'user';
    $user_password = 'user123';
    $user_email = 'user@clairocloud.local';
    $user_hash = password_hash($user_password, PASSWORD_BCRYPT);

    // Check if admin exists
    $adminCheck = fetchOne('SELECT id FROM users WHERE username = ?', ['admin']);
    if (!$adminCheck) {
        insert('users', [
            'username' => $admin_username,
            'email' => $admin_email,
            'password' => $admin_hash,
            'is_admin' => 1,
            'storage_quota' => 5368709120
        ]);
        echo "✓ Admin user created\n";
        echo "  Username: " . $admin_username . "\n";
        echo "  Password: " . $admin_password . "\n";
        echo "  Email: " . $admin_email . "\n\n";
    } else {
        echo "ℹ Admin user already exists\n\n";
    }
    
    // Check if regular user exists
    $userCheck = fetchOne('SELECT id FROM users WHERE username = ?', ['user']);
    if (!$userCheck) {
        insert('users', [
            'username' => $user_username,
            'email' => $user_email,
            'password' => $user_hash,
            'is_admin' => 0,
            'storage_quota' => 1073741824
        ]);
        echo "✓ Regular user created\n";
        echo "  Username: " . $user_username . "\n";
        echo "  Password: " . $user_password . "\n";
        echo "  Email: " . $user_email . "\n";
    } else {
        echo "ℹ Regular user already exists\n";
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
