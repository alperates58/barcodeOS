<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use Dmc\Database;
use Dmc\Settings;

$errors = [];
$success = false;

if (app_is_installed()) {
    $success = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$success) {
    $db = [
        'host' => trim((string)($_POST['db_host'] ?? 'localhost')),
        'port' => (int)($_POST['db_port'] ?? 3306),
        'database' => trim((string)($_POST['db_name'] ?? '')),
        'username' => trim((string)($_POST['db_user'] ?? '')),
        'password' => (string)($_POST['db_pass'] ?? ''),
        'charset' => 'utf8mb4',
    ];

    $siteName = trim((string)($_POST['site_name'] ?? 'BarcodeOS.com'));
    $adminName = trim((string)($_POST['admin_name'] ?? 'Admin'));
    $adminEmail = trim((string)($_POST['admin_email'] ?? ''));
    $adminPassword = (string)($_POST['admin_password'] ?? '');

    if ($db['database'] === '' || $db['username'] === '') {
        $errors[] = 'Veritabanı adı ve kullanıcı adı zorunludur.';
    }
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir admin e-posta adresi girin.';
    }
    if (strlen($adminPassword) < 8) {
        $errors[] = 'Admin şifresi en az 8 karakter olmalıdır.';
    }
    if (!is_writable(dirname(CONFIG_PATH))) {
        $errors[] = 'config klasörü yazılabilir değil.';
    }

    if (!$errors) {
        try {
            $pdo = Database::connectWithoutDatabase($db);
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $db['database']) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

            $config = [
                'installed' => true,
                'app' => [
                    'name' => $siteName,
                    'key' => bin2hex(random_bytes(24)),
                ],
                'db' => $db,
            ];

            $pdo = Database::connect($config);
            $schema = file_get_contents(APP_ROOT . '/database/schema.sql');
            if ($schema === false) {
                throw new RuntimeException('schema.sql okunamadı.');
            }
            $pdo->exec($schema);

            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, plan_key) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), role = VALUES(role), plan_key = VALUES(plan_key)');
            $stmt->execute([$adminName, $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT), 'admin', 'enterprise']);

            $configPhp = "<?php\n\nreturn " . var_export($config, true) . ";\n";
            file_put_contents(CONFIG_PATH, $configPhp);
            Settings::seedDatabase(Settings::defaults());
            $success = true;
        } catch (Throwable $e) {
            $errors[] = 'Kurulum hatası: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install - BarcodeOS.com</title>
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="install-body">
    <main class="install-layout">
        <section class="install-hero">
            <div class="install-brand">BarcodeOS.com</div>
            <h1>All Your Barcode Tools. One Platform.</h1>
            <p>Install the premium barcode generation workspace on any standard PHP + MySQL hosting environment.</p>
            <div class="install-badges">
                <span>GS1 Ready</span>
                <span>Bulk Processing</span>
                <span>SaaS Billing Ready</span>
                <span>Admin Managed</span>
            </div>
        </section>

        <section class="panel install-panel">
            <h1>Install BarcodeOS</h1>
            <p>Connect MySQL, create the first admin account and launch your workspace.</p>

            <?php if ($success): ?>
                <div class="notice success">
                    Installation complete. For security, delete or protect the `install` directory.
                </div>
                <a class="primary-link" href="../">Open Workspace</a>
            <?php else: ?>
                <?php if ($errors): ?>
                    <div class="notice error">
                        <?php foreach ($errors as $error): ?>
                            <div><?= e($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="install-form">
                    <h2>Site</h2>
                    <label>Site name <input name="site_name" value="<?= e($_POST['site_name'] ?? 'BarcodeOS.com') ?>"></label>

                    <h2>MySQL</h2>
                    <div class="form-grid">
                        <label>Host <input name="db_host" value="<?= e($_POST['db_host'] ?? 'localhost') ?>"></label>
                        <label>Port <input name="db_port" value="<?= e($_POST['db_port'] ?? '3306') ?>"></label>
                        <label>Database <input name="db_name" value="<?= e($_POST['db_name'] ?? '') ?>"></label>
                        <label>Username <input name="db_user" value="<?= e($_POST['db_user'] ?? '') ?>"></label>
                    </div>
                    <label>Password <input type="password" name="db_pass"></label>

                    <h2>Admin</h2>
                    <div class="form-grid">
                        <label>Name <input name="admin_name" value="<?= e($_POST['admin_name'] ?? 'Admin') ?>"></label>
                        <label>Email <input name="admin_email" value="<?= e($_POST['admin_email'] ?? '') ?>"></label>
                    </div>
                    <label>Password <input type="password" name="admin_password"></label>

                    <button class="primary-btn" type="submit">Start Installation</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
