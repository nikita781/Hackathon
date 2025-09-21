<?php

namespace App\Console\Commands;

use App\Models\EditorUpload;
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

        $deleted = EditorUpload::where(function ($query) {
                $query->whereNull('used_in_type')
                    ->orWhereNull('used_in_id');
            })
            ->get();

        foreach ($deleted as $item) {
            $item->clearMediaCollection('editorjs');
            $item->delete();
        }

        $this->info("Удалено " . count($deleted) . " файлов.");
    }
}
