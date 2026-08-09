<?php

declare(strict_types=1);

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Image optimisation on upload, using PHP's bundled GD.
 *
 * No image library is pulled in for this. GD ships with PHP, already encodes
 * WebP and AVIF, and the whole job here is three operations — decode, scale,
 * re-encode — so a dependency would buy nothing but a larger install and
 * another thing to keep patched.
 *
 * Three things happen to every uploaded image:
 *
 *  1. **It is capped.** A 6000px photograph from a phone is scaled down to
 *     `MAX_EDGE`, because nothing on the site displays an image larger than
 *     that and the original costs megabytes to deliver.
 *  2. **It is re-encoded.** That silently strips EXIF, which matters twice
 *     over: it removes the GPS coordinates and camera serial that phones
 *     embed, and it removes a payload that is otherwise shipped to every
 *     visitor for no benefit.
 *  3. **It gets WebP derivatives** at a fixed set of widths, so the view can
 *     emit a real `srcset` and a phone stops downloading a desktop image.
 *
 * The intrinsic size is encoded into the filename (`<random>-<w>x<h>.jpg`).
 * That is what lets a template emit `width`/`height` — and therefore hold the
 * layout against CLS — and build the `srcset` without a single filesystem
 * call per image on every page render.
 */
final class Image
{
    /**
     * Widths generated for the srcset.
     *
     * Chosen to cover a phone through a 2x desktop without producing a
     * derivative for every conceivable layout: each step is roughly 1.4x the
     * last, which is about the point where the saving stops being visible.
     */
    public const WIDTHS = [480, 768, 1024, 1440, 1920];

    /** Nothing on the site renders larger than this, so nothing is stored larger. */
    public const MAX_EDGE = 2400;

    public const QUALITY = 78;

    /**
     * Refuse to decode beyond this. GD holds 4 bytes per pixel, so a 30 MP
     * image already needs ~120 MB — past that a decompression bomb would take
     * the process down. Oversized uploads are stored unprocessed instead.
     */
    private const MAX_PIXELS = 30_000_000;

    /**
     * Optimise an upload and write every derivative into `$directory`.
     *
     * Returns the master filename, or null when the file cannot be processed
     * (unsupported format, animation, or too large to decode safely) — the
     * caller then stores the original as-is rather than losing the upload.
     */
    public static function optimise(UploadedFile $file, string $directory, string $random): ?string
    {
        $path = $file->getRealPath();

        if ($path === false || ! is_readable($path)) {
            return null;
        }

        $info = @getimagesize($path);

        if ($info === false) {
            return null;
        }

        [$width, $height] = $info;
        $type = $info[2] ?? 0;

        if ($width < 1 || $height < 1 || $width * $height > self::MAX_PIXELS) {
            return null;
        }

        // An animated GIF or WebP would lose its frames on re-encode, so it is
        // left alone rather than silently flattened.
        if ($type === IMAGETYPE_GIF || self::isAnimatedWebp($path)) {
            return null;
        }

        $extension = match ($type) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_AVIF => 'avif',
            default => null,
        };

        if ($extension === null) {
            return null;
        }

        try {
            $source = @imagecreatefromstring((string) file_get_contents($path));

            if (! $source instanceof GdImage) {
                return null;
            }

            $master = self::scaleToLongestEdge($source, min(self::MAX_EDGE, max($width, $height)), $width, $height);

            if ($master !== $source) {
                imagedestroy($source);
            }

            $masterWidth = imagesx($master);
            $masterHeight = imagesy($master);
            $base = "{$random}-{$masterWidth}x{$masterHeight}";

            if (! is_dir($directory) && ! @mkdir($directory, 0o755, true) && ! is_dir($directory)) {
                imagedestroy($master);

                return null;
            }

            self::encode($master, "{$directory}/{$base}.{$extension}", $extension);

            // WebP at every width the srcset can ask for, including the master
            // width, so a modern browser is never sent the heavier original.
            //
            // Scaled by *width*, not by longest edge: a `srcset` descriptor is
            // a width, so a portrait image scaled by its longest edge would be
            // advertised as 480w while actually being 360px across, and the
            // browser would pick a candidate a quarter narrower than it asked
            // for and render it soft.
            foreach (self::variantWidths($masterWidth) as $variant) {
                $resized = self::scaleToWidth($master, $variant, $masterWidth, $masterHeight);
                self::encode($resized, "{$directory}/{$base}-{$variant}.webp", 'webp');

                if ($resized !== $master) {
                    imagedestroy($resized);
                }
            }

            imagedestroy($master);

            return "{$base}.{$extension}";
        } catch (\Throwable $e) {
            // A failed optimisation must never cost the editor their upload:
            // the caller falls back to storing the file untouched.
            Log::warning('Image optimisation failed, storing the original.', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * The widths a `srcset` may reference for a master of this width.
     *
     * Derived, never stored: a template can recreate this list from the
     * dimensions in the filename, which is why rendering an image costs no
     * filesystem access at all.
     *
     * @return list<int>
     */
    public static function variantWidths(int $masterWidth): array
    {
        $widths = array_values(array_filter(
            self::WIDTHS,
            fn (int $width) => $width < $masterWidth,
        ));

        $widths[] = $masterWidth;

        return $widths;
    }

    /**
     * Pull the intrinsic size back out of a stored path.
     *
     * @return array{0:int,1:int}|null
     */
    public static function dimensions(string $path): ?array
    {
        if (preg_match('/-(\d+)x(\d+)(?:-\d+)?\.[a-z0-9]+$/i', $path, $matches) !== 1) {
            return null;
        }

        return [(int) $matches[1], (int) $matches[2]];
    }

    /** The WebP sibling of a stored master path, at the given width. */
    public static function variantPath(string $path, int $width): string
    {
        return preg_replace('/\.[a-z0-9]+$/i', "-{$width}.webp", $path) ?? $path;
    }

    /**
     * The smallest stored derivative at or above `$width`, for places that show
     * an image at a fixed small size (a thumbnail rail, an admin preview).
     *
     * Falls back to the master whenever no derivative can be guaranteed to
     * exist — pointing at a variant that was never written would render a
     * broken image, which is strictly worse than serving a few extra KB.
     */
    public static function thumb(?string $path, int $width): ?string
    {
        if (blank($path) || ! ($dimensions = self::dimensions($path))) {
            return $path;
        }

        foreach (self::variantWidths($dimensions[0]) as $variant) {
            if ($variant >= $width) {
                return self::variantPath($path, $variant);
            }
        }

        return $path;
    }

    /**
     * The complete WebP `srcset` for a stored master path.
     *
     * Null for anything stored before the pipeline existed, which is the signal
     * to fall back to a plain `<img>` rather than to guess at derivatives that
     * were never written.
     */
    public static function srcset(?string $path): ?string
    {
        if (blank($path) || ! ($dimensions = self::dimensions($path))) {
            return null;
        }

        return implode(', ', array_map(
            fn (int $width) => self::variantPath($path, $width)." {$width}w",
            self::variantWidths($dimensions[0]),
        ));
    }

    /**
     * Cap the longest edge at `$target`. Used for the master, where the point
     * is to bound what is stored regardless of orientation.
     */
    private static function scaleToLongestEdge(GdImage $source, int $target, int $width, int $height): GdImage
    {
        return self::resample($source, $target / max($width, $height), $width, $height);
    }

    /**
     * Scale so the *width* is `$target`. Used for srcset derivatives, whose
     * `w` descriptors the browser reads as widths.
     */
    private static function scaleToWidth(GdImage $source, int $target, int $width, int $height): GdImage
    {
        return self::resample($source, $target / $width, $width, $height);
    }

    /** Returns the source untouched when it is already at or below the target. */
    private static function resample(GdImage $source, float $ratio, int $width, int $height): GdImage
    {
        if ($ratio >= 1.0) {
            return $source;
        }

        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency: without this a PNG logo gains a black ground.
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $canvas;
    }

    private static function encode(GdImage $image, string $path, string $format): void
    {
        match ($format) {
            'jpg' => imagejpeg(self::flattened($image), $path, self::QUALITY),
            'png' => imagepng($image, $path, 8),
            'webp' => imagewebp($image, $path, self::QUALITY),
            'avif' => imageavif($image, $path, self::QUALITY),
            default => null,
        };

        @chmod($path, 0o644);
    }

    /** JPEG has no alpha channel, so a transparent source needs a white ground. */
    private static function flattened(GdImage $image): GdImage
    {
        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        return $canvas;
    }

    /** An animated WebP carries an ANIM chunk in its RIFF container. */
    private static function isAnimatedWebp(string $path): bool
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $header = (string) fread($handle, 64);
        fclose($handle);

        return str_starts_with($header, 'RIFF')
            && str_contains($header, 'WEBP')
            && str_contains($header, 'ANIM');
    }
}
