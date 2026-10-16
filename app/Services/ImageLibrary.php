<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The one dynamic image system for products, categories and markets.
 *
 * Seeded image paths stay relative to public/images/placeholders/, while new
 * uploads use Laravel's public storage disk so they can survive deploys on a
 * persistent volume. Image columns store either path form.
 */
class ImageLibrary
{
    /** Entity type => folder inside public/images/placeholders/. */
    public const FOLDERS = [
        'product' => 'product-images',
        'category' => 'category-images',
        'market' => 'market-images',
    ];

    /**
     * Public URL for a stored `image` column value (null when empty).
     * Understands full URLs, legacy storage/ paths and the current
     * placeholders-relative paths — with or without the folder prefix.
     */
    public static function url(string $type, ?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // Already a full URL.
        if (str_starts_with($path, 'http') || str_starts_with($path, '//')) {
            return $path;
        }

        // Legacy uploads made through ProfileImageService (storage/app/public).
        if (str_starts_with($path, 'profiles/')) {
            return asset('storage/'.$path);
        }

        if (str_starts_with($path, 'uploads/')) {
            return asset('storage/'.$path);
        }

        $folder = self::FOLDERS[$type];

        // Path already includes its folder ("product-images/apples.png").
        if (str_starts_with($path, $folder.'/')) {
            return self::assetUrl('images/placeholders/'.$path);
        }

        // Bare filename — assume it belongs to this entity type's folder.
        return self::assetUrl('images/placeholders/'.$folder.'/'.$path);
    }

    /**
     * Save an uploaded picture to the public storage disk and return its DB path.
     */
    public static function store(UploadedFile $file, string $type): string
    {
        $folder = self::FOLDERS[$type]
            ?? throw new \InvalidArgumentException("Unknown image type [{$type}].");

        // Unique, URL-safe name: slug of the original name + a short hash so
        // uploads never overwrite each other or the seeder pictures.
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $name = ($name ?: $type).'-u'.bin2hex(random_bytes(4));
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $path = Storage::disk('public')->putFileAs('uploads/'.$folder, $file, $name.'.'.$ext);

        if ($path === false) {
            throw new RuntimeException('Unable to store uploaded image.');
        }

        return $path;
    }

    /**
     * Store a new picture and remove the replaced one (if it is an upload).
     */
    public static function replace(UploadedFile $file, string $type, ?string $oldPath): string
    {
        $path = self::store($file, $type);

        self::delete($type, $oldPath);

        return $path;
    }

    /**
     * Delete an uploaded picture. Only files this library created are
     * removed — seeder pictures are shared assets and are never deleted.
     */
    public static function delete(string $type, ?string $path): void
    {
        if (! $path) {
            return;
        }

        $folder = self::FOLDERS[$type] ?? null;

        if (! $folder || ! str_starts_with($path, $folder.'/')) {
            if (! $folder || ! str_starts_with($path, 'uploads/'.$folder.'/')) {
                return;
            }

            Storage::disk('public')->delete($path);

            return;
        }

        // Uploads carry the "-u" marker in their filename; seeder files don't.
        if (! str_contains(pathinfo($path, PATHINFO_BASENAME), '-u')) {
            return;
        }

        $root = realpath(public_path('images/placeholders/'.$folder));
        $full = realpath(public_path('images/placeholders/'.$path));

        // Containment check — never delete outside our folder.
        if ($root && $full && str_starts_with($full, $root.DIRECTORY_SEPARATOR)) {
            @unlink($full);
        }
    }

    /** asset() with every path segment URL-encoded (handles "rainbow carrot.png"). */
    private static function assetUrl(string $relative): string
    {
        return asset(implode('/', array_map('rawurlencode', explode('/', $relative))));
    }
}
