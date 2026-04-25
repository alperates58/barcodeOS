<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use Dmc\Services\Gs1Parser;
use Dmc\Services\InputParser;
use Dmc\Settings;

if (!app_is_installed()) {
    redirect('install/');
}

$config = app_config();
$settings = Settings::all();
$results = [];
$summary = null;
$posted = $_SERVER['REQUEST_METHOD'] === 'POST';
$view = $posted ? 'generator' : ($_GET['view'] ?? 'dashboard');
$allowedViews = ['dashboard', 'generator', 'jobs', 'history', 'validation', 'templates', 'api', 'billing', 'team', 'settings', 'support'];
if (!in_array($view, $allowedViews, true)) {
    $view = 'dashboard';
}

$barcodeGroups = [
    'Linear Codes' => [
        'code128' => 'Code 128',
        'code11' => 'Code 11',
        'interleaved2of5' => 'Code 2 of 5 Interleaved',
        'code39' => 'Code 39',
        'code93' => 'Code 93',
        'gs1-128' => 'GS1-128',
        'msi' => 'MSI',
        'pharmacode' => 'Pharmacode One-Track',
        'telepen' => 'Telepen Alpha',
    ],
    'EAN / UPC' => [
        'ean8' => 'EAN-8',
        'ean13' => 'EAN-13',
        'ean14' => 'EAN-14',
        'upca' => 'UPC-A',
        'upce' => 'UPC-E',
    ],
    '2D Codes' => [
        'datamatrix' => 'Data Matrix',
        'gs1datamatrix' => 'GS1 DataMatrix',
        'qrcode' => 'QR Code',
        'gs1qrcode' => 'GS1 QR Code',
        'azteccode' => 'Aztec',
        'pdf417' => 'PDF417',
        'micropdf417' => 'MicroPDF417',
        'microqrcode' => 'Micro QR Code',
        'maxicode' => 'MaxiCode',
        'dotcode' => 'DotCode',
        'codablockf' => 'Codablock-F',
    ],
    'GS1 DataBar' => [
        'databaromni' => 'GS1 DataBar',
        'databarstacked' => 'GS1 DataBar Stacked',
        'databarstackedomni' => 'GS1 DataBar Stacked Omni',
        'databarlimited' => 'GS1 DataBar Limited',
        'databarexpanded' => 'GS1 DataBar Expanded',
        'databarexpandedstacked' => 'GS1 DataBar Expanded Stacked',
    ],
    'Postal Codes' => [
        'auspost' => 'Australian Post',
        'daft' => 'DAFT',
        'japanpost' => 'Japanese Postal Code',
        'kix' => 'KIX',
        'planet' => 'Planet Code',
        'royalmail' => 'Royal Mail 4-State',
        'postnet' => 'USPS PostNet',
        'onecode' => 'USPS Intelligent Mail',
    ],
    'Healthcare / ISBN' => [
        'hibcazteccode' => 'HIBC LIC Aztec',
        'hibcdatamatrix' => 'HIBC LIC Data Matrix',
        'hibcqrcode' => 'HIBC LIC QR Code',
        'isbn' => 'ISBN-13',
        'issn' => 'ISSN',
        'ismn' => 'ISMN',
        'pzn' => 'PZN',
    ],
];

$inputMode = $_POST['input_mode'] ?? 'line';
$size = max(2, min(8, (int)($_POST['scale'] ?? 4)));
$allBarcodeTypes = array_merge(...array_values($barcodeGroups));
$selectedBarcode = $_POST['barcode_type'] ?? 'gs1datamatrix';
if (!$posted && isset($_GET['barcode_type']) && isset($allBarcodeTypes[$_GET['barcode_type']])) {
    $selectedBarcode = (string)$_GET['barcode_type'];
}
$selectedLabel = $allBarcodeTypes[$selectedBarcode] ?? 'GS1 DataMatrix';
$shouldNormalizeGs1 = in_array($selectedBarcode, ['gs1datamatrix', 'gs1-128', 'gs1qrcode'], true);
$user = current_user();
$signedIn = is_signed_in();
$lang = current_lang();
$demoLimit = (int)$settings['demo_limit'];
$_SESSION['demo_used'] = $_SESSION['demo_used'] ?? 0;
$demoUsed = (int)$_SESSION['demo_used'];
$demoRemaining = $signedIn ? PHP_INT_MAX : max(0, $demoLimit - $demoUsed);
$limitNotice = null;

if ($posted) {
    $inputParser = new InputParser();
    $gs1Parser = new Gs1Parser();
    [$items, $sourceType] = $inputParser->fromRequest($_FILES, $_POST);
    $requestedCount = count($items);

    if (!$signedIn && $requestedCount > $demoRemaining) {
        $limitNotice = 'Demo hakkınız sınırlıdır. İşlenen satır sayısı kalan demo hakkınıza göre kısıtlandı.';
    }

    foreach ($items as $offset => $item) {
        if (!$signedIn && $offset >= $demoRemaining) {
            $results[] = [
                'line_no' => $item['line_no'],
                'original' => $item['value'],
                'success' => false,
                'normalized' => null,
                'ai_text' => null,
                'error' => 'Demo limiti doldu. Devam etmek için ücretsiz hesap oluşturun veya paket seçin.',
                'code_type' => 'demo_limit',
            ];
            continue;
        }

        $parsed = $shouldNormalizeGs1
            ? $gs1Parser->normalize($item['value'])
            : [
                'success' => true,
                'is_gs1' => false,
                'code_type' => 'raw',
                'original' => $item['value'],
                'normalized' => $item['value'],
                'ai_text' => $item['value'],
                'error' => null,
            ];

        $results[] = [
            'line_no' => $item['line_no'],
            'original' => $item['value'],
            'success' => $parsed['success'],
            'normalized' => $parsed['normalized'],
            'ai_text' => $parsed['ai_text'],
            'error' => $parsed['error'],
            'code_type' => $parsed['code_type'],
        ];
    }

    $valid = count(array_filter($results, fn(array $row): bool => $row['success']));
    if (!$signedIn) {
        $_SESSION['demo_used'] = min($demoLimit, $demoUsed + $valid);
        $demoUsed = (int)$_SESSION['demo_used'];
        $demoRemaining = max(0, $demoLimit - $demoUsed);
    }

    $summary = [
        'total' => count($results),
        'valid' => $valid,
        'invalid' => count($results) - $valid,
        'source_type' => $sourceType,
    ];
}

$usageLimit = $signedIn ? (int)$settings['monthly_limit'] : $demoLimit;
$usageUsed = $signedIn ? 1840 + (int)($summary['valid'] ?? 0) : $demoUsed;
$usageRemaining = max(0, $usageLimit - $usageUsed);
$usagePercent = min(100, round($usageUsed / $usageLimit * 100));
$todayGenerated = (int)($summary['valid'] ?? 328);
$monthGenerated = $usageUsed;
$successRate = $summary && $summary['total'] > 0 ? round($summary['valid'] / $summary['total'] * 100, 1) : 98.4;
$invalidRows = (int)($summary['invalid'] ?? 17);

$mainMenu = [
    'dashboard' => 'Dashboard',
    'generator' => 'Barcode Generator',
    'jobs' => 'Batch Jobs',
    'history' => 'History',
    'validation' => 'Validation Reports',
    'templates' => 'Templates',
    'api' => 'API Keys',
    'billing' => 'Usage & Billing',
    'team' => 'Team Members',
    'settings' => 'Settings',
    'support' => 'Support',
];
if ($signedIn && ($user['role'] ?? '') === 'admin') {
    $mainMenu['admin'] = 'Admin Panel';
}

$mockJobs = [
    ['date' => '2026-04-24 15:42', 'type' => $selectedLabel, 'records' => $summary['total'] ?? 420, 'valid' => $summary['valid'] ?? 416, 'invalid' => $summary['invalid'] ?? 4, 'status' => 'Completed', 'formats' => 'PNG, ZIP, PDF'],
    ['date' => '2026-04-24 11:18', 'type' => 'GS1 DataMatrix', 'records' => 1850, 'valid' => 1843, 'invalid' => 7, 'status' => 'Completed', 'formats' => 'PDF'],
    ['date' => '2026-04-23 17:05', 'type' => 'Code 128', 'records' => 640, 'valid' => 640, 'invalid' => 0, 'status' => 'Processing', 'formats' => 'ZIP'],
    ['date' => '2026-04-23 09:32', 'type' => 'EAN-13', 'records' => 120, 'valid' => 118, 'invalid' => 2, 'status' => 'Failed', 'formats' => '-'],
];

$topTypes = [
    ['name' => 'GS1 DataMatrix', 'value' => 46],
    ['name' => 'Data Matrix', 'value' => 22],
    ['name' => 'Code 128', 'value' => 18],
    ['name' => 'QR Code', 'value' => 14],
];

$invalidResults = array_values(array_filter($results, fn(array $row): bool => !$row['success']));

// Nav icon SVG paths for sidebar
$navIcons = [
    'dashboard'  => '<path d="M3 3h7v9H3z"/><path d="M14 3h7v5H14z"/><path d="M14 12h7v9H14z"/><path d="M3 16h7v5H3z"/>',
    'generator'  => '<path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 8v8M10 8v8M13 8v8M17 8v8"/>',
    'jobs'       => '<path d="M21 8 12 3 3 8l9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v10"/>',
    'history'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'validation' => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/>',
    'templates'  => '<path d="M20 12 12 20l-9-9V3h8l9 9Z"/><circle cx="7.5" cy="7.5" r="1.2" fill="currentColor"/>',
    'api'        => '<path d="M15 9V6a2 2 0 1 1 2 2h-3m-6 0V6a2 2 0 1 0-2 2h3m0 0v6m0 0v2a2 2 0 1 1-2 2v-3m0 0h6m0 0v2a2 2 0 1 0 2-2h-3"/>',
    'billing'    => '<path d="M20 12 12 20l-9-9V3h8l9 9Z"/><circle cx="7.5" cy="7.5" r="1.2" fill="currentColor"/>',
    'team'       => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.8-3.3 3.5-5.5 6.5-5.5s5.7 2.2 6.5 5.5"/><circle cx="17" cy="6" r="2.5"/><path d="M21.5 14c-.4-1.7-1.7-3-3.5-3"/>',
    'settings'   => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
    'support'    => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.8.4-1 .9-1 1.7"/><circle cx="12" cy="17" r=".7" fill="currentColor"/>',
    'admin'      => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/>',
];

function navIcon(string $key, array $icons): string {
    $path = $icons[$key] ?? '<circle cx="12" cy="12" r="4"/>';
    return '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}
?>
<!doctype html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($settings['app_name']) ?></title>
    <link rel="stylesheet" href="assets/css/tokens.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <script>
        (function(){
            var t = localStorage.getItem('bos-theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body data-pdf-size="<?= e((string)$settings['pdf_size_mm']) ?>" data-pdf-margin="<?= e((string)$settings['pdf_margin_mm']) ?>">
    <div class="saas-shell">

        <!-- ── Sidebar ── -->
        <aside class="sidebar" id="sidebar">

            <!-- Brand -->
            <div class="sidebar-brand">
                <a class="brand-logo" href="?view=dashboard">
                    <span class="brand-mark-svg">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <rect x="1" y="1" width="22" height="22" rx="6" fill="var(--ink)"/>
                            <g stroke="var(--bg-raised)" stroke-linecap="round">
                                <line x1="6" y1="7" x2="6" y2="17" stroke-width="1.6"/>
                                <line x1="9" y1="7" x2="9" y2="17" stroke-width="2.8"/>
                                <line x1="12.5" y1="7" x2="12.5" y2="17" stroke-width="1.2"/>
                                <line x1="15" y1="7" x2="15" y2="17" stroke-width="2.2"/>
                                <line x1="18" y1="7" x2="18" y2="17" stroke-width="1.6"/>
                            </g>
                        </svg>
                    </span>
                    <span class="brand-wordmark">barcode<span class="accent">OS</span></span>
                </a>
                <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Daralt">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/><path d="m15 10-2 2 2 2"/></svg>
                </button>
            </div>

            <!-- Workspace -->
            <div class="sidebar-workspace">
                <button class="workspace-btn" type="button">
                    <span class="workspace-icon"><?= e(strtoupper(substr((string)$settings['workspace_name'], 0, 2))) ?></span>
                    <div class="workspace-info">
                        <div class="workspace-name"><?= e($settings['workspace_name']) ?></div>
                        <div class="workspace-sub"><?= $signedIn ? e(($user['plan'] ?? 'Pro') . ' · ') . 'aktif' : 'Demo modu' ?></div>
                    </div>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" style="color:var(--ink-3)"><path d="m6 9 6 6 6-6"/></svg>
                </button>
            </div>

            <!-- Nav -->
            <nav class="sidebar-nav" aria-label="Ana menü">

                <!-- Workspace group -->
                <div class="nav-group">
                    <span class="nav-group-label">Workspace</span>
                    <a class="nav-item <?= $view === 'dashboard' ? 'active' : '' ?>" href="?view=dashboard">
                        <?php if ($view === 'dashboard'): ?><span class="nav-active-bar"></span><?php endif; ?>
                        <span class="nav-item-icon"><?= navIcon('dashboard', $navIcons) ?></span>
                        <span class="nav-item-label">Dashboard</span>
                    </a>
                    <a class="nav-item <?= $view === 'generator' ? 'active' : '' ?>" href="?view=generator">
                        <?php if ($view === 'generator'): ?><span class="nav-active-bar"></span><?php endif; ?>
                        <span class="nav-item-icon"><?= navIcon('generator', $navIcons) ?></span>
                        <span class="nav-item-label">Oluşturucu</span>
                        <span class="nav-item-badge">Canlı</span>
                    </a>
                    <a class="nav-item <?= $view === 'jobs' ? 'active' : '' ?>" href="?view=jobs">
                        <?php if ($view === 'jobs'): ?><span class="nav-active-bar"></span><?php endif; ?>
                        <span class="nav-item-icon"><?= navIcon('jobs', $navIcons) ?></span>
                        <span class="nav-item-label">Toplu İşler</span>
                    </a>
                </div>

                <!-- Tools group -->
                <div class="nav-group">
                    <span class="nav-group-label">Araçlar</span>
                    <a class="nav-item <?= $view === 'history' ? 'active' : '' ?>" href="?view=history">
                        <?php if ($view === 'history'): ?><span class="nav-active-bar"></span><?php endif; ?>
                        <span class="nav-item-icon"><?= navIcon('history', $navIcons) ?></span>
                        <span class="nav-item-label">Geçmiş</span>
                    </a>
                    <a class="nav-item <?= $view === 'validation' ? 'active' : '' ?>" href="?view=validation">
                        <?php if ($view === 'validation'): ?><span class="nav-active-bar"></span><?php endif; ?>
                        <span class="nav-item-icon"><?= navIcon('validation', $navIcons) ?></span>
                        <span class="nav-item-label">Doğrulama</span>
                    </a>
                    <a class="nav-item <?= $view === 'templates' ? 'active' : '' ?>" href="?view=templates">
                        <?php if ($view === 'templates'): ?><span class="nav-active-bar"></span><?php endif; ?>
                        <span class="nav-item-icon"><?= navIcon('templates', $navIcons) ?></span>
                        <span class="nav-item-label">Şablonlar</span>
                    </a>
                </div>

                <!-- Account group -->
                <div class="nav-group">
                    <span class="nav-group-label">Hesap</span>
                    <a class="nav-item <?= $view === 'api' ? 'active' : '' ?>" href="?view=api">
                        <?php if ($view === 'api'): ?><span class="nav-active-bar"></span><?php endif; ?>
                        <span class="nav-item-icon"><?= navIcon('api', $navIcons) ?></span>
                        <span class="nav-item-label">API Anahtarları</span>
                    </a>
                    <a class="nav-item <?= $view === 'billing' ? 'active' : '' ?>" href="?view=billing">
                        <?php if ($view === 'billing'): ?><span class="nav-active-bar"></span><?php endif; ?>
                        <span class="nav-item-icon"><?= navIcon('billing', $navIcons) ?></span>
                        <span class="nav-item-label">Kullanım & Fatura</span>
                    </a>
                    <a class="nav-item <?= $view === 'team' ? 'active' : '' ?>" href="?view=team">
                        <?php if ($view === 'team'): ?><span class="nav-active-bar"></span><?php endif; ?>
                        <span class="nav-item-icon"><?= navIcon('team', $navIcons) ?></span>
                        <span class="nav-item-label">Ekip</span>
                    </a>
                    <?php if ($signedIn && ($user['role'] ?? '') === 'admin'): ?>
                    <a class="nav-item <?= $view === 'admin' ? 'active' : '' ?>" href="admin.php">
                        <span class="nav-item-icon"><?= navIcon('admin', $navIcons) ?></span>
                        <span class="nav-item-label">Yönetici</span>
                    </a>
                    <?php endif; ?>
                </div>
            </nav>

            <!-- Symbology panel (collapsible) -->
            <div class="symbology-panel" id="symbologyPanel">
                <button class="symbology-toggle" type="button" id="symbologyToggle" aria-expanded="false" aria-controls="symbologyList">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 8v8M10 8v8M13 8v8M17 8v8"/></svg>
                    <span>Sembolojiler</span>
                    <b aria-hidden="true">+</b>
                </button>
                <div class="symbology-list" id="symbologyList" hidden>
                    <?php foreach ($barcodeGroups as $group => $types): ?>
                        <?php $groupId = 'sym_' . preg_replace('/[^a-z0-9]+/i', '_', strtolower($group)); ?>
                        <?php $groupActive = array_key_exists($selectedBarcode, $types); ?>
                        <div class="sym-group <?= $groupActive ? 'open active' : '' ?>">
                            <button type="button" class="sym-group-toggle" aria-expanded="<?= $groupActive ? 'true' : 'false' ?>" aria-controls="<?= e($groupId) ?>">
                                <span><?= e($group) ?></span>
                                <b aria-hidden="true"><?= $groupActive ? '-' : '+' ?></b>
                            </button>
                            <div class="sym-sublist" id="<?= e($groupId) ?>" <?= $groupActive ? '' : 'hidden' ?>>
                                <?php foreach ($types as $value => $label): ?>
                                    <button type="button" class="<?= $selectedBarcode === $value ? 'active' : '' ?>" data-bcid="<?= e($value) ?>">
                                        <?= e($label) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quota -->
            <div class="sidebar-quota">
                <div class="quota-inner">
                    <div class="quota-plan"><?= $signedIn ? e($user['plan'] ?? 'Pro') . ' Plan' : 'Demo Modu' ?></div>
                    <div class="quota-numbers">
                        <span class="quota-used mono"><?= e(number_format($usageUsed)) ?></span>
                        <span class="quota-total mono">/ <?= e(number_format($usageLimit)) ?></span>
                    </div>
                    <div class="quota-meter">
                        <div class="quota-meter-fill <?= $usagePercent > 85 ? 'danger' : ($usagePercent > 65 ? 'warn' : '') ?>"
                             style="width:<?= e((string)$usagePercent) ?>%"></div>
                    </div>
                    <a class="quota-upgrade" href="<?= $signedIn ? '?view=billing' : 'auth.php?mode=register' ?>">
                        <?= $signedIn ? 'Planı Yükselt' : 'Ücretsiz Hesap Aç' ?>
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="sidebar-footer">
                <div class="sidebar-footer-nav">
                    <a class="sidebar-footer-btn" href="admin.php">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/></svg>
                        <span>Ayarlar</span>
                    </a>
                    <a class="sidebar-footer-btn" href="?view=support">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.8.4-1 .9-1 1.7"/><circle cx="12" cy="17" r=".7" fill="currentColor"/></svg>
                        <span>Yardım & Dokümantasyon</span>
                    </a>
                </div>
                <?php if ($signedIn): ?>
                <div class="sidebar-user">
                    <div class="avatar"><?= e(strtoupper(substr((string)$user['name'], 0, 2))) ?></div>
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name"><?= e($user['name']) ?></div>
                        <div class="sidebar-user-sub"><?= e($user['email']) ?></div>
                    </div>
                    <a href="auth.php?action=logout" style="border:0;background:transparent;color:var(--ink-3);cursor:pointer;padding:4px;border-radius:5px;display:flex;" title="Çıkış">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M9 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4"/><path d="m16 8 4 4-4 4"/><path d="M20 12H9"/></svg>
                    </a>
                </div>
                <?php else: ?>
                <div style="display:flex;gap:8px;">
                    <a href="auth.php?mode=login" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center;">Giriş</a>
                    <a href="auth.php?mode=register" class="btn btn-accent btn-sm" style="flex:1;justify-content:center;">Başla</a>
                </div>
                <?php endif; ?>
            </div>
        </aside>

        <!-- ── Content ── -->
        <div class="content-shell">

            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-title">
                    <span class="topbar-page-name"><?= e($mainMenu[$view] ?? 'Dashboard') ?></span>
                    <?php if ($view === 'dashboard'): ?>
                    <span class="topbar-sub"><?= e($settings['workspace_name']) ?> · canlı</span>
                    <?php endif; ?>
                </div>

                <button type="button" class="topbar-search" onclick="document.getElementById('input_text')?.focus()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <span>Batch, kod, şablon ara…</span>
                    <span class="kbd">/</span>
                </button>

                <div class="topbar-actions">
                    <!-- Theme toggle -->
                    <button type="button" class="topbar-icon-btn" id="themeToggle" title="Temayı değiştir">
                        <svg id="themeIconMoon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M21 13A9 9 0 1 1 11 3a7 7 0 0 0 10 10Z"/></svg>
                        <svg id="themeIconSun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" style="display:none"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    </button>

                    <!-- Notifications -->
                    <button type="button" class="topbar-icon-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M6 8a6 6 0 1 1 12 0c0 5 2 6 2 8H4c0-2 2-3 2-8Z"/><path d="M10 19a2 2 0 0 0 4 0"/></svg>
                        <span class="notif-dot"></span>
                    </button>

                    <?php if ($signedIn): ?>
                    <a href="?view=generator" class="btn btn-accent">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 8v8M10 8v8M13 8v8M17 8v8"/></svg>
                        Yeni Batch
                    </a>
                    <?php else: ?>
                    <div class="guest-actions">
                        <span>Demo: <span class="mono"><?= e((string)$demoRemaining) ?>/<?= e((string)$demoLimit) ?></span></span>
                        <a class="btn btn-secondary btn-sm" href="auth.php?mode=login">Giriş Yap</a>
                        <a class="btn btn-accent btn-sm" href="auth.php?mode=register">Ücretsiz Başla</a>
                    </div>
                    <?php endif; ?>
                </div>
            </header>

            <main class="main-content">
                <?php if ($view === 'dashboard'): ?>
                    <div class="page-inner">

                        <!-- Page header -->
                        <div class="page-head">
                            <div>
                                <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                                    <h1 class="page-head-title">İyi günler<?= $signedIn ? ', ' . e(explode(' ', (string)$user['name'])[0]) : '' ?></h1>
                                    <span class="badge success"><span class="dot"></span>Sistemler normal</span>
                                </div>
                                <p class="page-head-sub"><?= date('l, d F') ?> · <?= e($settings['workspace_name']) ?> · <span class="mono"><?= date('H:i') ?> GMT+3</span></p>
                            </div>
                            <div class="page-head-actions">
                                <a class="btn btn-secondary" href="?view=history">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    Geçmiş
                                </a>
                                <a class="btn btn-secondary" href="?view=jobs">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M21 8 12 3 3 8l9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v10"/></svg>
                                    Toplu İşler
                                </a>
                                <a class="btn btn-accent" href="?view=generator">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 8v8M10 8v8M13 8v8M17 8v8"/></svg>
                                    Barkod Oluştur
                                </a>
                            </div>
                        </div>

                        <!-- KPI cards -->
                        <div class="stat-grid">
                            <div class="card stat-card">
                                <div class="stat-header">
                                    <span class="stat-icon accent">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 8v8M10 8v8M13 8v8M17 8v8"/></svg>
                                    </span>
                                    <span class="stat-label">Bugün üretilen</span>
                                </div>
                                <div class="stat-body">
                                    <div>
                                        <div class="stat-value"><?= e(number_format($todayGenerated)) ?></div>
                                        <div class="stat-meta">
                                            <span class="stat-trend pos">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m5 15 7-7 7 7"/></svg>
                                                +12%
                                            </span>
                                            <span class="stat-sub">dünle karşılaştırıldı</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card stat-card">
                                <div class="stat-header">
                                    <span class="stat-icon">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    </span>
                                    <span class="stat-label">Bu ay üretilen</span>
                                </div>
                                <div class="stat-body">
                                    <div>
                                        <div class="stat-value"><?= e(number_format($monthGenerated)) ?></div>
                                        <div class="stat-meta">
                                            <span class="stat-sub"><?= e(number_format($usageLimit)) ?> aylık limit</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card stat-card">
                                <div class="stat-header">
                                    <span class="stat-icon">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M5 12.5 10 17 19 7.5"/></svg>
                                    </span>
                                    <span class="stat-label">Başarı oranı</span>
                                </div>
                                <div class="stat-body">
                                    <div>
                                        <div class="stat-value"><?= e((string)$successRate) ?>%</div>
                                        <div class="stat-meta">
                                            <span class="stat-trend pos">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m5 15 7-7 7 7"/></svg>
                                                +0.3%
                                            </span>
                                            <span class="stat-sub">doğrulama sonrası</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card stat-card">
                                <div class="stat-header">
                                    <span class="stat-icon">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 3 2 21h20L12 3Z"/><path d="M12 10v5"/><circle cx="12" cy="18" r=".6" fill="currentColor"/></svg>
                                    </span>
                                    <span class="stat-label">Hatalı satırlar</span>
                                </div>
                                <div class="stat-body">
                                    <div>
                                        <div class="stat-value"><?= e((string)$invalidRows) ?></div>
                                        <div class="stat-meta">
                                            <span class="stat-trend neg">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m5 9 7 7 7-7"/></svg>
                                                -8.2%
                                            </span>
                                            <span class="stat-sub">dünle karşılaştırıldı</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main dashboard grid -->
                        <div class="dash-grid">
                            <!-- Usage + Quick actions -->
                            <div class="card">
                                <div class="card-head">
                                    <div>
                                        <div class="card-title">Aylık Kullanım</div>
                                        <div class="card-sub">Kota takibi</div>
                                    </div>
                                    <span class="mono" style="font-size:22px;font-weight:600;color:var(--ink);"><?= e((string)$usagePercent) ?>%</span>
                                </div>
                                <div class="large-meter">
                                    <span style="width:<?= e((string)$usagePercent) ?>%;background:<?= $usagePercent > 85 ? 'var(--danger)' : ($usagePercent > 65 ? 'var(--warn)' : 'var(--accent)') ?>;"></span>
                                </div>
                                <div class="usage-meta">
                                    <span>Kullanılan: <?= e(number_format($usageUsed)) ?></span>
                                    <span>Kalan: <?= e(number_format($usageRemaining)) ?></span>
                                    <span>Limit: <?= e(number_format($usageLimit)) ?></span>
                                </div>
                            </div>

                            <!-- Quick actions -->
                            <div class="card">
                                <div class="card-head">
                                    <div class="card-title">Hızlı İşlemler</div>
                                    <span style="font-size:11px;color:var(--ink-4);">Klavye öncelikli</span>
                                </div>
                                <div class="quick-grid">
                                    <a class="quick-btn accent-q" href="?view=generator">
                                        <span class="quick-btn-ico">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 8v8M10 8v8M13 8v8M17 8v8"/></svg>
                                        </span>
                                        <span class="quick-btn-label">Barkod Oluştur</span>
                                        <span class="kbd">G</span>
                                    </a>
                                    <a class="quick-btn" href="?view=generator">
                                        <span class="quick-btn-ico">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 20V8"/><path d="m7 13 5-5 5 5"/><path d="M4 4h16"/></svg>
                                        </span>
                                        <span class="quick-btn-label">CSV Yükle</span>
                                        <span class="kbd">U</span>
                                    </a>
                                    <a class="quick-btn" href="?view=history">
                                        <span class="quick-btn-ico">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                        </span>
                                        <span class="quick-btn-label">Geçmişi Aç</span>
                                        <span class="kbd">H</span>
                                    </a>
                                    <a class="quick-btn" href="?view=api">
                                        <span class="quick-btn-ico">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M15 9V6a2 2 0 1 1 2 2h-3m-6 0V6a2 2 0 1 0-2 2h3m0 0v6m0 0v2a2 2 0 1 1-2 2v-3m0 0h6m0 0v2a2 2 0 1 0 2-2h-3"/></svg>
                                        </span>
                                        <span class="quick-btn-label">API Anahtarı</span>
                                        <span class="kbd">A</span>
                                    </a>
                                    <a class="quick-btn" href="?view=validation">
                                        <span class="quick-btn-ico">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/></svg>
                                        </span>
                                        <span class="quick-btn-label">GS1 Doğrula</span>
                                        <span class="kbd">V</span>
                                    </a>
                                    <a class="quick-btn" href="?view=templates">
                                        <span class="quick-btn-ico">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M20 12 12 20l-9-9V3h8l9 9Z"/><circle cx="7.5" cy="7.5" r="1.2" fill="currentColor"/></svg>
                                        </span>
                                        <span class="quick-btn-label">Şablon Kullan</span>
                                        <span class="kbd">T</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Second row -->
                        <div class="dash-grid">
                            <!-- Recent jobs -->
                            <div class="card activity-table" style="padding:0;overflow:hidden;">
                                <div class="activity-header">
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <span class="card-title">Son Batch İşler</span>
                                        <span class="badge success"><span class="dot"></span>Canlı</span>
                                    </div>
                                    <a href="?view=jobs" class="btn btn-ghost btn-sm">Tümünü gör
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                                    </a>
                                </div>
                                <?php foreach (array_slice($mockJobs, 0, 4) as $idx => $job): ?>
                                <div class="activity-row">
                                    <div class="activity-ico <?= $job['status'] === 'Completed' ? 'success' : ($job['status'] === 'Processing' ? 'info' : 'danger') ?>">
                                        <?php if ($job['status'] === 'Completed'): ?>
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12.5 10 17 19 7.5"/></svg>
                                        <?php elseif ($job['status'] === 'Processing'): ?>
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="animation:bos-spin 1s linear infinite"><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
                                        <?php else: ?>
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div style="font-size:13px;font-weight:500;color:var(--ink);display:flex;gap:6px;align-items:baseline;">
                                            <span><?= e($job['type']) ?></span>
                                            <span class="mono" style="font-size:11.5px;color:var(--ink-3);"><?= e((string)$job['records']) ?> kayıt</span>
                                        </div>
                                        <div style="font-size:11.5px;color:var(--ink-3);margin-top:1px;"><?= e($job['formats']) ?> · <?= e($job['valid']) ?> geçerli / <?= e((string)$job['invalid']) ?> hatalı</div>
                                    </div>
                                    <span class="mono" style="font-size:11px;color:var(--ink-4);"><?= e(substr($job['date'], 11)) ?></span>
                                    <span class="badge <?= $job['status'] === 'Completed' ? 'success' : ($job['status'] === 'Processing' ? 'info' : 'danger') ?>">
                                        <span class="dot"></span>
                                        <?= $job['status'] === 'Completed' ? 'Tamamlandı' : ($job['status'] === 'Processing' ? 'İşleniyor' : 'Başarısız') ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                                <div class="activity-footer">
                                    <span><?= count($mockJobs) ?> işlem gösteriliyor</span>
                                    <a href="?view=jobs" style="font-size:12px;color:var(--ink-2);font-weight:500;display:inline-flex;align-items:center;gap:4px;text-decoration:none;">Tümü
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                                    </a>
                                </div>
                            </div>

                            <!-- Right column: top types + low quota -->
                            <div style="display:flex;flex-direction:column;gap:14px;">
                                <div class="card">
                                    <div class="card-head">
                                        <div class="card-title">En Çok Kullanılan Tipler</div>
                                    </div>
                                    <div class="type-bars">
                                        <?php foreach ($topTypes as $type): ?>
                                        <div class="type-bar-row">
                                            <span class="type-bar-name"><?= e($type['name']) ?></span>
                                            <div class="type-bar-track">
                                                <div class="type-bar-fill" style="width:<?= e((string)$type['value']) ?>%"></div>
                                            </div>
                                            <span class="type-bar-pct"><?= e((string)$type['value']) ?>%</span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-head">
                                        <div class="card-title">Kota Durumu</div>
                                        <span class="badge <?= $usagePercent > 85 ? 'danger' : ($usagePercent > 65 ? 'warn' : 'success') ?>">
                                            <span class="dot"></span>
                                            <?= $usagePercent ?>%
                                        </span>
                                    </div>
                                    <div class="large-meter" style="height:6px;">
                                        <span style="width:<?= e((string)$usagePercent) ?>%;background:<?= $usagePercent > 85 ? 'var(--danger)' : ($usagePercent > 65 ? 'var(--warn)' : 'var(--accent)') ?>;"></span>
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px;">
                                        <div style="padding:10px;background:var(--bg-sunken);border-radius:8px;">
                                            <div style="font-size:10.5px;color:var(--ink-4);text-transform:uppercase;letter-spacing:0.05em;">Kalan</div>
                                            <div class="mono" style="font-size:18px;font-weight:600;color:var(--ink);margin-top:2px;"><?= e(number_format($usageRemaining)) ?></div>
                                        </div>
                                        <div style="padding:10px;background:var(--bg-sunken);border-radius:8px;">
                                            <div style="font-size:10.5px;color:var(--ink-4);text-transform:uppercase;letter-spacing:0.05em;">Yenileme</div>
                                            <div class="mono" style="font-size:14px;font-weight:500;color:var(--ink);margin-top:4px;"><?= date('d M') ?></div>
                                        </div>
                                    </div>
                                    <a href="?view=billing" class="btn btn-secondary" style="width:100%;justify-content:center;margin-top:14px;">Planı Yönet</a>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php elseif ($view === 'generator'): ?>
                    <div class="page-inner" style="padding-bottom:0;">
                        <div class="page-head">
                            <div>
                                <h1 class="page-head-title"><?= e($selectedLabel) ?> Oluşturucu</h1>
                                <p class="page-head-sub">Metin yapıştırın, TXT/CSV yükleyin, doğrulayın ve PNG · ZIP · PDF olarak indirin.</p>
                            </div>
                            <div class="page-head-actions">
                                <button class="btn btn-secondary" type="button" id="sampleDataBtn">Örnek Veri</button>
                                <button class="btn btn-outline" type="button" id="clearInputBtn">Temizle</button>
                            </div>
                        </div>
                    </div>

                    <?php if (!$signedIn): ?>
                    <div style=”padding:0 24px;”>
                        <div class=”demo-banner”>
                            <strong>Demo modu.</strong>
                            <span>Kayıt olmadan <?= e((string)$demoLimit) ?> barkod ücretsiz oluşturabilirsiniz. Kalan: <?= e((string)$demoRemaining) ?></span>
                            <a href=”auth.php?mode=register”>Ücretsiz hesap aç</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($limitNotice): ?>
                    <div style=”padding:0 24px;”>
                        <div class=”demo-banner warning”>
                            <strong>Demo limiti uygulandı.</strong>
                            <span><?= e($limitNotice) ?></span>
                            <a href=”checkout.php?plan=pro”>Planı yükselt</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form method=”post” action=”?view=generator” enctype=”multipart/form-data” class=”generator-grid” style=”padding:0 24px 24px;”>
                        <!-- Left: main panel -->
                        <div class=”generator-main”>

                            <!-- Big input panel -->
                            <div class=”input-panel”>
                                <!-- Left: data input -->
                                <div class=”input-panel-left”>
                                    <div class=”input-panel-label”>
                                        <svg width=”13” height=”13” viewBox=”0 0 24 24” fill=”none” stroke=”currentColor” stroke-width=”1.8” stroke-linecap=”round”><rect x=”2” y=”6” width=”20” height=”12” rx=”2”/><path d=”M6 10h.01M10 10h.01M14 10h.01M18 10h.01M6 14h12”/></svg>
                                        Veri Girişi
                                    </div>
                                    <div class=”input-panel-hint”>
                                        Her satıra bir barkod verisi girin veya dosya yükleyin.
                                        <span class=”live-count” style=”float:right;”><strong id=”lineCount”>0</strong> satır</span>
                                    </div>

                                    <div class=”big-textarea-wrap”>
                                        <textarea
                                            id=”input_text”
                                            name=”input_text”
                                            placeholder=”011234567890123421ABC93XYZ&#10;(01)12345678901234(21)SERIAL(91)A1(92)B2&#10;&#10;Her satır bir barkod verisi…”><?= e($_POST['input_text'] ?? '') ?></textarea>
                                    </div>

                                    <label class=”dropzone” for=”inputFile”>
                                        <input id=”inputFile” type=”file” name=”input_file” accept=”.txt,.csv,text/plain,text/csv”>
                                        <svg width=”16” height=”16” viewBox=”0 0 24 24” fill=”none” stroke=”currentColor” stroke-width=”1.6” stroke-linecap=”round”><path d=”M12 20V8”/><path d=”m7 13 5-5 5 5”/><path d=”M4 4h16”/></svg>
                                        <div>
                                            <div class=”dropzone-main”>TXT veya CSV sürükle &amp; bırak</div>
                                            <div class=”dropzone-sub”>CSV modunda ilk sütun barkod verisi olarak kullanılır.</div>
                                        </div>
                                    </label>

                                    <div class=”input-row-btns”>
                                        <button class=”btn btn-secondary btn-sm” type=”button” id=”sampleDataBtn”>Örnek Veri</button>
                                        <button class=”btn btn-ghost btn-sm” type=”button” id=”clearInputBtn”>Temizle</button>
                                        <div class=”mode-group”>
                                            <button type=”button” class=”mode-btn active” data-mode=”IN”>GS1</button>
                                            <button type=”button” class=”mode-btn” data-mode=”OUT”>Ham</button>
                                            <button type=”button” class=”mode-btn” data-mode=”CSV”>CSV</button>
                                        </div>
                                    </div>

                                    <div class=”shortcut-hints”>
                                        <span><span class=”kbd”>↵</span> gönder</span>
                                        <span><span class=”kbd”>⌘Z</span> geri al</span>
                                        <span><span class=”kbd”>/</span> ara</span>
                                        <span><span class=”kbd”>Esc</span> iptal</span>
                                    </div>
                                </div>

                                <!-- Right: result state -->
                                <div class=”input-panel-right” style=”background:<?= $summary ? 'var(--success-soft)' : 'var(--bg-sunken)' ?>;”>
                                    <?php if ($summary): ?>
                                    <div class=”result-state-header” style=”color:var(--success);”>
                                        <svg width=”14” height=”14” viewBox=”0 0 24 24” fill=”none” stroke=”currentColor” stroke-width=”2” stroke-linecap=”round”><path d=”M5 12.5 10 17 19 7.5”/></svg>
                                        Son işlem · tamamlandı
                                    </div>
                                    <div class=”result-state-icon success” style=”animation:bos-bump 0.4s ease-out;”>
                                        <svg width=”36” height=”36” viewBox=”0 0 24 24” fill=”none” stroke=”currentColor” stroke-width=”2.4” stroke-linecap=”round”><path d=”M5 12.5 10 17 19 7.5”/></svg>
                                    </div>
                                    <div>
                                        <div style=”font-size:20px;font-weight:600;letter-spacing:-0.02em;color:var(--ink);”><?= e((string)$summary['valid']) ?> barkod oluşturuldu</div>
                                        <div class=”mono” style=”font-size:13px;color:var(--ink-3);margin-top:4px;”><?= e($selectedLabel) ?></div>
                                    </div>
                                    <div class=”result-mini-stats”>
                                        <div>
                                            <div class=”result-mini-k”>Toplam</div>
                                            <div class=”result-mini-v”><?= e((string)$summary['total']) ?></div>
                                        </div>
                                        <div>
                                            <div class=”result-mini-k”>Geçerli</div>
                                            <div class=”result-mini-v” style=”color:var(--success);”><?= e((string)$summary['valid']) ?></div>
                                        </div>
                                        <div>
                                            <div class=”result-mini-k”>Hatalı</div>
                                            <div class=”result-mini-v” style=”color:<?= $summary['invalid'] > 0 ? 'var(--danger)' : 'var(--ink)' ?>;”><?= e((string)$summary['invalid']) ?></div>
                                        </div>
                                    </div>
                                    <div style=”display:flex;gap:8px;margin-top:auto;”>
                                        <button class=”btn btn-secondary btn-sm” type=”button” id=”downloadErrorCsv” <?= count($invalidResults) === 0 ? 'disabled' : '' ?>>Hata CSV</button>
                                        <button class=”btn btn-ghost btn-sm”>Düzenle</button>
                                    </div>
                                    <?php else: ?>
                                    <div class=”result-state-header” style=”color:var(--ink-3);”>
                                        <svg width=”14” height=”14” viewBox=”0 0 24 24” fill=”none” stroke=”currentColor” stroke-width=”1.6” stroke-linecap=”round”><path d=”M3 7V5a2 2 0 0 1 2-2h2”/><path d=”M17 3h2a2 2 0 0 1 2 2v2”/><path d=”M21 17v2a2 2 0 0 1-2 2h-2”/><path d=”M7 21H5a2 2 0 0 1-2-2v-2”/><path d=”M7 8v8M10 8v8M13 8v8M17 8v8”/></svg>
                                        Sonuç · bekleniyor
                                    </div>
                                    <div class=”result-state-icon neutral”>
                                        <svg width=”36” height=”36” viewBox=”0 0 24 24” fill=”none” stroke=”currentColor” stroke-width=”1.6” stroke-linecap=”round”><path d=”M3 7V5a2 2 0 0 1 2-2h2”/><path d=”M17 3h2a2 2 0 0 1 2 2v2”/><path d=”M21 17v2a2 2 0 0 1-2 2h-2”/><path d=”M7 21H5a2 2 0 0 1-2-2v-2”/><path d=”M7 8v8M10 8v8M13 8v8M17 8v8”/></svg>
                                    </div>
                                    <div style=”font-size:15px;font-weight:600;color:var(--ink);”>Çıktı bekleniyor</div>
                                    <div style=”font-size:13px;color:var(--ink-3);line-height:1.5;”>Veri girin ve barkodları oluştur düğmesine tıklayın.</div>
                                    <div style=”margin-top:auto;font-size:12px;color:var(--ink-4);”>Tip: <span class=”mono”><?= e($selectedLabel) ?></span></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Settings card -->
                            <div class=”card”>
                                <div class=”card-head”>
                                    <div>
                                        <div class=”card-title”>Ayarlar</div>
                                        <div class=”card-sub”>Üretim davranışı ve çıktı profili</div>
                                    </div>
                                    <button type=”submit” class=”btn btn-accent”>
                                        <svg width=”15” height=”15” viewBox=”0 0 24 24” fill=”none” stroke=”currentColor” stroke-width=”1.6” stroke-linecap=”round”><path d=”M3 7V5a2 2 0 0 1 2-2h2”/><path d=”M17 3h2a2 2 0 0 1 2 2v2”/><path d=”M21 17v2a2 2 0 0 1-2 2h-2”/><path d=”M7 21H5a2 2 0 0 1-2-2v-2”/><path d=”M7 8v8M10 8v8M13 8v8M17 8v8”/></svg>
                                        Barkodları Oluştur
                                    </button>
                                </div>
                                <div class=”settings-grid”>
                                    <label>
                                        <span>Barkod tipi</span>
                                        <select id=”barcodeType” name=”barcode_type”>
                                            <?php foreach ($barcodeGroups as $group => $types): ?>
                                                <optgroup label=”<?= e($group) ?>”>
                                                    <?php foreach ($types as $value => $label): ?>
                                                        <option value=”<?= e($value) ?>” <?= $selectedBarcode === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label>
                                        <span>Giriş modu</span>
                                        <select name=”input_mode”>
                                            <option value=”line” <?= $inputMode === 'line' ? 'selected' : '' ?>>Her satır bir barkod</option>
                                            <option value=”csv_first_column” <?= $inputMode === 'csv_first_column' ? 'selected' : '' ?>>CSV ilk sütun</option>
                                        </select>
                                    </label>
                                    <label>
                                        <span>Çıktı ölçeği</span>
                                        <input type=”number” name=”scale” min=”2” max=”8” value=”<?= e((string)$size) ?>”>
                                    </label>
                                    <label>
                                        <span>PDF sayfa başına</span>
                                        <select id=”pdfPerPage”>
                                            <option value=”1” selected>1 barkod</option>
                                            <option value=”2”>2 barkod</option>
                                            <option value=”3”>3 barkod</option>
                                            <option value=”4”>4 barkod</option>
                                        </select>
                                    </label>
                                </div>
                                <div class=”toggle-grid”>
                                    <label>
                                        <input type=”checkbox” name=”one_per_row” checked>
                                        <div>
                                            <span>Her satır için bir barkod üret</span>
                                            <small>Büyük listeler için toplu işleme uygundur.</small>
                                        </div>
                                    </label>
                                    <label>
                                        <input type=”checkbox” name=”escape_sequences” checked>
                                        <div>
                                            <span>Escape dizilerini değerlendir</span>
                                            <small>\F, \t ve \n stili girişleri işler.</small>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Results -->
                            <?php if ($summary): ?>
                            <div class=”card session-log” style=”padding:0;”>
                                <div class=”session-log-header”>
                                    <div>
                                        <span style=”font-size:13px;font-weight:600;color:var(--ink);”>Sonuçlar</span>
                                        <span class=”mono” style=”font-size:11px;color:var(--ink-4);margin-left:8px;”><?= e((string)$summary['total']) ?> kayıt</span>
                                    </div>
                                    <div style=”display:flex;gap:6px;”>
                                        <button class=”btn btn-ghost btn-sm” type=”button”>
                                            <svg width=”13” height=”13” viewBox=”0 0 24 24” fill=”none” stroke=”currentColor” stroke-width=”1.6” stroke-linecap=”round”><path d=”M3 5h18l-7 9v6l-4-2v-4L3 5Z”/></svg>
                                            Filtrele
                                        </button>
                                        <button class=”btn btn-ghost btn-sm” type=”button” id=”downloadErrorCsv” <?= count($invalidResults) === 0 ? 'disabled' : '' ?>>
                                            <svg width=”13” height=”13” viewBox=”0 0 24 24” fill=”none” stroke=”currentColor” stroke-width=”1.6” stroke-linecap=”round”><path d=”M12 4v12”/><path d=”m7 11 5 5 5-5”/><path d=”M4 20h16”/></svg>
                                            Hata CSV
                                        </button>
                                    </div>
                                </div>
                                <?php if (count($invalidResults) > 0): ?>
                                <div class=”error-summary” style=”margin:14px 16px 0;”>
                                    <strong>Hatalı satırlar var —</strong>
                                    <span>İlk hata: Satır <?= e((string)$invalidResults[0]['line_no']) ?> — <?= e((string)$invalidResults[0]['error']) ?></span>
                                </div>
                                <?php endif; ?>
                                <div class=”sr-data-table”>
                                    <table id=”resultTable” class=”data-table” data-bcid=”<?= e($selectedBarcode) ?>” data-scale=”<?= e((string)$size) ?>”>
                                        <thead>
                                            <tr>
                                                <th>Satır</th>
                                                <th>Durum</th>
                                                <th>Orijinal veri</th>
                                                <th>Encode edilen veri</th>
                                                <th>Hata</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($results as $index => $row): ?>
                                                <?php $encodeText = $shouldNormalizeGs1 ? (string)$row['ai_text'] : (string)$row['normalized']; ?>
                                                <tr class=”<?= $row['success'] ? 'is-valid' : 'is-invalid' ?>”
                                                    data-valid=”<?= $row['success'] ? '1' : '0' ?>”
                                                    data-line=”<?= e((string)$row['line_no']) ?>”
                                                    data-text=”<?= e($encodeText) ?>”
                                                    <?= $index > 500 ? 'data-deferred=”1”' : '' ?>>
                                                    <td class=”mono”><?= e((string)$row['line_no']) ?></td>
                                                    <td><?= $row['success'] ? '<span class=”badge success”><span class=”dot”></span>Geçerli</span>' : '<span class=”badge danger”><span class=”dot”></span>Hatalı</span>' ?></td>
                                                    <td class=”mono” style=”max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;”><?= e($row['original']) ?></td>
                                                    <td class=”mono” style=”max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;”><?= e($encodeText) ?></td>
                                                    <td style=”color:var(--danger);font-size:12px;”><?= e($row['error'] ?? $row['code_type'] ?? '') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class=”empty-state”>
                                <svg width=”36” height=”36” viewBox=”0 0 24 24” fill=”none” stroke=”currentColor” stroke-width=”1.2” stroke-linecap=”round” style=”color:var(--ink-5)”><path d=”M3 7V5a2 2 0 0 1 2-2h2”/><path d=”M17 3h2a2 2 0 0 1 2 2v2”/><path d=”M21 17v2a2 2 0 0 1-2 2h-2”/><path d=”M7 21H5a2 2 0 0 1-2-2v-2”/><path d=”M7 8v8M10 8v8M13 8v8M17 8v8”/></svg>
                                <strong>Henüz çıktı yok</strong>
                                <span>Veri girip “Barkodları Oluştur” düğmesine tıkladığınızda doğrulama ve indirme seçenekleri burada aktif olur.</span>
                            </div>
                            <?php endif; ?>

                        </div>

                        <!-- Right: aside -->
                        <div class=”generator-aside”>

                            <!-- Preview -->
                            <div class=”card”>
                                <div class=”card-head”>
                                    <div>
                                        <div class=”card-title”>Önizleme</div>
                                        <div class=”card-sub”>Seçilen geçerli satır canlı çizilir</div>
                                    </div>
                                </div>
                                <div class=”preview-frame”>
                                    <canvas id=”previewCanvas”></canvas>
                                    <span id=”emptyPreview”>Barkod önizlemesi</span>
                                </div>
                                <div class=”badge-row”>
                                    <span class=”badge”>GS1 Uyumlu</span>
                                    <span class=”badge”>ISO/IEC 16022</span>
                                    <span class=”badge”>API Ready</span>
                                </div>
                                <div class=”action-stack”>
                                    <button type=”button” id=”downloadSelected” class=”btn btn-secondary wide” disabled>
                                        <svg width=”14” height=”14” viewBox=”0 0 24 24” fill=”none” stroke=”currentColor” stroke-width=”1.6” stroke-linecap=”round”><path d=”M12 4v12”/><path d=”m7 11 5 5 5-5”/><path d=”M4 20h16”/></svg>
                                        PNG İndir
                                    </button>
                                    <button type=”button” id=”downloadZip” class=”btn btn-outline wide” disabled>
                                        <svg width=”14” height=”14” viewBox=”0 0 24 24” fill=”none” stroke=”currentColor” stroke-width=”1.6” stroke-linecap=”round”><path d=”M12 4v12”/><path d=”m7 11 5 5 5-5”/><path d=”M4 20h16”/></svg>
                                        ZIP İndir
                                    </button>
                                    <button type=”button” id=”downloadPdf” class=”btn btn-outline wide” disabled>
                                        <svg width=”14” height=”14” viewBox=”0 0 24 24” fill=”none” stroke=”currentColor” stroke-width=”1.6” stroke-linecap=”round”><path d=”M12 4v12”/><path d=”m7 11 5 5 5-5”/><path d=”M4 20h16”/></svg>
                                        PDF İndir
                                    </button>
                                    <a class=”btn btn-ghost wide” href=”?view=history”>Son çıktıları görüntüle</a>
                                </div>
                            </div>

                            <!-- Quota -->
                            <div class=”card”>
                                <div class=”card-head”>
                                    <div class=”card-title”>Kullanım Kotası</div>
                                    <span class=”badge <?= $usagePercent > 85 ? 'danger' : ($usagePercent > 65 ? 'warn' : 'success') ?>”><?= e((string)$usagePercent) ?>%</span>
                                </div>
                                <div class=”mono” style=”font-size:22px;font-weight:600;color:var(--ink);margin-bottom:10px;”>
                                    <?= e(number_format($usageUsed)) ?> <span style=”font-size:14px;color:var(--ink-4);”>/ <?= e(number_format($usageLimit)) ?></span>
                                </div>
                                <div class=”large-meter” style=”height:6px;”>
                                    <span style=”width:<?= e((string)$usagePercent) ?>%;background:<?= $usagePercent > 85 ? 'var(--danger)' : ($usagePercent > 65 ? 'var(--warn)' : 'var(--accent)') ?>;”></span>
                                </div>
                                <p style=”font-size:12.5px;color:var(--ink-3);margin:10px 0 14px;”>
                                    <?= $signedIn ? 'Pro pakette' : 'Demo modda' ?> <strong><?= e(number_format($usageRemaining)) ?></strong> barkod hakkınız kaldı.
                                </p>
                                <a href=”<?= $signedIn ? '?view=billing' : 'auth.php?mode=register' ?>” class=”btn btn-secondary wide”>
                                    <?= $signedIn ? 'Planı Yönet' : 'Ücretsiz Hesap Aç' ?>
                                </a>
                            </div>

                            <!-- Batch status -->
                            <div class=”card queue-card”>
                                <div class=”card-head”>
                                    <div class=”card-title”>Batch Kuyruğu</div>
                                    <span class=”badge”>Hazır</span>
                                </div>
                                <div class=”job-states”>
                                    <span>Bekliyor</span>
                                    <span>İşleniyor</span>
                                    <span>Tamamlandı</span>
                                    <span>Başarısız</span>
                                </div>
                                <p style=”font-size:12px;color:var(--ink-3);margin:10px 0 0;line-height:1.5;”>Büyük dosyalar için kuyruk altyapısı hazır.</p>
                            </div>

                        </div>
                    </form>
                <?php elseif ($view === 'billing'): ?>
                    <div class="page-inner">
                        <div class="page-head">
                            <div>
                                <h1 class="page-head-title">Kullanım &amp; Fatura</h1>
                                <p class="page-head-sub">Planınızı yönetin, fatura geçmişini görüntüleyin ve kota ayarlarını yapın.</p>
                            </div>
                            <div class="page-head-actions">
                                <button class="btn btn-secondary" type="button">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 4v12"/><path d="m7 11 5 5 5-5"/><path d="M4 20h16"/></svg>
                                    Fatura İndir
                                </button>
                            </div>
                        </div>

                        <!-- Usage overview -->
                        <div class="stat-grid" style="margin-bottom:20px;">
                            <div class="card stat-card">
                                <div class="stat-header">
                                    <span class="stat-icon accent">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 8v8M10 8v8M13 8v8M17 8v8"/></svg>
                                    </span>
                                    <span class="stat-label">Bu ay kullanım</span>
                                </div>
                                <div class="stat-body"><div>
                                    <div class="stat-value"><?= e(number_format($usageUsed)) ?></div>
                                    <div class="stat-meta"><span class="stat-sub">/ <?= e(number_format($usageLimit)) ?> limit</span></div>
                                </div></div>
                            </div>
                            <div class="card stat-card">
                                <div class="stat-header">
                                    <span class="stat-icon">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    </span>
                                    <span class="stat-label">Kalan hak</span>
                                </div>
                                <div class="stat-body"><div>
                                    <div class="stat-value"><?= e(number_format($usageRemaining)) ?></div>
                                    <div class="stat-meta"><span class="stat-sub">yenileme: <?= date('d M') ?></span></div>
                                </div></div>
                            </div>
                            <div class="card stat-card" style="grid-column:span 2;">
                                <div class="stat-header">
                                    <span class="stat-icon">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                                    </span>
                                    <span class="stat-label">Aylık kota kullanımı</span>
                                    <span class="badge <?= $usagePercent > 85 ? 'danger' : ($usagePercent > 65 ? 'warn' : 'success') ?>" style="margin-left:auto;"><?= $usagePercent ?>%</span>
                                </div>
                                <div class="large-meter" style="height:8px;margin-top:6px;">
                                    <span style="width:<?= e((string)$usagePercent) ?>%;background:<?= $usagePercent > 85 ? 'var(--danger)' : ($usagePercent > 65 ? 'var(--warn)' : 'var(--accent)') ?>;"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Plans -->
                        <div style="font-size:12px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--ink-3);margin-bottom:14px;">Planlar</div>
                        <div class="billing-plans">
                            <?php foreach ($settings['plans'] as $key => $plan): ?>
                                <article class="card plan-card <?= $key === 'pro' ? 'featured' : '' ?>">
                                    <div class="plan-name"><?= e($plan['name']) ?></div>
                                    <div class="plan-price"><?= e($plan['price']) ?> <span style="font-size:14px;font-weight:400;color:var(--ink-3);">/ay</span></div>
                                    <div class="plan-desc"><?= e($plan['limit']) ?> barkod/ay · <?= e($plan['description']) ?></div>
                                    <a class="btn <?= $key === 'pro' ? 'btn-accent' : 'btn-secondary' ?> wide" style="margin-top:8px;" href="<?= $key === 'free' ? 'auth.php?mode=register' : 'checkout.php?plan=' . e($key) ?>">
                                        <?= $key === 'free' ? 'Ücretsiz Başla' : ($key === 'enterprise' ? 'Teklif Al' : 'Satın Al') ?>
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>

                <?php elseif ($view === 'jobs'): ?>
                    <div class="page-inner">
                        <div class="page-head">
                            <div>
                                <h1 class="page-head-title">Toplu İşler</h1>
                                <p class="page-head-sub">Batch barkod işlemlerinizi takip edin, tekrar indirin veya yeniden çalıştırın.</p>
                            </div>
                            <div class="page-head-actions">
                                <button class="btn btn-secondary" type="button">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 4v12"/><path d="m7 11 5 5 5-5"/><path d="M4 20h16"/></svg>
                                    CSV Export
                                </button>
                                <a class="btn btn-accent" href="?view=generator">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                    Yeni Batch
                                </a>
                            </div>
                        </div>

                        <div class="card" style="padding:0;overflow:hidden;">
                            <div class="activity-header">
                                <span class="card-title">Tüm İşler</span>
                                <span class="badge success"><span class="dot"></span><?= count($mockJobs) ?> işlem</span>
                            </div>
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Barkod Tipi</th>
                                            <th>Kayıt</th>
                                            <th>Geçerli / Hatalı</th>
                                            <th>Formatlar</th>
                                            <th>Durum</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($mockJobs as $job): ?>
                                        <tr>
                                            <td class="mono" style="font-size:12px;color:var(--ink-3);"><?= e($job['date']) ?></td>
                                            <td style="font-weight:500;"><?= e($job['type']) ?></td>
                                            <td class="mono"><?= e((string)$job['records']) ?></td>
                                            <td>
                                                <span style="color:var(--success);font-weight:500;"><?= e((string)$job['valid']) ?></span>
                                                <span style="color:var(--ink-4);"> / </span>
                                                <span style="color:<?= $job['invalid'] > 0 ? 'var(--danger)' : 'var(--ink-3)' ?>;font-weight:<?= $job['invalid'] > 0 ? '500' : '400' ?>;"><?= e((string)$job['invalid']) ?></span>
                                            </td>
                                            <td style="color:var(--ink-3);font-size:12px;"><?= e($job['formats']) ?></td>
                                            <td>
                                                <span class="badge <?= $job['status'] === 'Completed' ? 'success' : ($job['status'] === 'Processing' ? 'info' : 'danger') ?>">
                                                    <span class="dot"></span>
                                                    <?= $job['status'] === 'Completed' ? 'Tamamlandı' : ($job['status'] === 'Processing' ? 'İşleniyor' : 'Başarısız') ?>
                                                </span>
                                            </td>
                                            <td><button class="mini-btn" type="button">İndir</button></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($view === 'history'): ?>
                    <div class="page-inner">
                        <div class="page-head">
                            <div>
                                <h1 class="page-head-title">Üretim Geçmişi</h1>
                                <p class="page-head-sub">Tüm barkod üretim kayıtlarınız kronolojik olarak listelenir.</p>
                            </div>
                            <div class="page-head-actions">
                                <button class="btn btn-secondary" type="button">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 4v12"/><path d="m7 11 5 5 5-5"/><path d="M4 20h16"/></svg>
                                    CSV Export
                                </button>
                            </div>
                        </div>

                        <div class="stat-grid" style="margin-bottom:20px;">
                            <div class="card stat-card">
                                <div class="stat-header"><span class="stat-label">Toplam üretim</span></div>
                                <div class="stat-body"><div><div class="stat-value"><?= e(number_format($monthGenerated)) ?></div></div></div>
                            </div>
                            <div class="card stat-card">
                                <div class="stat-header"><span class="stat-label">Başarı oranı</span></div>
                                <div class="stat-body"><div><div class="stat-value"><?= e((string)$successRate) ?>%</div></div></div>
                            </div>
                            <div class="card stat-card">
                                <div class="stat-header"><span class="stat-label">En çok kullanılan</span></div>
                                <div class="stat-body"><div><div class="stat-value" style="font-size:16px;">GS1 DataMatrix</div></div></div>
                            </div>
                            <div class="card stat-card">
                                <div class="stat-header"><span class="stat-label">Toplam iş sayısı</span></div>
                                <div class="stat-body"><div><div class="stat-value"><?= count($mockJobs) ?></div></div></div>
                            </div>
                        </div>

                        <div class="card" style="padding:0;overflow:hidden;">
                            <div class="activity-header">
                                <span class="card-title">Geçmiş İşlemler</span>
                            </div>
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Tarih &amp; Saat</th>
                                            <th>Barkod Tipi</th>
                                            <th>Kayıt</th>
                                            <th>Başarılı / Hatalı</th>
                                            <th>Formatlar</th>
                                            <th>Durum</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($mockJobs as $job): ?>
                                        <tr>
                                            <td class="mono" style="font-size:12px;color:var(--ink-3);"><?= e($job['date']) ?></td>
                                            <td style="font-weight:500;"><?= e($job['type']) ?></td>
                                            <td class="mono"><?= e((string)$job['records']) ?></td>
                                            <td>
                                                <span style="color:var(--success);font-weight:500;"><?= e((string)$job['valid']) ?></span>
                                                <span style="color:var(--ink-4);"> / </span>
                                                <span style="color:<?= $job['invalid'] > 0 ? 'var(--danger)' : 'var(--ink-3)' ?>;"><?= e((string)$job['invalid']) ?></span>
                                            </td>
                                            <td style="color:var(--ink-3);font-size:12px;"><?= e($job['formats']) ?></td>
                                            <td>
                                                <span class="badge <?= $job['status'] === 'Completed' ? 'success' : ($job['status'] === 'Processing' ? 'info' : 'danger') ?>">
                                                    <span class="dot"></span>
                                                    <?= $job['status'] === 'Completed' ? 'Tamamlandı' : ($job['status'] === 'Processing' ? 'İşleniyor' : 'Başarısız') ?>
                                                </span>
                                            </td>
                                            <td><button class="mini-btn" type="button">Tekrar İndir</button></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($view === 'validation'): ?>
                    <div class="page-inner">
                        <div class="page-head">
                            <div>
                                <h1 class="page-head-title">Doğrulama Raporları</h1>
                                <p class="page-head-sub">GS1, EAN/UPC ve özel format doğrulama sonuçlarınızı inceleyin.</p>
                            </div>
                            <div class="page-head-actions">
                                <a class="btn btn-accent" href="?view=generator">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/></svg>
                                    Yeni Doğrulama
                                </a>
                            </div>
                        </div>
                        <div class="placeholder-grid">
                            <article class="card">
                                <h2>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" style="color:var(--success);vertical-align:-3px;margin-right:6px;"><path d="M5 12.5 10 17 19 7.5"/></svg>
                                    GS1 Doğrulama
                                </h2>
                                <p>GS1-128, GS1 DataMatrix ve GS1 QR Code için GTIN, SSCC, AI parse ve checksum doğrulaması.</p>
                                <a href="?view=generator&barcode_type=gs1datamatrix" class="btn btn-secondary btn-sm" style="margin-top:12px;">Generator'da Dene</a>
                            </article>
                            <article class="card">
                                <h2>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" style="color:var(--accent-ink);vertical-align:-3px;margin-right:6px;"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M2 10h20"/></svg>
                                    EAN / UPC Kontrol
                                </h2>
                                <p>EAN-8, EAN-13, UPC-A ve UPC-E barkodları için check digit hesaplama ve format doğrulama.</p>
                                <a href="?view=generator&barcode_type=ean13" class="btn btn-secondary btn-sm" style="margin-top:12px;">EAN-13 Dene</a>
                            </article>
                            <article class="card">
                                <h2>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" style="color:var(--info);vertical-align:-3px;margin-right:6px;"><path d="M12 4v12"/><path d="m7 11 5 5 5-5"/><path d="M4 20h16"/></svg>
                                    Toplu CSV Doğrulama
                                </h2>
                                <p>Yüzlerce satırı aynı anda doğrulayın, hatalı satırları CSV olarak dışa aktarın ve düzeltin.</p>
                                <a href="?view=generator" class="btn btn-secondary btn-sm" style="margin-top:12px;">CSV Yükle</a>
                            </article>
                        </div>
                        <?php if (count($invalidResults) > 0): ?>
                        <div class="card" style="padding:0;overflow:hidden;margin-top:4px;">
                            <div class="activity-header">
                                <span class="card-title">Son Doğrulama Hataları</span>
                                <span class="badge danger"><span class="dot"></span><?= count($invalidResults) ?> hata</span>
                            </div>
                            <div class="table-wrap">
                                <table>
                                    <thead><tr><th>Satır</th><th>Orijinal Veri</th><th>Hata Açıklaması</th></tr></thead>
                                    <tbody>
                                        <?php foreach (array_slice($invalidResults, 0, 20) as $row): ?>
                                        <tr>
                                            <td class="mono" style="width:60px;"><?= e((string)$row['line_no']) ?></td>
                                            <td class="mono" style="color:var(--ink-2);"><?= e($row['original']) ?></td>
                                            <td style="color:var(--danger);font-size:12px;"><?= e((string)$row['error']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($view === 'templates'): ?>
                    <div class="page-inner">
                        <div class="page-head">
                            <div>
                                <h1 class="page-head-title">Şablonlar</h1>
                                <p class="page-head-sub">Kayıtlı barkod profillerinizi, favori ayarlarınızı ve export şablonlarınızı yönetin.</p>
                            </div>
                            <div class="page-head-actions">
                                <button class="btn btn-accent" type="button">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                                    Şablon Oluştur
                                </button>
                            </div>
                        </div>
                        <div class="placeholder-grid">
                            <article class="card">
                                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                                    <span class="stat-icon accent"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 8v8M10 8v8M13 8v8M17 8v8"/></svg></span>
                                    <div><div style="font-size:14px;font-weight:600;color:var(--ink);">GS1 DataMatrix Pro</div><span class="badge success">Aktif</span></div>
                                </div>
                                <p>GS1 DataMatrix, AI parse aktif, scale 4, ZIP + PDF çıktısı. Lojistik operasyonları için optimize edilmiş.</p>
                                <div style="display:flex;gap:6px;margin-top:12px;">
                                    <a href="?view=generator&barcode_type=gs1datamatrix" class="btn btn-accent btn-sm">Uygula</a>
                                    <button class="btn btn-ghost btn-sm" type="button">Düzenle</button>
                                </div>
                            </article>
                            <article class="card">
                                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                                    <span class="stat-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M2 10h20"/></svg></span>
                                    <div><div style="font-size:14px;font-weight:600;color:var(--ink);">EAN-13 Standart</div><span class="badge">Taslak</span></div>
                                </div>
                                <p>EAN-13 format, check digit otomatik, scale 3, PNG tek çıktı. Perakende ürün barkodları için hazır şablon.</p>
                                <div style="display:flex;gap:6px;margin-top:12px;">
                                    <a href="?view=generator&barcode_type=ean13" class="btn btn-secondary btn-sm">Uygula</a>
                                    <button class="btn btn-ghost btn-sm" type="button">Düzenle</button>
                                </div>
                            </article>
                            <article class="card">
                                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                                    <span class="stat-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M21 8 12 3 3 8l9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v10"/></svg></span>
                                    <div><div style="font-size:14px;font-weight:600;color:var(--ink);">Code 128 Toplu</div><span class="badge">Taslak</span></div>
                                </div>
                                <p>Code 128, ham veri modu, scale 5, CSV girişi, toplu ZIP çıktısı. Depo operasyonları için.</p>
                                <div style="display:flex;gap:6px;margin-top:12px;">
                                    <a href="?view=generator&barcode_type=code128" class="btn btn-secondary btn-sm">Uygula</a>
                                    <button class="btn btn-ghost btn-sm" type="button">Düzenle</button>
                                </div>
                            </article>
                        </div>
                    </div>

                <?php elseif ($view === 'api'): ?>
                    <div class="page-inner">
                        <div class="page-head">
                            <div>
                                <h1 class="page-head-title">API Anahtarları</h1>
                                <p class="page-head-sub">REST API erişimi için anahtarlar oluşturun, yönetin ve webhook endpoint'leri yapılandırın.</p>
                            </div>
                            <div class="page-head-actions">
                                <?php if (!$signedIn): ?>
                                <a class="btn btn-secondary" href="auth.php?mode=register">Hesap Gerekli</a>
                                <?php else: ?>
                                <button class="btn btn-accent" type="button">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                                    Yeni Anahtar
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="placeholder-grid" style="margin-bottom:20px;">
                            <article class="card">
                                <h2>REST API</h2>
                                <p>Her barkod tipi için <code style="font-family:var(--font-mono);font-size:12px;background:var(--bg-sunken);padding:1px 5px;border-radius:4px;">POST /api/v1/generate</code> endpoint'i ile programatik barkod üretimi. PNG, SVG veya base64 çıktı.</p>
                            </article>
                            <article class="card">
                                <h2>Webhooks</h2>
                                <p>Batch iş tamamlandığında, hata eşiği aşıldığında veya kota dolduğunda HTTP POST ile bildirim alın. HMAC imzalı güvenli payload.</p>
                            </article>
                            <article class="card">
                                <h2>Rate Limitleri</h2>
                                <p>Pro: 100 req/dk, Enterprise: sınırsız. Burst modu: 300 req/dk kısa süreli artışlara izin verir. X-RateLimit header'ları ile takip edin.</p>
                            </article>
                        </div>
                        <?php if ($signedIn): ?>
                        <div class="card" style="padding:0;overflow:hidden;">
                            <div class="activity-header">
                                <span class="card-title">API Anahtarlarım</span>
                            </div>
                            <div class="table-wrap">
                                <table>
                                    <thead><tr><th>Ad</th><th>Anahtar</th><th>Oluşturulma</th><th>Son Kullanım</th><th>İzinler</th><th></th></tr></thead>
                                    <tbody>
                                        <tr>
                                            <td style="font-weight:500;">Production Key</td>
                                            <td class="mono" style="font-size:12px;color:var(--ink-3);">bos_live_••••••••••••4f2a</td>
                                            <td style="font-size:12px;color:var(--ink-3);"><?= date('d M Y', strtotime('-30 days')) ?></td>
                                            <td style="font-size:12px;color:var(--ink-3);"><?= date('d M Y') ?></td>
                                            <td><span class="badge success">Tam Erişim</span></td>
                                            <td><button class="mini-btn" type="button">Kopyala</button></td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight:500;">Test Key</td>
                                            <td class="mono" style="font-size:12px;color:var(--ink-3);">bos_test_••••••••••••9c1b</td>
                                            <td style="font-size:12px;color:var(--ink-3);"><?= date('d M Y', strtotime('-7 days')) ?></td>
                                            <td style="font-size:12px;color:var(--ink-3);">—</td>
                                            <td><span class="badge">Sadece Okuma</span></td>
                                            <td><button class="mini-btn" type="button">Kopyala</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" style="color:var(--ink-5)"><path d="M15 9V6a2 2 0 1 1 2 2h-3m-6 0V6a2 2 0 1 0-2 2h3m0 0v6m0 0v2a2 2 0 1 1-2 2v-3m0 0h6m0 0v2a2 2 0 1 0 2-2h-3"/></svg>
                            <strong>API erişimi için hesap gerekli</strong>
                            <span>Ücretsiz hesap oluşturarak API anahtarı alabilir ve REST endpoint'leri kullanabilirsiniz.</span>
                            <a href="auth.php?mode=register" class="btn btn-accent btn-sm">Ücretsiz Başla</a>
                        </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($view === 'team'): ?>
                    <div class="page-inner">
                        <div class="page-head">
                            <div>
                                <h1 class="page-head-title">Ekip Üyeleri</h1>
                                <p class="page-head-sub">Çalışma alanınıza üye ekleyin, roller atayın ve erişim izinlerini yönetin.</p>
                            </div>
                            <div class="page-head-actions">
                                <?php if ($signedIn): ?>
                                <button class="btn btn-accent" type="button">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                                    Üye Davet Et
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($signedIn): ?>
                        <div class="card" style="padding:0;overflow:hidden;margin-bottom:16px;">
                            <div class="activity-header"><span class="card-title">Üyeler</span></div>
                            <div class="table-wrap">
                                <table>
                                    <thead><tr><th>Üye</th><th>E-posta</th><th>Rol</th><th>Son Giriş</th><th>Durum</th><th></th></tr></thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div style="display:flex;align-items:center;gap:8px;">
                                                    <div class="avatar"><?= e(strtoupper(substr((string)$user['name'], 0, 2))) ?></div>
                                                    <span style="font-weight:500;"><?= e($user['name']) ?></span>
                                                </div>
                                            </td>
                                            <td style="font-size:12px;color:var(--ink-3);"><?= e($user['email']) ?></td>
                                            <td><span class="badge accent">Admin</span></td>
                                            <td style="font-size:12px;color:var(--ink-3);"><?= date('d M Y') ?></td>
                                            <td><span class="badge success"><span class="dot"></span>Aktif</span></td>
                                            <td><button class="mini-btn" type="button">Düzenle</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="placeholder-grid">
                            <article class="card">
                                <h2>Roller &amp; İzinler</h2>
                                <p>Admin, Editor ve Viewer rolleri. Her rol için barkod üretim, API erişim ve fatura görüntüleme izinleri ayrı ayrı ayarlanabilir.</p>
                            </article>
                            <article class="card">
                                <h2>Davet Yönetimi</h2>
                                <p>Bekleyen davetleri görüntüleyin, iptal edin veya yeniden gönderin. Davet bağlantıları 48 saat geçerlidir.</p>
                            </article>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" style="color:var(--ink-5)"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.8-3.3 3.5-5.5 6.5-5.5s5.7 2.2 6.5 5.5"/><circle cx="17" cy="6" r="2.5"/><path d="M21.5 14c-.4-1.7-1.7-3-3.5-3"/></svg>
                            <strong>Ekip özelliği için giriş yapın</strong>
                            <span>Hesabınıza giriş yaparak ekip üyelerini yönetebilirsiniz.</span>
                            <a href="auth.php?mode=login" class="btn btn-accent btn-sm">Giriş Yap</a>
                        </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($view === 'settings'): ?>
                    <div class="page-inner">
                        <div class="page-head">
                            <div>
                                <h1 class="page-head-title">Ayarlar</h1>
                                <p class="page-head-sub">Uygulama tercihlerinizi, görünüm ve varsayılan çıktı ayarlarınızı yapılandırın.</p>
                            </div>
                            <div class="page-head-actions">
                                <a class="btn btn-secondary" href="admin.php">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/></svg>
                                    Sistem Ayarları
                                </a>
                            </div>
                        </div>
                        <div class="placeholder-grid" style="grid-template-columns:repeat(auto-fit,minmax(300px,1fr));">
                            <article class="card">
                                <h2>Görünüm</h2>
                                <p style="margin-bottom:14px;">Tema, yoğunluk ve arayüz tercihleri. Değişiklikler anında uygulanır ve tarayıcıda saklanır.</p>
                                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                    <button class="btn btn-secondary btn-sm" onclick="document.documentElement.setAttribute('data-theme','light');localStorage.setItem('bos-theme','light');">☀ Açık Tema</button>
                                    <button class="btn btn-secondary btn-sm" onclick="document.documentElement.setAttribute('data-theme','dark');localStorage.setItem('bos-theme','dark');">🌙 Koyu Tema</button>
                                </div>
                            </article>
                            <article class="card">
                                <h2>Varsayılan Çıktı</h2>
                                <p>PDF boyutu: <?= e((string)$settings['pdf_size_mm']) ?>mm · Kenar boşluğu: <?= e((string)$settings['pdf_margin_mm']) ?>mm · Varsayılan ölçek 4. Sistem ayarlarından değiştirin.</p>
                                <a href="admin.php" class="btn btn-secondary btn-sm" style="margin-top:12px;">Ayarla</a>
                            </article>
                            <article class="card">
                                <h2>Demo &amp; Kota</h2>
                                <p>Demo limiti: <?= e((string)$settings['demo_limit']) ?> barkod · Aylık limit: <?= e(number_format((int)$settings['monthly_limit'])) ?> barkod. Paket ayarlarına göre otomatik uygulanır.</p>
                                <a href="?view=billing" class="btn btn-secondary btn-sm" style="margin-top:12px;">Planı Değiştir</a>
                            </article>
                            <article class="card">
                                <h2>Workspace</h2>
                                <p>Workspace adı: <strong><?= e($settings['workspace_name']) ?></strong>. Logo, renk ve white-label ayarları Enterprise planda kullanılabilir.</p>
                                <a href="admin.php" class="btn btn-secondary btn-sm" style="margin-top:12px;">Yönetici Paneli</a>
                            </article>
                        </div>
                    </div>

                <?php elseif ($view === 'support'): ?>
                    <div class="page-inner">
                        <div class="page-head">
                            <div>
                                <h1 class="page-head-title">Yardım &amp; Destek</h1>
                                <p class="page-head-sub">Dokümantasyon, sık sorulan sorular ve destek talebi oluşturun.</p>
                            </div>
                        </div>
                        <div class="placeholder-grid" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr));margin-bottom:20px;">
                            <article class="card">
                                <h2>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" style="color:var(--accent-ink);vertical-align:-3px;margin-right:6px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
                                    Dokümantasyon
                                </h2>
                                <p>GS1 Application Identifier listesi, API referansı, barkod semboloji karşılaştırması ve entegrasyon kılavuzları.</p>
                                <a href="#" class="btn btn-secondary btn-sm" style="margin-top:12px;">Belgeleri Aç</a>
                            </article>
                            <article class="card">
                                <h2>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" style="color:var(--accent-ink);vertical-align:-3px;margin-right:6px;"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.8.4-1 .9-1 1.7"/><circle cx="12" cy="17" r=".7" fill="currentColor"/></svg>
                                    Sık Sorulan Sorular
                                </h2>
                                <p>GS1 AI parse nasıl çalışır? Hangi barkod tipi kullanmalıyım? ZIP ve PDF farkı nedir? Cevaplar burada.</p>
                                <a href="#" class="btn btn-secondary btn-sm" style="margin-top:12px;">SSS'e Git</a>
                            </article>
                            <article class="card">
                                <h2>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" style="color:var(--accent-ink);vertical-align:-3px;margin-right:6px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                    Destek Talebi
                                </h2>
                                <p>Çözülemeyen bir sorun için destek talebi açın. Pro ve Enterprise müşterilerine 4 saat içinde yanıt verilir.</p>
                                <a href="mailto:support@barcodeos.com" class="btn btn-accent btn-sm" style="margin-top:12px;">Talep Aç</a>
                            </article>
                        </div>

                        <div class="card">
                            <div class="card-head">
                                <div class="card-title">Hızlı Başlangıç Kılavuzu</div>
                            </div>
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-top:4px;">
                                <?php
                                $steps = [
                                    ['num'=>'1','title'=>'Veri Girin','desc'=>'Barkod Generator sayfasına gidin ve metin alanına barkod verilerinizi yapıştırın.'],
                                    ['num'=>'2','title'=>'Tip Seçin','desc'=>'Sol menüdeki Sembolojiler panelinden veya Ayarlar kartından barkod tipini belirleyin.'],
                                    ['num'=>'3','title'=>'Oluşturun','desc'=>'"Barkodları Oluştur" düğmesine tıklayın, sonuçlar anında görüntülenir.'],
                                    ['num'=>'4','title'=>'İndirin','desc'=>'PNG, ZIP veya PDF olarak dışa aktarın. Hatalı satırları CSV olarak inceleyin.'],
                                ];
                                foreach ($steps as $step): ?>
                                <div style="display:flex;gap:12px;">
                                    <div style="width:28px;height:28px;border-radius:99px;background:var(--accent-soft);color:var(--accent-ink);font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><?= $step['num'] ?></div>
                                    <div>
                                        <div style="font-size:13px;font-weight:600;color:var(--ink);margin-bottom:3px;"><?= $step['title'] ?></div>
                                        <div style="font-size:12px;color:var(--ink-3);line-height:1.5;"><?= $step['desc'] ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Fallback for unknown views -->
                    <div class="page-inner">
                        <div class="page-head">
                            <div>
                                <h1 class="page-head-title"><?= e($mainMenu[$view] ?? 'Sayfa') ?></h1>
                                <p class="page-head-sub">Bu sayfa yakında aktif olacak.</p>
                            </div>
                        </div>
                        <div class="empty-state">
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" style="color:var(--ink-5)"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>
                            <strong>Yapım aşamasında</strong>
                            <span>Bu modül yakında kullanıma açılacak. Sorularınız için destek sayfasını ziyaret edin.</span>
                            <a href="?view=support" class="btn btn-secondary btn-sm">Destek</a>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://unpkg.com/bwip-js/dist/bwip-js-min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
