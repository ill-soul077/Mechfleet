<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';

// If already logged in, redirect to dashboard
if (auth_is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = null;
$next = $_GET['next'] ?? ($_POST['next'] ?? 'index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $code  = trim($_POST['code'] ?? '');

    try {
        // Simple "manager code" check to keep it basic: code must be 'letmein' in DEV
        if (!DEV_MODE && $code !== 'letmein') {
            throw new RuntimeException('Invalid access code.');
        }
        $stmt = $pdo->prepare('SELECT manager_id, first_name, last_name FROM manager WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $mgr = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$mgr) {
            throw new RuntimeException('Manager account not found.');
        }
        auth_login($email);
        header('Location: ' . $next);
        exit;
    } catch (Throwable $t) {
        $error = $t->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login · Mechfleet</title>
  
  <!-- Bootstrap 5.3 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    
    .login-container {
      width: 100%;
      max-width: 450px;
      padding: 20px;
    }
    
    .login-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      overflow: hidden;
    }
    
    .login-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 40px 30px;
      text-align: center;
      color: white;
    }
    
    .login-header i {
      font-size: 3rem;
      margin-bottom: 15px;
      opacity: 0.95;
    }
    
    .login-header h1 {
      font-size: 1.75rem;
      font-weight: 700;
      margin: 0 0 8px 0;
    }
    
    .login-header p {
      margin: 0;
      opacity: 0.9;
      font-size: 0.95rem;
    }
    
    .login-body {
      padding: 40px 30px;
    }
    
    .form-label {
      font-weight: 600;
      color: #495057;
      margin-bottom: 8px;
    }
    
    .form-control {
      border-radius: 8px;
      border: 2px solid #e9ecef;
      padding: 12px 16px;
      font-size: 0.95rem;
      transition: all 0.3s ease;
    }
    
    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
    }
    
    .btn-login {
      width: 100%;
      padding: 14px;
      font-size: 1rem;
      font-weight: 600;
      border-radius: 8px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      color: white;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
      color: white;
    }
    
    .btn-login:active {
      transform: translateY(0);
    }
    
    .alert {
      border-radius: 8px;
      border: none;
    }
    
    .hint-text {
      font-size: 0.85rem;
      color: #6c757d;
      margin-top: 4px;
    }
    
    .input-icon-wrapper {
      position: relative;
    }
    
    .input-icon-wrapper i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: #6c757d;
    }
    
    .input-icon-wrapper input {
      padding-left: 45px;
    }
  </style>
</head>
<body>

<div class="login-container">
  <div class="login-card">
    <div class="login-header">
      <i class="fas fa-wrench"></i>
      <h1>Mechfleet</h1>
      <p>Management System</p>
    </div>
    
    <div class="login-body">
      <h2 class="h4 mb-3 text-center" style="color: #495057; font-weight: 600;">Welcome Back</h2>
      
      <?php if ($error): ?>
        <div class="alert alert-danger mb-4">
          <i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?>
        </div>
      <?php endif; ?>

      <form method="post" action="">
        <input type="hidden" name="next" value="<?= e($next) ?>" />
        
        <div class="mb-3">
          <label for="email" class="form-label">Email Address</label>
          <div class="input-icon-wrapper">
            <i class="fas fa-envelope"></i>
            <input type="email" id="email" name="email" class="form-control" 
                   required placeholder="manager@mechfleet.com" 
                   value="<?= e($_POST['email'] ?? '') ?>" autofocus>
          </div>
        </div>
        
        <div class="mb-4">
          <label for="code" class="form-label">Access Code</label>
          <div class="input-icon-wrapper">
            <i class="fas fa-lock"></i>
            <input type="password" id="code" name="code" class="form-control" 
                   required placeholder="Enter access code">
          </div>
          <div class="hint-text">
            <i class="fas fa-info-circle me-1"></i>Demo code: <strong>letmein</strong>
          </div>
        </div>
        
        <button type="submit" class="btn btn-login">
          <i class="fas fa-sign-in-alt me-2"></i>Sign In
        </button>
      </form>
    </div>
  </div>
  
  <div class="text-center mt-4 text-white">
    <small>&copy; <?= date('Y') ?> Mechfleet. All rights reserved.</small>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
