<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_PATH', APP_ROOT . '/app');
define('CONFIG_PATH', APP_ROOT . '/config/config.php');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'tr'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

spl_autoload_register(function (string $class): void {
    $prefix = 'Dmc\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

function app_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $example = APP_ROOT . '/config/config.example.php';
    $config = is_file(CONFIG_PATH) ? require CONFIG_PATH : require $example;

    return $config;
}

function app_is_installed(): bool
{
    $config = app_config();
    return is_file(CONFIG_PATH) && !empty($config['installed']);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_signed_in(): bool
{
    return current_user() !== null;
}

function current_lang(): string
{
    return $_SESSION['lang'] ?? 'en';
}

function t(string $en, ?string $tr = null): string
{
    if (current_lang() === 'tr' && $tr !== null) {
        return $tr;
    }

    return $en;
}
