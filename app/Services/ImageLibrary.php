<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * The one dynamic image system for products, categories and markets.
 *
 * Each of those tables has an `image` column that stores a path RELATIVE
 * to public/images/placeholders/, e.g. "product-images/apples.png".
 * Seeder pictures live in those folders and farmer/admin uploads are saved
 * into the very same folders — so the UI only ever calls ImageLibrary::url()
 * with whatever the DB column holds. No magic, no slug maps.
 */
class ImageLibrary
{
    /** Entity type => folder inside public/images/placeholders/. */
    public const FOLDERS = [
        'product'  => 'product-images',
        'category' => 'category-images',
        'market'   => 'market-images',
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

        $folder = self::FOLDERS[$type];

        // Path already includes its folder ("product-images/apples.png").
        if (str_starts_with($path, $folder.'/')) {
            return self::assetUrl('images/placeholders/'.$path);
        }

        // Bare filename — assume it belongs to this entity type's folder.
        return self::assetUrl('images/placeholders/'.$folder.'/'.$path);
    }

    /**
     * Save an uploaded picture into public/images/placeholders/<type-folder>/
     * and return the DB-ready relative path, e.g. "product-images/mango-u4f2c1.png".
     */
    public static function store(UploadedFile $file, string $type): string
    {
        $folder = self::FOLDERS[$type]
            ?? throw new \InvalidArgumentException("Unknown image type [{$type}].");

        $dir = public_path('images/placeholders/'.$folder);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // Unique, URL-safe name: slug of the original name + a short hash so
        // uploads never overwrite each other or the seeder pictures.
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $name = ($name ?: $type).'-u'.bin2hex(random_bytes(4));
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');

        $file->move($dir, $name.'.'.$ext);

        return $folder.'/'.$name.'.'.$ext;
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
