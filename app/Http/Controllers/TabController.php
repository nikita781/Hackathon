<?php

namespace App\Http\Controllers;

use App\Http\Requests\TabRequest;
use App\Models\Hackathon;
use App\Models\Tab;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\MediaCannotBeDeleted;

class TabController extends Controller
{
    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     * @throws MediaCannotBeDeleted
     */
    public function update(TabRequest $request, Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(404);
        }

        $data = $request->validated();
        $tab = $hackathon->tabs()->with('sections.items')->where('title', $data['title'])->firstOrFail();

        $existingSectionIds = [];
        $existingItemIdsBySection = [];

        if (!empty($data['sections'])) {
            foreach ($data['sections'] as $sectionData) {

//              Обновления секций таба
                if (!empty($sectionData['id'])) {
                    $section = $tab->sections()->find($sectionData['id']);
                    if ($section) {
                        $section->update([
                            'title' => $sectionData['title'],
                            'content' => $sectionData['content'] ?? null,
                        ]);
                    } else {
//                      Создание секций таба, если не найдена секция по переданному id
                        $section = $tab->sections()->create([
                            'title' => $sectionData['title'],
                            'content' => $sectionData['content'] ?? null,
                        ]);
                    }
                } else {
//                  Создание секций таба, если не передано id
                    $section = $tab->sections()->create([
                        'title' => $sectionData['title'],
                        'content' => $sectionData['content'] ?? null,
                    ]);
                }

                $existingSectionIds[] = $section->id;
                $existingItemIdsBySection[$section->id] = [];

                if (!empty($sectionData['items'])) {
                    foreach ($sectionData['items'] as $itemData) {
//                      Обновления элементов секций таба
                        if (!empty($itemData['id'])) {
                            $item = $section->items()->find($itemData['id']);
                            if ($item) {
                                $item->update([
                                    'title' => $itemData['title'],
                                    'content' => $itemData['content'] ?? null,
                                ]);
                            } else {
//                              Создание элементов сеции, если не найден елемент по id
                                $item = $section->items()->create([
                                    'title' => $itemData['title'],
                                    'content' => $itemData['content'] ?? null,
                                ]);
                            }
                        } else {
//                          Создание элементов сеции, если не передан id
                            $item = $section->items()->create([
                                'title' => $itemData['title'],
                                'content' => $itemData['content'] ?? null,
                            ]);
                        }

                        if (!empty($itemData['image'])) {
                            $item->clearMediaCollection('image');
                            $item->addMedia($itemData['image_path'])->toMediaCollection('image');
                        }

                        $existingItemIdsBySection[$section->id][] = $item->id;
                    }
                }
            }

            // Удаление секций, если их не было в запросе
            $tab->sections()->whereNotIn('id', $existingSectionIds)->each(function ($section) {
                $section->items()->delete();
                $section->delete();
            });

            // Удаление элементов, если их не было в запросе
            foreach ($tab->sections as $section) {
                $keepIds = $existingItemIdsBySection[$section->id] ?? [];
                $section->items()->whereNotIn('id', $keepIds)->delete();
            }
        }

        if (!empty($data['delete_media_ids'])) {
            foreach ($data['delete_media_ids'] as $id) {
                $tab->deleteMedia($id);
            }
        }

        if (!empty($data['files'])) {
            foreach ($data['files'] as $file) {
                $tab->addMedia($file)->toMediaCollection('files');
            }
        }

        if (!empty($data['partners'])) {
            foreach ($data['partners'] as $partner) {
                $tab->addMedia($partner)->toMediaCollection('partner_images');
            }
        }

        return back()->with('success', 'Сохранено');
    }
}
