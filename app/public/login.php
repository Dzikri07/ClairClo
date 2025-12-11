<?php

session_start();
require_once __DIR__ . '/connection.php';

// REST OF YOUR LOGIN CODE...

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['user'] ?? '';
    $password = $_POST['pass'] ?? '';

    try {
        // Get database connection
        $pdo = getDB();

        // Find user by username
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['storage_quota'] = $user['storage_quota'];
            $_SESSION['storage_used'] = $user['storage_used'];

            // Update last login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);

            // Redirect admin to dashboard, others to home
            if (!empty($user['is_admin']) && $user['is_admin'] == 1) {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = "Username atau password salah";
        }
    } catch (Exception $e) {
        $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
        error_log("Login error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Clario Cloud Storage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: url('assets/image/loginScreen.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
            overflow: hidden;
        }

        /* Dark overlay for better contrast */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 0;
            pointer-events: none;
        }

        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .login-container {
            /* Glass morphism effect */
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeInDown 0.8s ease-out;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .logo-section img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }

        .logo-text {
            font-size: 28px;
            font-weight: 700;
            color: white;
            letter-spacing: -1px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .logo-text .c {
            color: #00d4ff;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .form-group {
            margin-bottom: 20px;
            animation: fadeIn 1s ease-out both;
        }

        .form-group:nth-child(1) { animation-delay: 0.2s; }
        .form-group:nth-child(2) { animation-delay: 0.3s; }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .form-control {
            background: rgba(240, 245, 250, 0.9);
            border: 2px solid rgba(0, 212, 255, 0.4);
            border-radius: 12px;
            padding: 14px 18px;
            color: #1a1a1a;
            font-size: 15px;
            font-weight: 500;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .form-control::placeholder {
            color: transparent;
        }



        .form-control:focus {
            background: rgba(255, 255, 255, 0.98);
            border-color: #00d4ff;
            box-shadow: 0 0 0 4px rgba(0, 212, 255, 0.25), inset 0 2px 8px rgba(0, 0, 0, 0.05);
            color: #1a1a1a;
            outline: none;
        }

        .form-control:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 1000px rgba(240, 245, 250, 0.9) inset !important;
            -webkit-text-fill-color: #1a1a1a !important;
            caret-color: #00d4ff;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(0, 212, 255, 0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
            animation: slideUp 0.8s ease-out 0.4s both;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #00e6ff 0%, #00aadd 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 212, 255, 0.5);
            color: white;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            background: rgba(220, 53, 69, 0.25);
            border: 1px solid rgba(220, 53, 69, 0.5);
            color: #ff6b9d;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            animation: shake 0.5s ease-in-out;
            margin-bottom: 20px;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .alert i {
            color: #ff6b9d;
        }

        .forgot-password {
            text-align: right;
            margin-top: 12px;
        }

        .forgot-password a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: #00d4ff;
            text-decoration: underline;
        }

        .divider {
            text-align: center;
            margin: 25px 0 20px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 13px;
        }

        .register-link {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            animation: fadeIn 1.2s ease-out 0.5s both;
        }

        .register-link a {
            color: #00d4ff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: #00e6ff;
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .login-container {
                padding: 40px 25px;
            }

            .logo-section {
                gap: 10px;
            }

            .logo-section img {
                width: 40px;
                height: 40px;
            }

            .logo-text {
                font-size: 24px;
            }

            .login-header p {
                font-size: 13px;
            }

            .form-control {
                padding: 12px 14px;
                font-size: 14px;
            }

            .btn-login {
                padding: 12px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <div class="logo-section">
                    <img src="assets/image/clairo.png" alt="Clario Logo">
                    <div class="logo-text"><span class="c">c</span>lario</div>
                </div>
                <p>Akses penyimpanan cloud Anda</p>
            </div>

            <?php if(!empty($error)): ?>
                <div class="alert alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-text" style="background: rgba(0, 212, 255, 0.15); border: 2px solid rgba(0, 212, 255, 0.4); border-right: none; border-radius: 12px 0 0 12px; color: #00d4ff; font-weight: 700;">
                            <i class="fas fa-user"></i>
                        </span>
                        <input name="user" type="text" class="form-control" placeholder="Username atau Email" required style="border-left: none; border-radius: 0 12px 12px 0;">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-text" style="background: rgba(0, 212, 255, 0.15); border: 2px solid rgba(0, 212, 255, 0.4); border-right: none; border-radius: 12px 0 0 12px; color: #00d4ff; font-weight: 700;">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input name="pass" type="password" class="form-control" placeholder="Password" required style="border-left: none; border-radius: 0 12px 12px 0;">
                    </div>
                    
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>LOGIN
                </button>
                <div class="forgot-password">
                        <a href="forgot_password.php">Lupa password?</a>
                    </div>  
            </form>

           
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
