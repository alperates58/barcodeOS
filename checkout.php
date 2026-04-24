<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use Dmc\Settings;

$settings = Settings::all();
$plan = $_GET['plan'] ?? 'pro';
$plans = $settings['plans'];
$selected = $plans[$plan] ?? $plans['pro'];
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout - DataMatrix Pro</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-body">
    <main class="auth-card checkout-card">
        <a class="auth-brand" href="index.php?view=billing">DataMatrix Pro</a>
        <h1><?= e($selected['name']) ?> plan</h1>
        <p>Checkout screen prepared for a payment provider. Stripe, Iyzico or PayTR integration can be connected here.</p>
        <div class="checkout-summary">
            <span>Plan</span><strong><?= e($selected['name']) ?></strong>
            <span>Price</span><strong><?= e($selected['price']) ?></strong>
            <span>Limit</span><strong><?= e($selected['limit']) ?></strong>
        </div>
        <div class="auth-errors soft">
            Bu demo sürümde gerçek ödeme alınmaz. Entegrasyon sonrası webhook ile paket aktiflenecek.
        </div>
        <a class="btn primary wide" href="auth.php?mode=register">Create account and continue</a>
        <a class="btn outline wide" href="index.php?view=billing">Back to plans</a>
    </main>
</body>
</html>
