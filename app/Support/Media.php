<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Editor uploads.
 *
 * Two rules make this safe rather than convenient: the filename is generated,
 * never taken from the upload, and the extension is derived from the file's
 * sniffed MIME type rather than from what the client claimed. Together they
 * mean an editor cannot store `invoice.php` or overwrite an existing asset by
 * uploading a file with the same name.
 */
final class Media
{
    public const DISK = 'media';

    public const DOCUMENT_DISK = 'documents';

    /**
     * Store an image and return the public URL path used in `<img src>`.
     *
     * The file goes through `Image::optimise()` first: capped in size, stripped
     * of EXIF, and written alongside a set of WebP derivatives. That returns a
     * filename carrying the intrinsic dimensions, which is what lets templates
     * emit `width`/`height` and a real `srcset` without touching the disk.
     *
     * If optimisation is not possible — an animation, an unsupported format, an
     * image too large to decode safely — the original is stored untouched. An
     * editor never loses an upload to an optimisation that could not run.
     */
    public static function storeImage(UploadedFile $file, string $folder = 'uploads'): string
    {
        $folder = self::folder($folder);
        $random = Str::random(32);

        $optimised = Image::optimise(
            $file,
            Storage::disk(self::DISK)->path($folder),
            $random,
        );

        if ($optimised !== null) {
            return '/storage/media/'.$folder.'/'.$optimised;
        }

        $path = $file->storeAs($folder, self::filename($file, $random), ['disk' => self::DISK]);

        return '/storage/media/'.$path;
    }

    /**
     * Store a document on the *private* disk and return its relative path.
     *
     * Documents are streamed by DownloadController, so the returned value is a
     * storage path, not a URL — an unpublished document stays unreachable.
     */
    public static function storeDocument(UploadedFile $file, string $folder = 'documents'): string
    {
        return $file->storeAs(
            self::folder($folder),
            self::filename($file),
            ['disk' => self::DOCUMENT_DISK],
        );
    }

    /**
     * Remove a previously stored image and every derivative generated from it,
     * ignoring anything outside the media disk.
     */
    public static function deleteImage(?string $url): void
    {
        if (blank($url) || ! Str::startsWith($url, '/storage/media/')) {
            return;
        }

        $path = Str::after($url, '/storage/media/');

        if (str_contains($path, '..')) {
            return;
        }

        $disk = Storage::disk(self::DISK);
        $disk->delete($path);

        // The WebP siblings are derived from the master, so they are only ever
        // reachable through it — leaving them behind would leak disk forever.
        if ($dimensions = Image::dimensions($path)) {
            foreach (Image::variantWidths($dimensions[0]) as $width) {
                $disk->delete(Image::variantPath($path, $width));
            }
        }
    }

    /** A slug-safe folder, so an editor-supplied name can never traverse. */
    private static function folder(string $folder): string
    {
        return Str::slug($folder) ?: 'uploads';
    }

    /**
     * `<random>.<ext>`, with the extension taken from the sniffed MIME type and
     * checked against an allow-list — never from the client-supplied name.
     */
    private static function filename(UploadedFile $file, ?string $random = null): string
    {
        $allowed = array_merge(
            config('site.uploads.image_mimes'),
            config('site.uploads.document_mimes'),
        );

        $extension = strtolower((string) $file->extension());

        if (! in_array($extension, $allowed, true)) {
            $extension = 'bin';
        }

        return ($random ?? Str::random(32)).'.'.$extension;
    }
}
