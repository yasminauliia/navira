<?php
/**
 * Helper URL aplikasi web (bukan path filesystem).
 */

function appWebPath(): string
{
    static $path = null;
    if ($path !== null) {
        return $path;
    }

    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
    $appRoot = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: dirname(__DIR__));

    if ($docRoot !== '' && $appRoot !== '' && stripos($appRoot, $docRoot) === 0) {
        $path = '/' . trim(substr($appRoot, strlen($docRoot)), '/');
        return $path === '/' ? '' : $path;
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (preg_match('#^(/[^/]+)/#', $script, $m)) {
        $path = $m[1];
        return $path;
    }

    $path = '/kapal';
    return $path;
}

function appBaseUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . appWebPath();
}

function naviraLogoPath(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'logo.png';
}

function naviraLogoExists(): bool
{
    return is_file(naviraLogoPath());
}

function naviraLogoWebSrc(string $relativePrefix = '../'): string
{
    return rtrim($relativePrefix, '/') . '/assets/logo.png';
}

function naviraLogoDataUri(): string
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    if (!naviraLogoExists()) {
        $cache = '';
        return $cache;
    }

    $binary = @file_get_contents(naviraLogoPath());
    $cache  = ($binary !== false && $binary !== '')
        ? 'data:image/png;base64,' . base64_encode($binary)
        : '';

    return $cache;
}

/**
 * Tag <img> logo untuk halaman web.
 */
function naviraLogoImg(string $relativePrefix = '../', int $height = 42, string $extraClass = ''): string
{
    if (!naviraLogoExists()) {
        return '';
    }

    $class = trim('navira-logo ' . $extraClass);
    $src   = htmlspecialchars(naviraLogoWebSrc($relativePrefix), ENT_QUOTES, 'UTF-8');

    return '<img src="' . $src . '" alt="Navira" class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8')
        . '" style="height:' . $height . 'px;width:auto;object-fit:contain;">';
}

/**
 * Logo untuk template PDF (Dompdf) — pakai base64 agar pasti tampil.
 */
function naviraLogoDataUriForPdf(int $maxHeight = 48): string
{
    static $cache = [];
    if (isset($cache[$maxHeight])) {
        return $cache[$maxHeight];
    }

    $path = naviraLogoPath();
    if (!is_file($path)) {
        $cache[$maxHeight] = '';
        return '';
    }

    if (extension_loaded('gd')) {
        $info = @getimagesize($path);
        if ($info !== false) {
            $src = null;
            switch ($info[2]) {
                case IMAGETYPE_PNG:
                    $src = @imagecreatefrompng($path);
                    break;
                case IMAGETYPE_JPEG:
                    $src = @imagecreatefromjpeg($path);
                    break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagecreatefromwebp')) {
                        $src = @imagecreatefromwebp($path);
                    }
                    break;
            }

            if ($src) {
                $w = imagesx($src);
                $h = imagesy($src);
                if ($w > 0 && $h > 0) {
                    $newH = min($maxHeight, $h);
                    $newW = (int)round($w * ($newH / $h));
                    $dst  = imagecreatetruecolor($newW, $newH);
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                    imagefill($dst, 0, 0, $transparent);
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
                    ob_start();
                    imagepng($dst);
                    $png = ob_get_clean();
                    imagedestroy($src);
                    imagedestroy($dst);
                    if ($png !== false && $png !== '') {
                        $cache[$maxHeight] = 'data:image/png;base64,' . base64_encode($png);
                        return $cache[$maxHeight];
                    }
                }
                imagedestroy($src);
            }
        }
    }

    $cache[$maxHeight] = naviraLogoDataUri();
    return $cache[$maxHeight];
}

function naviraLogoHtmlPdf(int $height = 48): string
{
    $dataUri = naviraLogoDataUriForPdf($height);
    if ($dataUri === '') {
        return '';
    }

    return '<img src="' . $dataUri . '" class="brand-logo" alt="" style="height:' . $height . 'px;width:auto;display:block;" />';
}

