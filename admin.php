<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use Dmc\Settings;

$user = current_user();
if (!$user || ($user['role'] ?? '') !== 'admin') {
    redirect('auth.php?mode=login');
}

$settings = Settings::all();
$saved = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings['app_name'] = trim((string)($_POST['app_name'] ?? $settings['app_name']));
    $settings['workspace_name'] = trim((string)($_POST['workspace_name'] ?? $settings['workspace_name']));
    $settings['demo_limit'] = max(0, (int)($_POST['demo_limit'] ?? $settings['demo_limit']));
    $settings['monthly_limit'] = max(1, (int)($_POST['monthly_limit'] ?? $settings['monthly_limit']));
    $settings['pdf_size_mm'] = max(30, min(300, (int)($_POST['pdf_size_mm'] ?? $settings['pdf_size_mm'])));
    $settings['pdf_margin_mm'] = max(0, min(30, (int)($_POST['pdf_margin_mm'] ?? $settings['pdf_margin_mm'])));
    $settings['trust_badges'] = array_values(array_filter(array_map('trim', explode("\n", (string)($_POST['trust_badges'] ?? '')))));

    foreach (['free', 'pro', 'business', 'enterprise'] as $plan) {
        $settings['plans'][$plan]['name'] = trim((string)($_POST["plan_{$plan}_name"] ?? $settings['plans'][$plan]['name']));
        $settings['plans'][$plan]['price'] = trim((string)($_POST["plan_{$plan}_price"] ?? $settings['plans'][$plan]['price']));
        $settings['plans'][$plan]['limit'] = trim((string)($_POST["plan_{$plan}_limit"] ?? $settings['plans'][$plan]['limit']));
        $settings['plans'][$plan]['description'] = trim((string)($_POST["plan_{$plan}_description"] ?? $settings['plans'][$plan]['description']));
    }

    if (!is_writable(APP_ROOT . '/config')) {
        $errors[] = 'config klasörü yazılabilir değil.';
    }

    if (!$errors) {
        Settings::save($settings);
        $saved = true;
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Settings - <?= e($settings['app_name']) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="admin-body">
    <main class="admin-shell">
        <div class="page-head">
            <div><p class="eyebrow">Admin Panel</p><h1>Parameter Management</h1><p>Manage demo limits, plans, PDF output and brand text from this screen.</p></div>
            <a class="btn outline" href="index.php?view=dashboard">Back to dashboard</a>
        </div>

        <?php if ($saved): ?><div class="demo-banner"><strong>Settings saved.</strong><span>Changes apply on new requests.</span></div><?php endif; ?>
        <?php if ($errors): ?><div class="demo-banner warning"><strong>Could not save.</strong><span><?= e(implode(' ', $errors)) ?></span></div><?php endif; ?>

        <form method="post" class="admin-form">
            <section class="card">
                <div class="card-head"><h2>General Settings</h2></div>
                <div class="settings-grid">
                    <label><span>Application name</span><input name="app_name" value="<?= e($settings['app_name']) ?>"></label>
                    <label><span>Workspace name</span><input name="workspace_name" value="<?= e($settings['workspace_name']) ?>"></label>
                    <label><span>Demo limit</span><input type="number" name="demo_limit" value="<?= e((string)$settings['demo_limit']) ?>"></label>
                    <label><span>Monthly limit</span><input type="number" name="monthly_limit" value="<?= e((string)$settings['monthly_limit']) ?>"></label>
                    <label><span>PDF kare ölçüsü (mm)</span><input type="number" name="pdf_size_mm" value="<?= e((string)$settings['pdf_size_mm']) ?>"></label>
                    <label><span>PDF margin (mm)</span><input type="number" name="pdf_margin_mm" value="<?= e((string)$settings['pdf_margin_mm']) ?>"></label>
                </div>
                <label class="full-field"><span>Trust badges - one per line</span><textarea name="trust_badges" rows="4"><?= e(implode("\n", $settings['trust_badges'])) ?></textarea></label>
            </section>

            <section class="card">
                <div class="card-head"><h2>Plans</h2></div>
                <div class="admin-plans">
                    <?php foreach ($settings['plans'] as $key => $plan): ?>
                        <fieldset>
                            <legend><?= e(strtoupper($key)) ?></legend>
                            <label><span>Name</span><input name="plan_<?= e($key) ?>_name" value="<?= e($plan['name']) ?>"></label>
                            <label><span>Price</span><input name="plan_<?= e($key) ?>_price" value="<?= e($plan['price']) ?>"></label>
                            <label><span>Limit</span><input name="plan_<?= e($key) ?>_limit" value="<?= e($plan['limit']) ?>"></label>
                            <label><span>Description</span><input name="plan_<?= e($key) ?>_description" value="<?= e($plan['description']) ?>"></label>
                        </fieldset>
                    <?php endforeach; ?>
                </div>
            </section>

            <button class="btn primary" type="submit">Save Settings</button>
        </form>
    </main>
</body>
</html>
