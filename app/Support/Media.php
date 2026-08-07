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
     */
    public static function storeImage(UploadedFile $file, string $folder = 'uploads'): string
    {
        $path = $file->storeAs(
            self::folder($folder),
            self::filename($file),
            ['disk' => self::DISK],
        );

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

    /** Remove a previously stored image, ignoring anything outside the media disk. */
    public static function deleteImage(?string $url): void
    {
        if (blank($url) || ! Str::startsWith($url, '/storage/media/')) {
            return;
        }

        $path = Str::after($url, '/storage/media/');

        if (! str_contains($path, '..')) {
            Storage::disk(self::DISK)->delete($path);
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
    private static function filename(UploadedFile $file): string
    {
        $allowed = array_merge(
            config('site.uploads.image_mimes'),
            config('site.uploads.document_mimes'),
        );

        $extension = strtolower((string) $file->extension());

        if (! in_array($extension, $allowed, true)) {
            $extension = 'bin';
        }

        return Str::random(32).'.'.$extension;
    }
}
