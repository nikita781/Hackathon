<?php

return [
    'title.required' => 'Das Feld „Titel“ ist erforderlich.',
    'title.max' => 'Das Feld „Titel“ darf nicht mehr als 255 Zeichen enthalten.',

    'description.required' => 'Das Feld „Beschreibung“ ist erforderlich.',

    'preview.required' => 'Ein Vorschaubild muss angegeben werden.',
    'preview.image' => 'Die Datei muss ein Bild sein.',
    'preview.mimes' => 'Das Bild muss im Format jpeg, png, jpg oder webp vorliegen.',
    'preview.max' => 'Die maximale Bildgröße beträgt 5 MB.',

    'about.nullable' => 'Das Feld „Über“ ist optional.',
    'stack.max' => 'Das Feld „Technologie-Stack“ darf nicht mehr als 255 Zeichen enthalten.',

    'project_link.url' => 'Der Projektlink muss eine gültige URL sein.',
    'project_link.starts_with' => 'Der Projektlink muss mit https://github.com/ beginnen.',

    'presentation.file' => 'Die Präsentation muss eine Datei sein.',
    'presentation.mimes' => 'Die Präsentation muss im Format PDF, PPT oder PPTX vorliegen.',
    'presentation.max' => 'Die maximale Dateigröße der Präsentation beträgt 10 MB.',

    'video_link.url' => 'Der Videolink muss eine gültige URL sein.',
    'video_link.starts_with' => 'Der Videolink muss mit https://vkvideo.ru/video oder https://rutube.ru/video/ beginnen.',

    'gallery.array' => 'Die Galerie muss ein Array sein.',
    'gallery.*.image' => 'Jede Datei in der Galerie muss ein Bild sein.',
    'gallery.*.mimes' => 'Jedes Bild muss im Format jpeg, png, jpg oder webp sein.',
    'gallery.*.max' => 'Die maximale Größe jedes Bildes beträgt 5 MB.',

    'status.in' => 'Der ausgewählte Status ist ungültig.',

    'delete_media_ids.array' => 'Die zu löschenden Medien-IDs müssen ein Array sein.',
    'delete_media_ids.*.integer' => 'Jede Medien-ID muss eine Ganzzahl sein.',
    'delete_media_ids.*.exists' => 'Einige der angegebenen Medien wurden nicht gefunden.',

    'title.min' => 'Der Titel muss mindestens 5 Zeichen enthalten.',
    'description.min' => 'Die Beschreibung muss mindestens 10 Zeichen enthalten.',
    'about.min' => 'Das Feld "Über das Projekt" muss mindestens 10 Zeichen enthalten.',
];
