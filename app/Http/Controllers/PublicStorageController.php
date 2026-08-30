<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicStorageController extends Controller
{
    public function __invoke(string $path): BinaryFileResponse|Response
    {
        $path = str_replace('\\', '/', $path);
        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            abort(404);
        }

        return response()->file(
            $disk->path($path),
            ['Cache-Control' => 'public, max-age=31536000, immutable'],
        );
    }
}
