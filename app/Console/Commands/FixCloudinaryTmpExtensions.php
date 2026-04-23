<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Models\Notice;
use App\Models\Result;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Illuminate\Console\Command;

class FixCloudinaryTmpExtensions extends Command
{
    protected $signature = 'cloudinary:fix-tmp-extensions {--dry-run : Show what would be changed without making changes}';

    protected $description = 'Rename Cloudinary raw files that have .tmp appended to their public_id and update DB records';

    public function handle(): int
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => config('cloudinary.cloud_name'),
                'api_key' => config('cloudinary.api_key'),
                'api_secret' => config('cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => true,
            ],
        ]);

        $uploader = new UploadApi;
        $dryRun = $this->option('dry-run');
        $fixed = 0;
        $errors = 0;

        $models = [
            'Result' => Result::where('file_public_id', 'like', '%.tmp')->get(),
            'Assignment' => Assignment::where('file_public_id', 'like', '%.tmp')->get(),
            'Notice' => Notice::where('file_public_id', 'like', '%.tmp')->get(),
        ];

        foreach ($models as $type => $records) {
            foreach ($records as $record) {
                $oldPublicId = $record->file_public_id;
                $newPublicId = preg_replace('/\.tmp$/', '', $oldPublicId);

                if ($oldPublicId === $newPublicId) {
                    continue;
                }

                $this->info("{$type} #{$record->id}: {$oldPublicId} → {$newPublicId}");

                if ($dryRun) {
                    $fixed++;

                    continue;
                }

                try {
                    // Rename the file on Cloudinary
                    $result = $uploader->rename($oldPublicId, $newPublicId, [
                        'resource_type' => 'raw',
                    ]);

                    // Update DB: public_id and file_url
                    $record->update([
                        'file_public_id' => $newPublicId,
                        'file_url' => $result['secure_url'],
                    ]);

                    $this->info('  ✓ Fixed successfully');
                    $fixed++;
                } catch (\Throwable $e) {
                    $this->error("  ✗ Failed: {$e->getMessage()}");
                    $errors++;
                }
            }
        }

        $prefix = $dryRun ? '[DRY RUN] Would fix' : 'Fixed';
        $this->newLine();
        $this->info("{$prefix} {$fixed} file(s). Errors: {$errors}.");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
