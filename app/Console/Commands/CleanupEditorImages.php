<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Telescope\Telescope;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CleanupEditorImages extends Command
{
    protected $signature = 'editor:cleanup';

    protected $description = 'Удаляет непривязанные картинки EditorJS';

    public function handle(): void
    {
        if (class_exists(Telescope::class)) {
            Telescope::stopRecording();
        }

        $this->info('Ищем непривязанные картинки...');

        $deleted = Media::where('collection_name', 'editorjs')
            ->where(function ($query) {
                $query->whereNull('model_type')
                    ->orWhereNull('model_id');
            })
            ->delete();

        $this->info("Удалено {$deleted} файлов.");
    }
}
