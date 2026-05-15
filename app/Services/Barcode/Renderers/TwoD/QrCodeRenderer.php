<?php

namespace App\Services\Barcode\Renderers\TwoD;

use App\Services\Barcode\Renderers\Contracts\BarcodeRendererInterface;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use InvalidArgumentException;

class QrCodeRenderer implements BarcodeRendererInterface
{
    protected const DEFAULT_WIDTH = 300;

    protected const DEFAULT_MARGIN = 1;

    protected const DEFAULT_FOREGROUND_COLOR = '#000000';

    protected const DEFAULT_BACKGROUND_COLOR = '#ffffff';

    protected const DEFAULT_ERROR_CORRECTION = 'M';

    public function render(string $data, string $format, array $parameters = []): array
    {
        $normalizedFormat = strtolower(trim($format));

        if ($normalizedFormat !== 'svg') {
            throw new InvalidArgumentException('QR Code renderer supports SVG output only.');
        }

        $width = $this->resolveWidth($parameters);
        $margin = $this->resolveMargin($parameters);
        $foregroundColor = $this->resolveColor(
            $parameters['foreground_color'] ?? null,
            self::DEFAULT_FOREGROUND_COLOR,
        );
        $backgroundColor = $this->resolveColor(
            $parameters['background_color'] ?? null,
            self::DEFAULT_BACKGROUND_COLOR,
        );

        $svg = (new QRCode(new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'outputBase64' => false,
            'eccLevel' => $this->resolveEccLevel($parameters['error_correction'] ?? null),
            'addQuietzone' => $margin > 0,
            'quietzoneSize' => $margin,
            'scale' => max(1, (int) ceil($width / 33)),
            'bgColor' => $backgroundColor,
            'moduleValues' => [
                QRMatrix::M_DATA_DARK => $foregroundColor,
                QRMatrix::M_DATA => $backgroundColor,
                QRMatrix::M_FINDER_DARK => $foregroundColor,
                QRMatrix::M_FINDER_DOT => $foregroundColor,
                QRMatrix::M_ALIGNMENT_DARK => $foregroundColor,
                QRMatrix::M_TIMING_DARK => $foregroundColor,
                QRMatrix::M_FORMAT_DARK => $foregroundColor,
                QRMatrix::M_VERSION_DARK => $foregroundColor,
                QRMatrix::M_DARKMODULE => $foregroundColor,
                QRMatrix::M_SEPARATOR => $backgroundColor,
                QRMatrix::M_QUIETZONE => $backgroundColor,
            ],
            'svgUseFillAttributes' => true,
            'cssClass' => 'barcodeos-qr-preview',
        ])))->render($data);

        return [
            'format' => 'svg',
            'mime_type' => 'image/svg+xml',
            'content' => $this->applyDimensions($svg, $width),
            'width' => $width,
            'height' => $width,
            'metadata' => [
                'renderer' => 'qr-code',
            ],
        ];
    }

    protected function resolveWidth(array $parameters): int
    {
        $candidate = $parameters['width'] ?? $parameters['size'] ?? self::DEFAULT_WIDTH;

        if (! is_numeric($candidate)) {
            return self::DEFAULT_WIDTH;
        }

        return max(64, min(2048, (int) $candidate));
    }

    protected function resolveMargin(array $parameters): int
    {
        $candidate = $parameters['margin'] ?? self::DEFAULT_MARGIN;

        if (! is_numeric($candidate)) {
            return self::DEFAULT_MARGIN;
        }

        return max(0, min(75, (int) $candidate));
    }

    protected function resolveColor(mixed $candidate, string $default): string
    {
        if (! is_string($candidate)) {
            return $default;
        }

        $normalized = trim($candidate);

        if (! preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $normalized)) {
            return $default;
        }

        return strtolower($normalized);
    }

    protected function resolveEccLevel(mixed $candidate): int
    {
        $normalized = is_string($candidate)
            ? strtoupper(trim($candidate))
            : self::DEFAULT_ERROR_CORRECTION;

        return match ($normalized) {
            'L' => EccLevel::L,
            'Q' => EccLevel::Q,
            'H' => EccLevel::H,
            default => EccLevel::M,
        };
    }

    protected function applyDimensions(string $svg, int $width): string
    {
        return preg_replace(
            '/^<svg\s+xmlns=/',
            sprintf('<svg width="%d" height="%d" xmlns=', $width, $width),
            $svg,
            1,
        ) ?? $svg;
    }
}
