<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    /** Render a URL as inline SVG (FR-18). */
    public function svg(string $url, int $size = 300): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd(),
        );

        $svg = (new Writer($renderer))->writeString($url);

        // Strip the XML declaration so the SVG can be embedded inline.
        return preg_replace('/^<\?xml.*?\?>\s*/', '', $svg);
    }
}
