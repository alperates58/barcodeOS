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
<html lang="tr" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isRegister ? 'Hesap Oluştur' : 'Giriş Yap' ?> — barcodeOS</title>
    <link rel="stylesheet" href="assets/css/tokens.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <script>
        (function(){
            var t = localStorage.getItem('bos-theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body>
<div class="auth-shell">

    <!-- Left visual panel -->
    <div class="auth-panel-left">
        <div class="auth-glow"></div>

        <!-- Top bar -->
        <div class="auth-top">
            <a href="index.php?view=dashboard" style="display:inline-flex;align-items:center;gap:8px;text-decoration:none;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                    <rect x="1" y="1" width="22" height="22" rx="6" fill="oklch(0.78 0.14 32)"/>
                    <g stroke="#141210" stroke-linecap="round">
                        <line x1="6" y1="7" x2="6" y2="17" stroke-width="1.6"/>
                        <line x1="9" y1="7" x2="9" y2="17" stroke-width="2.8"/>
                        <line x1="12.5" y1="7" x2="12.5" y2="17" stroke-width="1.2"/>
                        <line x1="15" y1="7" x2="15" y2="17" stroke-width="2.2"/>
                        <line x1="18" y1="7" x2="18" y2="17" stroke-width="1.6"/>
                    </g>
                </svg>
                <span style="font-size:16px;font-weight:600;letter-spacing:-0.01em;color:var(--bg-raised);">barcode<span style="color:oklch(0.78 0.14 32)">OS</span></span>
            </a>
            <div class="auth-status-pill">
                <span class="auth-status-dot"></span>
                <span>Tüm sistemler çalışıyor · %99.99 SLA</span>
            </div>
        </div>

        <!-- Hero -->
        <div class="auth-hero">
            <h1 class="auth-headline">
                Her barkod,<br>
                <span class="warm">eksiksiz üretilir.</span>
            </h1>
            <p class="auth-desc">
                GS1 uyumlu, toplu üretim destekli barkod platformu. Yavaş araçlara son, tek tıkla ZIP, PDF ve API.
            </p>

            <div class="auth-stats-card">
                <div class="auth-stats-header">
                    <span>Canlı · Son 24 saat</span>
                    <span class="mono" style="font-size:11px;"><?= date('H:i:s') ?></span>
                </div>
                <div class="auth-barcode-bars">
                    <?php for ($i = 0; $i < 72; $i++):
                        $n = ($i * 13 + 7) % 9;
                        $w = $n < 2 ? 1 : ($n < 5 ? 2 : 3);
                        $show = $i % 2 === 0;
                    ?>
                        <div style="width:<?= $w ?>px;background:<?= $show ? 'oklch(0.9 0.02 32)' : 'transparent' ?>;opacity:<?= $show ? '0.85' : '0' ?>;"></div>
                    <?php endfor; ?>
                </div>
                <div class="auth-stats-grid">
                    <div>
                        <div class="auth-stat-v">14.2</div>
                        <div class="auth-stat-k">Barkod/sn</div>
                    </div>
                    <div>
                        <div class="auth-stat-v">99.99%</div>
                        <div class="auth-stat-k">Uptime</div>
                    </div>
                    <div>
                        <div class="auth-stat-v">38 ms</div>
                        <div class="auth-stat-k">Gecikme</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trust -->
        <div class="auth-trust">
            <span style="text-transform:uppercase;letter-spacing:0.08em;">Güvenilen markalar</span>
            <span class="auth-trust-sep"></span>
            <div class="auth-trust-brands">
                <span>Aksa Lojistik</span>
                <span>Mavi Nokta</span>
                <span>Terra Supply</span>
                <span>Halo Depo</span>
            </div>
        </div>
    </div>

    <!-- Right form panel -->
    <div class="auth-panel-right">
        <div class="auth-corner-link">
            <span><?= $isRegister ? 'Hesabınız var mı?' : 'Yeni misiniz?' ?></span>
            <a href="auth.php?mode=<?= $isRegister ? 'login' : 'register' ?>" class="btn btn-secondary btn-sm">
                <?= $isRegister ? 'Giriş Yap' : 'Ücretsiz Başla' ?>
            </a>
        </div>

        <div class="auth-form-container">
            <h1 class="auth-title"><?= $isRegister ? 'Hesap oluştur' : "barcodeOS'a giriş yap" ?></h1>
            <p class="auth-subtitle"><?= $isRegister ? 'Demo limitlerini kaldır, geçmişe ve API\'ye eriş.' : 'Şirket e-postanızı kullanın.' ?></p>

            <?php if ($errors): ?>
                <div class="auth-errors">
                    <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!$isRegister): ?>
            <!-- SSO options -->
            <div class="auth-sso">
                <button type="button" class="btn btn-secondary btn-lg auth-sso-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24"><path d="M21.35 11.1H12v2.9h5.35c-.23 1.5-1.68 4.4-5.35 4.4-3.22 0-5.85-2.67-5.85-5.95S8.78 6.5 12 6.5c1.83 0 3.06.78 3.76 1.45l2.57-2.47C16.73 3.96 14.57 3 12 3 6.98 3 3 6.98 3 12s3.98 9 9 9c5.2 0 8.64-3.65 8.64-8.79 0-.6-.07-1.05-.14-1.1Z" fill="#4285F4"/></svg>
                    Google Workspace ile devam et
                </button>
                <button type="button" class="btn btn-secondary btn-lg auth-sso-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/></svg>
                    SSO ile devam et (SAML)
                </button>
            </div>
            <div class="auth-or"><hr><span>veya</span><hr></div>
            <?php endif; ?>

            <!-- Email/password form -->
            <form method="post" class="auth-form">
                <input type="hidden" name="mode" value="<?= $isRegister ? 'register' : 'login' ?>">

                <?php if ($isRegister): ?>
                <div class="auth-field">
                    <label class="auth-label" for="name">Ad Soyad / Şirket</label>
                    <input id="name" class="auth-input" name="name" type="text"
                           value="<?= e($_POST['name'] ?? '') ?>" placeholder="Adınız veya şirket adı">
                </div>
                <?php endif; ?>

                <div class="auth-field">
                    <label class="auth-label" for="email">E-posta</label>
                    <input id="email" class="auth-input" name="email" type="email"
                           value="<?= e($_POST['email'] ?? '') ?>" placeholder="siz@sirket.com" required>
                </div>

                <div class="auth-field">
                    <label class="auth-label" for="password">
                        <span>Şifre</span>
                        <?php if (!$isRegister): ?><a href="#">Unuttum?</a><?php endif; ?>
                    </label>
                    <input id="password" class="auth-input" name="password" type="password"
                           placeholder="••••••••••" required>
                </div>

                <?php if (!$isRegister): ?>
                <label class="auth-remember">
                    <input type="checkbox" checked>
                    Bu cihazda oturumumu açık tut
                </label>
                <?php endif; ?>

                <button type="submit" class="btn btn-accent btn-lg auth-submit">
                    <?= $isRegister ? 'Hesap Oluştur' : 'Giriş Yap' ?>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                </button>
            </form>

            <!-- Operator box -->
            <div class="auth-operator">
                <div class="auth-operator-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 8v8M10 8v8M13 8v8M17 8v8"/></svg>
                </div>
                <div class="auth-operator-info">
                    <div class="auth-operator-title">Demo modunda mı?</div>
                    <div class="auth-operator-sub">Hesap olmadan <?= e((string)($settings['demo_limit'] ?? 10)) ?> barkod ücretsiz oluşturabilirsiniz.</div>
                </div>
                <a href="index.php?view=generator" class="btn btn-secondary btn-sm">Demo dene</a>
            </div>

            <div class="auth-switch">
                <?php if ($isRegister): ?>
                    Zaten hesabınız var mı? <a href="auth.php?mode=login">Giriş yapın</a>
                <?php else: ?>
                    Hesabınız yok mu? <a href="auth.php?mode=register">Ücretsiz başlayın</a>
                <?php endif; ?>
            </div>

            <div class="auth-footer">
                SOC 2 Type II korumalı ·
                <a href="#">Gizlilik</a> ·
                <a href="#">Kullanım Şartları</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
