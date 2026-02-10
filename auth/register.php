<?php
require_once '../config/database.php';
require_once '../config/auth.php';

redirectIfLoggedIn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nim = trim($_POST['nim'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $prodi = trim($_POST['prodi'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validasi
    if (empty($nim) || empty($nama_lengkap) || empty($prodi) || empty($email) || empty($password)) {
        $error = 'Semua field harus diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak sama!';
    } else {
        // Cek apakah NIM sudah terdaftar
        $stmt = $pdo->prepare("SELECT id_mahasiswa FROM mahasiswa WHERE nim = :nim");
        $stmt->execute([':nim' => $nim]);
        if ($stmt->fetch()) {
            $error = 'NIM sudah terdaftar!';
        } else {
            // Cek apakah email sudah terdaftar
            $stmt = $pdo->prepare("SELECT id_mahasiswa FROM mahasiswa WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $error = 'Email sudah terdaftar!';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                // Insert ke database
                $stmt = $pdo->prepare("
                    INSERT INTO mahasiswa (nim, nama_lengkap, prodi, email, password) 
                    VALUES (:nim, :nama_lengkap, :prodi, :email, :password)
                ");
                
                try {
                    $stmt->execute([
                        ':nim' => $nim,
                        ':nama_lengkap' => $nama_lengkap,
                        ':prodi' => $prodi,
                        ':email' => $email,
                        ':password' => $hashed_password
                    ]);
                    
                    $success = 'Registrasi berhasil! Silakan login.';
                    
                    // Reset form
                    $_POST = [];
                    
                } catch(PDOException $e) {
                    $error = 'Terjadi kesalahan: ' . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SIPASTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-500: #667eea;
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
        
        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 60px 40px;
            max-width: 600px;
            width: 100%;
        }
        
        .register-container h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .register-container p {
            color: #666;
            margin-bottom: 40px;
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
        
        .btn-register {
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
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            border-radius: 12px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .login-link a {
            color: var(--primary-500);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .login-link a:hover {
            color: #f093fb;
            text-decoration: underline;
        }
        
        .back-arrow {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            font-size: 1.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .back-arrow:hover {
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <a href="login.php" class="back-arrow">
        <i class="bi bi-arrow-left-circle-fill"></i>
    </a>
    
    <div class="register-container">
        <h1>📝 Daftar Akun</h1>
        <p>Buat akun baru untuk mengakses SIPASTI sebagai mahasiswa</p>
        
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
            <div class="form-group">
                <label for="nim"><i class="bi bi-hash me-2"></i>NIM</label>
                <input type="text" class="form-control" id="nim" name="nim" 
                       placeholder="Contoh: 11203362310099" required 
                       value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="nama_lengkap"><i class="bi bi-person me-2"></i>Nama Lengkap</label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                       placeholder="Masukkan nama lengkap Anda" required 
                       value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="prodi"><i class="bi bi-book me-2"></i>Program Studi</label>
                <select class="form-control" id="prodi" name="prodi" required>
                    <option value="">-- Pilih Program Studi --</option>
                    <option value="S1 Teknologi Informasi" <?= (($_POST['prodi'] ?? '') == 'S1 Teknologi Informasi') ? 'selected' : '' ?>>S1 Teknologi Informasi</option>
                    <option value="S1 Sistem Informasi" <?= (($_POST['prodi'] ?? '') == 'S1 Sistem Informasi') ? 'selected' : '' ?>>S1 Sistem Informasi</option>
                    <option value="S1 Informatika" <?= (($_POST['prodi'] ?? '') == 'S1 Informatika') ? 'selected' : '' ?>>S1 Informatika</option>
                    <option value="S1 Komputerisasi Akuntansi" <?= (($_POST['prodi'] ?? '') == 'S1 Komputerisasi Akuntansi') ? 'selected' : '' ?>>S1 Komputerisasi Akuntansi</option>
                    <option value="S1 Manajemen Informatika" <?= (($_POST['prodi'] ?? '') == 'S1 Manajemen Informatika') ? 'selected' : '' ?>>S1 Manajemen Informatika</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="email"><i class="bi bi-envelope me-2"></i>Email</label>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="Masukkan email aktif Anda" required 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Minimal 6 karakter" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password"><i class="bi bi-lock me-2"></i>Konfirmasi Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                       placeholder="Ulangi password Anda" required>
            </div>
            
            <button type="submit" class="btn-register">
                <i class="bi bi-person-plus-fill me-2"></i>Daftar Sekarang
            </button>
        </form>
        
        <div class="login-link">
            <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>