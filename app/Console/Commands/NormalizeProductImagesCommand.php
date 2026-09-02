<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use App\Support\Image\ProductImageNormalizer;
use App\Support\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class NormalizeProductImagesCommand extends Command
{
    protected $signature = 'products:normalize-images
                            {--dry-run : Report images without writing changes}
                            {--only-missing : Skip images already 800x800}';

    protected $description = 'Fit local product images onto the standard square canvas.';

    public function handle(ProductImageNormalizer $normalizer): int
    {
        if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
            $this->error('GD or Imagick extension is required.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $onlyMissing = (bool) $this->option('only-missing');

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        ProductImage::query()
            ->orderBy('id')
            ->chunkById(100, function ($images) use ($normalizer, $dryRun, $onlyMissing, &$processed, &$skipped, &$failed) {
                foreach ($images as $image) {
                    $local = Media::localStoragePath($image->url);
                    if ($local === null || ! str_starts_with($local, 'products/')) {
                        $skipped++;

                        continue;
                    }

                    $absolute = Storage::disk('public')->path($local);
                    if (! is_file($absolute)) {
                        $skipped++;

                        continue;
                    }

                    if ($onlyMissing && $normalizer->isNormalized($absolute)) {
                        $skipped++;

                        continue;
                    }

                    if ($dryRun) {
                        $processed++;

                        continue;
                    }

                    try {
                        $temp = $normalizer->normalizePath($absolute);
                        $newPath = 'products/'.Str::random(40).'.jpg';

                        $stream = fopen($temp, 'rb');
                        if ($stream === false) {
                            throw new \RuntimeException('Could not read normalized image.');
                        }

                        try {
                            $stored = Storage::disk('public')->put($newPath, $stream);
                        } finally {
                            fclose($stream);
                            @unlink($temp);
                        }

                        if (! $stored) {
                            throw new \RuntimeException('Could not store normalized image.');
                        }

                        $image->update(['url' => $newPath]);
                        Media::delete($local);
                        $processed++;
                    } catch (Throwable $e) {
                        $failed++;
                        $this->warn("Image #{$image->id}: {$e->getMessage()}");
                    }
                }
            });

        $this->info(sprintf(
            '%s %d image(s), skipped %d, failed %d.',
            $dryRun ? 'Would normalize' : 'Normalized',
            $processed,
            $skipped,
            $failed,
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
