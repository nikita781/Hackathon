<?php

return [
    'title.required' => 'Поле "Заголовок вкладки" обязательно.',
    'title.in' => 'Заголовок вкладки недопустим.',

    'sections.array' => 'Секции должны быть массивом.',
    'sections.*.id.integer' => 'ID секции должен быть числом.',
    'sections.*.id.exists' => 'Секция не найдена.',
    'sections.*.title.required_with' => 'Заголовок секции обязателен.',
    'sections.*.title.string' => 'Заголовок секции должен быть строкой.',
    'sections.*.title.max' => 'Заголовок секции не должен превышать 255 символов.',
    'sections.*.content.string' => 'Контент секции должен быть строкой.',
    'sections.*.content.max' => 'Контент секции не должен превышать 65535 символов.',

    'sections.*.items.array' => 'Пункты секции должны быть массивом.',
    'sections.*.items.*.id.integer' => 'ID пункта должен быть числом.',
    'sections.*.items.*.id.exists' => 'Пункт не найден.',
    'sections.*.items.*.title.required_with' => 'Заголовок пункта обязателен.',
    'sections.*.items.*.title.string' => 'Заголовок пункта должен быть строкой.',
    'sections.*.items.*.title.max' => 'Заголовок пункта не должен превышать 255 символов.',
    'sections.*.items.*.content.max' => 'Контент пункта не должен превышать 65535 символов.',
    'sections.*.items.*.image_path.image' => 'Файл должен быть изображением.',
    'sections.*.items.*.image_path.mimes' => 'Изображение должно быть в формате jpeg, png, jpg, webp.',
    'sections.*.items.*.image_path.max' => 'Максимальный размер изображения — 2 МБ.',

    'partners.array' => 'Партнёры должны быть массивом.',
    'partners.*.image' => 'Каждый партнёр должен быть изображением.',
    'partners.*.max' => 'Максимальный размер изображения партнёра — 2 МБ.',

    'files.array' => 'Файлы должны быть массивом.',
    'files.*.file' => 'Каждый файл должен быть корректным файлом.',
    'files.*.max' => 'Максимальный размер файла — 2 МБ.',

    'delete_media_ids.array' => 'IDs медиа для удаления должны быть массивом.',
    'delete_media_ids.*.integer' => 'ID медиа должен быть числом.',
    'delete_media_ids.*.exists' => 'Некоторые указанные медиа не найдены.',
];
