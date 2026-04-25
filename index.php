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
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($settings['app_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body data-pdf-size="<?= e((string)$settings['pdf_size_mm']) ?>" data-pdf-margin="<?= e((string)$settings['pdf_margin_mm']) ?>">
    <div class="saas-shell">
        <aside class="sidebar">
            <a class="brand-block" href="?view=dashboard">
                <span class="brand-mark">DM</span>
                <span>
                    <strong><?= e($settings['app_name']) ?></strong>
                    <small><?= e($settings['tagline'] ?? 'All Your Barcode Tools. One Platform.') ?></small>
                </span>
            </a>

            <nav class="main-menu" aria-label="SaaS menüsü">
                <?php foreach ($mainMenu as $key => $label): ?>
                    <a class="<?= $view === $key ? 'active' : '' ?>" href="<?= ($key === 'admin' || $key === 'settings') ? 'admin.php' : '?view=' . e($key) ?>">
                        <span><?= e(substr($label, 0, 1)) ?></span><?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="symbology-panel" id="symbologyPanel">
                <button class="symbology-toggle" type="button" id="symbologyToggle" aria-expanded="false" aria-controls="symbologyList">
                    <span>Symbologies</span>
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

            <div class="quota-card">
                <div>
                    <span><?= $signedIn ? e(($user['plan'] ?? 'Pro') . ' Plan') : 'Demo Plan' ?></span>
                    <strong><?= e((string)$usageRemaining) ?></strong>
                    <small>kalan barkod</small>
                </div>
                <div class="meter"><span style="width: <?= e((string)$usagePercent) ?>%"></span></div>
                    <a href="<?= $signedIn ? '?view=billing' : 'auth.php?mode=register' ?>"><?= $signedIn ? 'Usage & Billing' : 'Create free account' ?></a>
            </div>
        </aside>

        <div class="content-shell">
            <header class="topbar">
                <div class="trust-strip">
                    <?php foreach ($settings['trust_badges'] as $badge): ?>
                        <span><?= e($badge) ?></span>
                    <?php endforeach; ?>
                </div>
                <form class="language-select" method="get">
                    <input type="hidden" name="view" value="<?= e($view) ?>">
                    <label>
                        <span>Language</span>
                        <select name="lang" onchange="this.form.submit()">
                            <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>EN</option>
                            <option value="tr" <?= $lang === 'tr' ? 'selected' : '' ?>>TR</option>
                        </select>
                    </label>
                </form>
                <?php if ($signedIn): ?>
                    <div class="user-menu">
                        <a href="?view=support">Help</a>
                        <button type="button"><?= e(strtoupper(substr((string)$user['name'], 0, 2))) ?></button>
                        <div>
                            <strong><?= e($user['name']) ?></strong>
                        <span><?= e($settings['workspace_name']) ?> · <a href="auth.php?action=logout">Logout</a></span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="guest-actions">
                        <span>Demo: <?= e((string)$demoRemaining) ?>/<?= e((string)$demoLimit) ?> credits left</span>
                        <a class="btn outline" href="auth.php?mode=login">Sign In</a>
                        <a class="btn primary" href="auth.php?mode=register">Start Free</a>
                    </div>
                <?php endif; ?>
            </header>

            <main class="main-content">
                <?php if ($view === 'dashboard'): ?>
                    <section class="page-head">
                        <div>
                            <p class="eyebrow">Workspace Dashboard</p>
                            <h1>Enterprise barcode operations center</h1>
                            <p>Manage bulk generation, validation reports, API access and subscription limits from one professional workspace.</p>
                        </div>
                        <div class="head-actions">
                            <a class="btn secondary" href="?view=history">Open History</a>
                            <a class="btn primary" href="?view=generator">Create Barcode</a>
                        </div>
                    </section>

                    <section class="metric-grid">
                        <article><span>Generated today</span><strong><?= e((string)$todayGenerated) ?></strong><small>+12% vs yesterday</small></article>
                        <article><span>Generated this month</span><strong><?= e((string)$monthGenerated) ?></strong><small><?= e((string)$usageLimit) ?> monthly limit</small></article>
                        <article><span>Success rate</span><strong><?= e((string)$successRate) ?>%</strong><small>after validation</small></article>
                        <article><span>Invalid rows</span><strong><?= e((string)$invalidRows) ?></strong><small>reportable</small></article>
                    </section>

                    <section class="dashboard-grid">
                        <article class="card usage-wide">
                            <div class="card-head">
                                <div><h2>Usage Limit</h2><p>Monthly quota tracking</p></div>
                                <strong><?= e((string)$usagePercent) ?>%</strong>
                            </div>
                            <div class="large-meter"><span style="width: <?= e((string)$usagePercent) ?>%"></span></div>
                            <div class="usage-meta">
                                <span>Used: <?= e((string)$usageUsed) ?></span>
                                <span>Remaining: <?= e((string)$usageRemaining) ?></span>
                                <span>Limit: <?= e((string)$usageLimit) ?></span>
                            </div>
                        </article>

                        <article class="card">
                            <div class="card-head"><h2>Quick Actions</h2></div>
                            <div class="quick-grid">
                                <a href="?view=generator">Create barcode</a>
                                <a href="?view=generator">Upload CSV</a>
                                <a href="?view=history">Open history</a>
                                <a href="?view=api">Create API key</a>
                            </div>
                        </article>

                        <article class="card">
                            <div class="card-head"><h2>Top barcode types</h2></div>
                            <div class="type-bars">
                                <?php foreach ($topTypes as $type): ?>
                                    <div><span><?= e($type['name']) ?></span><div><i style="width: <?= e((string)$type['value']) ?>%"></i></div><b><?= e((string)$type['value']) ?>%</b></div>
                                <?php endforeach; ?>
                            </div>
                        </article>

                        <article class="card table-card">
                            <div class="card-head"><h2>Recent batch jobs</h2><a href="?view=jobs">View all</a></div>
                            <div class="table-wrap compact">
                                <table>
                                    <thead><tr><th>Tarih</th><th>Tip</th><th>Kayıt</th><th>Durum</th></tr></thead>
                                    <tbody>
                                        <?php foreach (array_slice($mockJobs, 0, 3) as $job): ?>
                                            <tr><td><?= e($job['date']) ?></td><td><?= e($job['type']) ?></td><td><?= e((string)$job['records']) ?></td><td><span class="status <?= e(strtolower($job['status'])) ?>"><?= e($job['status']) ?></span></td></tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </article>
                    </section>
                <?php elseif ($view === 'generator'): ?>
                    <section class="page-head generator-head">
                        <div>
                            <p class="eyebrow">Barcode Generator</p>
                            <h1><?= e($selectedLabel) ?> generation</h1>
                            <p>Paste text, upload TXT/CSV files, validate rows and download outputs as PNG, ZIP or square PDF.</p>
                        </div>
                        <div class="head-actions">
                            <button class="btn secondary" type="button" id="sampleDataBtn">Insert Sample</button>
                            <button class="btn outline" type="button" id="clearInputBtn">Clear</button>
                        </div>
                    </section>

                    <?php if (!$signedIn): ?>
                        <div class="demo-banner">
                            <strong>You are in demo mode.</strong>
                            <span>You can generate <?= e((string)$demoLimit) ?> barcodes without signing in. Remaining credits: <?= e((string)$demoRemaining) ?>.</span>
                            <a href="auth.php?mode=register">Create a free account</a>
                        </div>
                    <?php endif; ?>

                    <?php if ($limitNotice): ?>
                        <div class="demo-banner warning">
                            <strong>Demo limit applied.</strong>
                            <span><?= e($limitNotice) ?></span>
                            <a href="checkout.php?plan=pro">Upgrade plan</a>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="?view=generator" enctype="multipart/form-data" class="generator-grid">
                        <section class="generator-main">
                            <article class="card data-card">
                                <div class="card-head">
                                    <div><h2>Data Input</h2><p>Enter one barcode per row or upload a TXT/CSV file.</p></div>
                                    <span class="live-count"><strong id="lineCount">0</strong> data rows</span>
                                </div>
                                <textarea id="input_text" name="input_text" rows="12" placeholder="011234567890123421ABC93XYZ&#10;(01)12345678901234(21)SERIAL(91)A1(92)B2"><?= e($_POST['input_text'] ?? '') ?></textarea>
                                <label class="dropzone" for="inputFile">
                                    <input id="inputFile" type="file" name="input_file" accept=".txt,.csv,text/plain,text/csv">
                                    <span>Drop TXT or CSV file here</span>
                                    <small>In CSV mode, the first column is used as barcode data.</small>
                                </label>
                            </article>

                            <article class="card settings-card">
                                <div class="card-head"><div><h2>Settings</h2><p>Generation behavior and output profile.</p></div></div>
                                <div class="settings-grid">
                                    <label><span>Barcode type</span><select id="barcodeType" name="barcode_type"><?php foreach ($barcodeGroups as $group => $types): ?><optgroup label="<?= e($group) ?>"><?php foreach ($types as $value => $label): ?><option value="<?= e($value) ?>" <?= $selectedBarcode === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select></label>
                                    <label><span>Input mode</span><select name="input_mode"><option value="line" <?= $inputMode === 'line' ? 'selected' : '' ?>>Her satır bir barkod</option><option value="csv_first_column" <?= $inputMode === 'csv_first_column' ? 'selected' : '' ?>>CSV ilk sütun</option></select></label>
                                    <label><span>Output scale</span><input type="number" name="scale" min="2" max="8" value="<?= e((string)$size) ?>"></label>
                                    <label><span>PDF per page</span><select id="pdfPerPage"><option value="1" selected>1 barkod</option><option value="2">2 barkod</option><option value="3">3 barkod</option><option value="4">4 barkod</option></select></label>
                                </div>
                                <div class="toggle-grid">
                                    <label><input type="checkbox" name="one_per_row" checked><span>Generate one barcode per row</span><small>Ready for batch processing on large lists.</small></label>
                                    <label><input type="checkbox" name="escape_sequences" checked><span>Evaluate escape sequences</span><small>Handles \F, \t and \n style input.</small></label>
                                </div>
                                <button class="btn primary wide" type="submit">Generate Barcodes</button>
                            </article>

                            <?php if ($summary): ?>
                                <article class="card results-card">
                                    <div class="card-head">
                                        <div><h2>Generation Summary</h2><p>Barcodes are ready. Download actions are active in the preview panel.</p></div>
                                        <button class="btn outline" type="button" id="downloadErrorCsv" <?= count($invalidResults) === 0 ? 'disabled' : '' ?>>Error Report CSV</button>
                                    </div>
                                    <div class="summary-grid">
                                        <div><span>Toplam</span><strong><?= e((string)$summary['total']) ?></strong></div>
                                        <div><span>Geçerli</span><strong><?= e((string)$summary['valid']) ?></strong></div>
                                        <div><span>Hatalı</span><strong><?= e((string)$summary['invalid']) ?></strong></div>
                                        <div><span>Kaynak</span><strong><?= e($summary['source_type']) ?></strong></div>
                                    </div>
                                    <?php if (count($invalidResults) > 0): ?>
                                        <div class="error-summary">
                                            <strong>Hatalı satırlar var</strong>
                                            <span>Detayları CSV olarak indirebilirsiniz. İlk hata: Satır <?= e((string)$invalidResults[0]['line_no']) ?> - <?= e((string)$invalidResults[0]['error']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="table-wrap sr-data-table">
                                        <table id="resultTable" data-bcid="<?= e($selectedBarcode) ?>" data-scale="<?= e((string)$size) ?>">
                                            <thead><tr><th>Satır</th><th>Durum</th><th>Orijinal veri</th><th>Encode edilen veri</th><th>Hata mesajı</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($results as $index => $row): ?>
                                                    <?php $encodeText = $shouldNormalizeGs1 ? (string)$row['ai_text'] : (string)$row['normalized']; ?>
                                                    <tr class="<?= $row['success'] ? 'is-valid' : 'is-invalid' ?>" data-valid="<?= $row['success'] ? '1' : '0' ?>" data-line="<?= e((string)$row['line_no']) ?>" data-text="<?= e($encodeText) ?>" <?= $index > 500 ? 'data-deferred="1"' : '' ?>>
                                                        <td><?= e((string)$row['line_no']) ?></td>
                                                        <td><?= $row['success'] ? '<span class="status completed">Valid</span>' : '<span class="status failed">Invalid</span>' ?></td>
                                                        <td><?= e($row['original']) ?></td>
                                                        <td><?= e($encodeText) ?></td>
                                                        <td><?= e($row['error'] ?? $row['code_type'] ?? '') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </article>
                            <?php else: ?>
                                <article class="empty-state">
                                    <strong>Henüz çıktı yok</strong>
                                    <span>Veri girip “Barkodları Oluştur” dediğinizde validasyon ve indirme seçenekleri burada aktif olur.</span>
                                </article>
                            <?php endif; ?>
                        </section>

                        <aside class="generator-aside">
                            <article class="card preview-card">
                                <div class="card-head"><div><h2>Önizleme</h2><p>Seçilen geçerli satır canlı çizilir.</p></div></div>
                                <div class="preview-frame"><canvas id="previewCanvas"></canvas><span id="emptyPreview">Barkod önizlemesi</span></div>
                                <div class="badge-row"><span>GS1 Uyumlu</span><span>ISO/IEC 16022</span><span>API Ready</span></div>
                                <div class="action-stack">
                                    <button type="button" id="downloadSelected" class="btn secondary wide" disabled>PNG indir</button>
                                    <button type="button" id="downloadZip" class="btn outline wide" disabled>ZIP indir</button>
                                    <button type="button" id="downloadPdf" class="btn outline wide" disabled>PDF indir</button>
                                    <a class="btn ghost wide" href="?view=history">Son çıktıları görüntüle</a>
                                </div>
                            </article>

                            <article class="card quota-summary">
                                <div class="card-head"><h2>Kullanım Kotası</h2></div>
                                <strong><?= e((string)$usageUsed) ?> / <?= e((string)$usageLimit) ?></strong>
                                <div class="large-meter"><span style="width: <?= e((string)$usagePercent) ?>%"></span></div>
                                <p><?= $signedIn ? 'Pro pakette' : 'Demo modda' ?> <?= e((string)$usageRemaining) ?> barkod hakkınız kaldı.</p>
                            </article>

                            <article class="card queue-card">
                                <div class="card-head"><h2>Batch Job Ready</h2></div>
                                <div class="job-states"><span>Pending</span><span>Processing</span><span>Completed</span><span>Failed</span></div>
                                <p>Büyük dosyalar için kuyruk arayüzü hazır; backend queue eklenince aynı panel kullanılacak.</p>
                            </article>
                        </aside>
                    </form>
                <?php else: ?>
                    <section class="page-head">
                        <div><p class="eyebrow"><?= e($mainMenu[$view] ?? 'Workspace') ?></p><h1><?= e($mainMenu[$view] ?? 'Workspace') ?></h1><p>Bu bölüm SaaS ürün yapısı için hazırlandı. Backend modülü bağlandığında aynı arayüz canlı veriye geçecek.</p></div>
                    </section>
                    <?php if ($view === 'billing'): ?>
                        <section class="billing-plans">
                            <?php foreach ($settings['plans'] as $key => $plan): ?>
                                <article class="card plan-card <?= $key === 'pro' ? 'featured' : '' ?> <?= $key === 'enterprise' ? 'dark' : '' ?>">
                                    <span><?= e($plan['name']) ?></span>
                                    <strong><?= e($plan['price']) ?></strong>
                                    <p><?= e($plan['limit']) ?> · <?= e($plan['description']) ?></p>
                                    <a class="btn <?= $key === 'pro' ? 'primary' : 'outline' ?> wide" href="<?= $key === 'free' ? 'auth.php?mode=register' : 'checkout.php?plan=' . e($key) ?>"><?= $key === 'free' ? 'Başla' : ($key === 'enterprise' ? 'Teklif Al' : 'Satın Al') ?></a>
                                </article>
                            <?php endforeach; ?>
                        </section>
                    <?php else: ?>
                        <section class="placeholder-grid">
                            <article class="card"><h2>Saved templates</h2><p>Sık kullanılan barkod ayarları, favoriler ve export profilleri burada listelenecek.</p></article>
                            <article class="card"><h2>API & Webhooks</h2><p>API key, webhook endpointleri, audit log ve entegrasyon ayarları için placeholder.</p></article>
                            <article class="card"><h2>Team / Company</h2><p>Takım üyeleri, roller, şirket profili, white label logo ve faturalama bilgileri.</p></article>
                        </section>
                    <?php endif; ?>
                    <section class="card table-card">
                        <div class="card-head"><h2>İşlem geçmişi</h2><button class="btn outline" type="button">Export CSV</button></div>
                        <div class="table-wrap compact">
                            <table>
                                <thead><tr><th>Oluşturma tarihi</th><th>Barkod tipi</th><th>Kayıt</th><th>Başarılı / Hatalı</th><th>Formatlar</th><th>Durum</th><th></th></tr></thead>
                                <tbody>
                                    <?php foreach ($mockJobs as $job): ?>
                                        <tr><td><?= e($job['date']) ?></td><td><?= e($job['type']) ?></td><td><?= e((string)$job['records']) ?></td><td><?= e((string)$job['valid']) ?> / <?= e((string)$job['invalid']) ?></td><td><?= e($job['formats']) ?></td><td><span class="status <?= e(strtolower($job['status'])) ?>"><?= e($job['status']) ?></span></td><td><button class="mini-btn" type="button">Tekrar indir</button></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
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
