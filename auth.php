<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use Dmc\Database;

$config = app_config();
$mode = $_GET['mode'] ?? 'login';
$errors = [];

if (($_GET['action'] ?? '') === 'logout') {
    unset($_SESSION['user']);
    redirect('index.php?view=dashboard');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'login';
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $name = trim((string)($_POST['name'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi girin.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Şifre en az 6 karakter olmalıdır.';
    }

    if (!$errors) {
        try {
            $pdo = Database::connect($config);

            if ($mode === 'register') {
                if ($name === '') {
                    $name = explode('@', $email)[0];
                }

                $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE email = email');
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), 'user']);
            }

            $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errors[] = 'E-posta veya şifre hatalı.';
            } else {
                $_SESSION['user'] = [
                    'id' => (int)$user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'plan' => 'Pro',
                ];
                redirect('index.php?view=dashboard');
            }
        } catch (Throwable $e) {
                $errors[] = 'Membership requires a database connection. If installation is complete, please check your database settings.';
        }
    }
}

$isRegister = $mode === 'register';
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isRegister ? 'Create Account' : 'Sign In' ?> - DataMatrix Pro</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-body">
    <main class="auth-card">
        <a class="auth-brand" href="index.php?view=dashboard">DataMatrix Pro</a>
        <h1><?= $isRegister ? 'Create your workspace' : 'Sign in to your account' ?></h1>
        <p><?= $isRegister ? 'Remove demo limits and unlock history, plans and team features.' : 'Manage plan limits, batch history and generated outputs.' ?></p>

        <?php if ($errors): ?>
            <div class="auth-errors">
                <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="auth-form">
            <input type="hidden" name="mode" value="<?= $isRegister ? 'register' : 'login' ?>">
            <?php if ($isRegister): ?>
                <label>Name / Company <input name="name" value="<?= e($_POST['name'] ?? '') ?>"></label>
            <?php endif; ?>
            <label>Email <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required></label>
            <label>Password <input type="password" name="password" required></label>
            <button class="btn primary wide" type="submit"><?= $isRegister ? 'Create Account' : 'Sign In' ?></button>
        </form>

        <div class="auth-switch">
            <?php if ($isRegister): ?>
                Already have an account? <a href="auth.php?mode=login">Sign in</a>
            <?php else: ?>
                No account yet? <a href="auth.php?mode=register">Start free</a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
