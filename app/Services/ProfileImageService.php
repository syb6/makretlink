<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Handles profile photo storage for every role.
 *
 * Live-server friendly: no image processing extensions required (GD/Imagick
 * are often missing on shared hosts), enforces size/mime limits via
 * controller validation, and always deletes the replaced file so the
 * storage folder doesn't grow unbounded.
 */
class ProfileImageService
{
    public function replace(UploadedFile $file, ?string $oldPath, string $prefix): string
    {
        // Store with a short hash name: avoids unicode/path issues and
        // gives every upload a unique, cache-bustable filename.
        $path = $file->storeAs(
            'profiles',
            $prefix.'-'.substr(hash('sha256', $file->getClientOriginalName().microtime()), 0, 10).'.'.$file->getClientOriginalExtension(),
            'public'
        );

        $this->delete($oldPath);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path && $this->isManagedPath($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /** Only files we manage can be deleted — never arbitrary storage paths. */
    private function isManagedPath(string $path): bool
    {
        foreach (['profiles/', 'products/', 'markets/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
