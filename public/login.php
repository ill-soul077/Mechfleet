<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';

$error = null;
$next = $_GET['next'] ?? ($_POST['next'] ?? 'sql_demos.php');

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

$pageTitle = 'Manager Login';
require __DIR__ . '/header.php';
?>
  <h2>Manager Login</h2>
  <p class="muted">Enter your manager email to access the SQL Demos. In production, use proper authentication.</p>

  <?php if ($error): ?>
    <p class="err"><strong>Error:</strong> <?= e($error) ?></p>
  <?php endif; ?>

  <form method="post" action="">
    <input type="hidden" name="next" value="<?= e($next) ?>" />
    <label for="email"><strong>Email</strong></label><br />
    <input type="email" id="email" name="email" required placeholder="manager@example.com" />
    <div style="margin-top:.5rem"></div>
    <label for="code"><strong>Access Code</strong> <span class="muted">(demo: letmein)</span></label><br />
    <input type="password" id="code" name="code" placeholder="letmein" />
    <div style="margin-top:.5rem"></div>
    <button type="submit">Sign in</button>
  </form>

<?php require __DIR__ . '/footer.php'; ?>
