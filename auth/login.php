<?php
require_once '../config/database.php';
require_once '../config/auth.php';

redirectIfLoggedIn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    if (empty($email) || empty($password) || empty($role)) {
        $error = 'Semua field harus diisi!';
    } else {
        if ($role === 'mahasiswa') {
            // Login sebagai mahasiswa
            $stmt = $pdo->prepare("SELECT * FROM mahasiswa WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['role'] = 'mahasiswa';
                $_SESSION['id_mahasiswa'] = $user['id_mahasiswa'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['email'] = $user['email'];
                
                header('Location: ../mahasiswa/dashboard.php');
                exit;
            } else {
                $error = 'Email atau password salah!';
            }
            
        } elseif ($role === 'admin') {
            // Login sebagai admin
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['role'] = 'admin';
                $_SESSION['id_admin'] = $user['id_admin'];
                $_SESSION['nama_admin'] = $user['nama_admin'];
                $_SESSION['email'] = $user['email'];
                
                header('Location: ../admin/dashboard.php');
                exit;
            } else {
                $error = 'Email atau password salah!';
            }
        } else {
            $error = 'Role tidak valid!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIPASTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-500: #667eea;
            --secondary: #f093fb;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: flex;
            flex-direction: row;
        }
        
        .login-left {
            flex: 1;
            background: var(--primary-gradient);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.1); opacity: 0.5; }
        }
        
        .login-left h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }
        
        .login-left p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        
        .login-right {
            flex: 1;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-right h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 30px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-500);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .role-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .role-tab {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            text-align: center;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .role-tab.active {
            background: var(--primary-gradient);
            color: white;
            border-color: var(--primary-500);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .role-tab:hover:not(.active) {
            border-color: var(--primary-500);
            background: #f8f9ff;
        }
        
        .alert {
            border-radius: 12px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .register-link a {
            color: var(--primary-500);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .register-link a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-left, .login-right {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Branding -->
        <div class="login-left">
            <h1>👋 Welcome to SIPASTI</h1>
            <p>Sistem Peminjaman Sarana Terpadu<br>Universitas Sari Mulia</p>
            <div style="position: relative; z-index: 1;">
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-check-circle-fill me-2" style="font-size: 1.2rem;"></i>
                    <span>Peminjaman Online Mudah & Cepat</span>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-check-circle-fill me-2" style="font-size: 1.2rem;"></i>
                    <span>QR Code untuk Validasi Pengembalian</span>
                </div>
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2" style="font-size: 1.2rem;"></i>
                    <span>Tracking Status Real-time</span>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="login-right">
            <h2>🔐 Login</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- Role Tabs -->
                <div class="role-tabs">
                    <div class="role-tab active" data-role="mahasiswa">
                        <i class="bi bi-mortarboard me-2"></i>Mahasiswa
                    </div>
                    <div class="role-tab" data-role="admin">
                        <i class="bi bi-shield-lock me-2"></i>Admin
                    </div>
                </div>
                
                <input type="hidden" name="role" id="roleInput" value="mahasiswa">
                
                <div class="form-group">
                    <label for="email"><i class="bi bi-envelope me-2"></i>Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="Masukkan email Anda" required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Masukkan password Anda" required>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login Sekarang
                </button>
            </form>
            
            <div class="register-link">
                <p>Belum punya akun? <a href="register.php">Daftar sebagai Mahasiswa</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Role tab switching
    document.querySelectorAll('.role-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Update hidden input value
            const role = this.getAttribute('data-role');
            document.getElementById('roleInput').value = role;
        });
    });
    </script>
</body>
</html>