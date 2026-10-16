<?php

namespace Tests\Feature;

use App\Services\ImageLibrary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageLibraryTest extends TestCase
{
    public function test_uploads_are_stored_on_the_public_disk_and_deleted_from_it(): void
    {
        Storage::fake('public');

        $path = ImageLibrary::store(
            UploadedFile::fake()->create('fresh tomatoes.png', 100, 'image/png'),
            'product',
        );

        $this->assertStringStartsWith('uploads/product-images/fresh-tomatoes-u', $path);
        $this->assertStringEndsWith('.png', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame(asset('storage/'.$path), ImageLibrary::url('product', $path));

        ImageLibrary::delete('product', $path);

        Storage::disk('public')->assertMissing($path);
    }
}
