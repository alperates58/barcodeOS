<?php

declare(strict_types=1);

namespace Dmc;

use Throwable;

final class Settings
{
    public static function defaults(): array
    {
        return [
            'app_name' => 'BarcodeOS.com',
            'tagline' => 'All Your Barcode Tools. One Platform.',
            'workspace_name' => 'Pro Workspace',
            'demo_limit' => 10,
            'monthly_limit' => 5000,
            'pdf_size_mm' => 100,
            'pdf_margin_mm' => 4,
            'trust_badges' => ['GS1 Ready', 'ISO/IEC 16022 Compatible', 'Bulk Processing', 'API Ready'],
            'plans' => [
                'free' => ['name' => 'Free', 'price' => '$0', 'limit' => '10 demo barkod', 'description' => 'Demo kullanım ve temel PNG indirme.'],
                'pro' => ['name' => 'Pro', 'price' => '$49 / ay', 'limit' => '5.000 barkod / ay', 'description' => 'ZIP/PDF, geçmiş ve template desteği.'],
                'business' => ['name' => 'Business', 'price' => '$129 / ay', 'limit' => '50.000 barkod / ay', 'description' => 'API keys, webhook ve takım üyeleri.'],
                'enterprise' => ['name' => 'Enterprise', 'price' => 'Özel fiyat', 'limit' => 'Sınırsız / SLA', 'description' => 'White label, SLA ve özel kurulum.'],
            ],
        ];
    }

    public static function all(): array
    {
        $settings = self::defaults();
        $path = APP_ROOT . '/config/settings.php';
        $custom = is_file($path) ? require $path : [];
        $settings = array_replace_recursive($settings, is_array($custom) ? $custom : []);

        try {
            if (function_exists('app_is_installed') && app_is_installed()) {
                $pdo = Database::connect(app_config());

                $rows = $pdo->query('SELECT setting_key, setting_value, value_type FROM app_settings')->fetchAll();
                foreach ($rows as $row) {
                    $settings[$row['setting_key']] = self::decodeValue($row['setting_value'], $row['value_type']);
                }

                $plans = $pdo->query('SELECT plan_key, name, price_label, monthly_limit, description FROM plans WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
                if ($plans) {
                    $settings['plans'] = [];
                    foreach ($plans as $plan) {
                        $settings['plans'][$plan['plan_key']] = [
                            'name' => $plan['name'],
                            'price' => $plan['price_label'],
                            'limit' => $plan['monthly_limit'] === null ? 'Custom' : number_format((int)$plan['monthly_limit'], 0, ',', '.') . ' barkod / ay',
                            'description' => (string)$plan['description'],
                        ];
                    }
                }
            }
        } catch (Throwable) {
            // File defaults keep the app usable before installation or if DB is unavailable.
        }

        return $settings;
    }

    public static function save(array $settings): void
    {
        $path = APP_ROOT . '/config/settings.php';
        $content = "<?php\n\nreturn " . var_export($settings, true) . ";\n";
        file_put_contents($path, $content);

        try {
            if (function_exists('app_is_installed') && app_is_installed()) {
                self::saveToDatabase($settings);
            }
        } catch (Throwable) {
            // File save already succeeded; DB can be repaired from admin/install later.
        }
    }

    public static function seedDatabase(array $settings): void
    {
        self::saveToDatabase($settings);
    }

    private static function saveToDatabase(array $settings): void
    {
        $pdo = Database::connect(app_config());

        $settingKeys = ['app_name', 'workspace_name', 'demo_limit', 'monthly_limit', 'pdf_size_mm', 'pdf_margin_mm', 'trust_badges'];
        $stmt = $pdo->prepare('REPLACE INTO app_settings (setting_key, setting_value, value_type) VALUES (?, ?, ?)');

        foreach ($settingKeys as $key) {
            $value = $settings[$key] ?? null;
            $type = is_array($value) ? 'json' : (is_int($value) ? 'int' : 'string');
            $stmt->execute([$key, self::encodeValue($value, $type), $type]);
        }

        $planStmt = $pdo->prepare(
            'INSERT INTO plans (plan_key, name, price_label, monthly_limit, description, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?, 1, ?)
             ON DUPLICATE KEY UPDATE name = VALUES(name), price_label = VALUES(price_label), monthly_limit = VALUES(monthly_limit), description = VALUES(description), is_active = 1, sort_order = VALUES(sort_order)'
        );

        $order = 0;
        foreach (($settings['plans'] ?? []) as $key => $plan) {
            $monthlyLimit = self::parseMonthlyLimit((string)($plan['limit'] ?? ''));
            $planStmt->execute([
                $key,
                $plan['name'] ?? $key,
                $plan['price'] ?? '',
                $monthlyLimit,
                $plan['description'] ?? '',
                $order++,
            ]);
        }
    }

    private static function encodeValue(mixed $value, string $type): string
    {
        if ($type === 'json') {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
        }

        return (string)$value;
    }

    private static function decodeValue(string $value, string $type): mixed
    {
        if ($type === 'int') {
            return (int)$value;
        }
        if ($type === 'json') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return $value;
    }

    private static function parseMonthlyLimit(string $label): ?int
    {
        if (stripos($label, 'sınırsız') !== false || stripos($label, 'unlimited') !== false || stripos($label, 'custom') !== false) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $label);
        return $digits === '' ? null : (int)$digits;
    }
}
