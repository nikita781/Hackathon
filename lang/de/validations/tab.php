<?php

return [
    'title.required' => 'Das Feld "Titel des Tabs" ist erforderlich.',
    'title.in' => 'Der Tab-Titel ist ungültig.',

    'sections.array' => 'Abschnitte müssen ein Array sein.',
    'sections.*.id.integer' => 'Die Abschnitts-ID muss eine Ganzzahl sein.',
    'sections.*.id.exists' => 'Abschnitt nicht gefunden.',
    'sections.*.title.required_with' => 'Der Abschnittstitel ist erforderlich.',
    'sections.*.title.string' => 'Der Abschnittstitel muss ein Text sein.',
    'sections.*.title.max' => 'Der Abschnittstitel darf nicht länger als 255 Zeichen sein.',
    'sections.*.content.string' => 'Der Abschnittsinhalt muss ein Text sein.',
    'sections.*.content.max' => 'Der Abschnittsinhalt darf nicht länger als 65535 Zeichen sein.',

    'sections.*.items.array' => 'Abschnittselemente müssen ein Array sein.',
    'sections.*.items.*.id.integer' => 'Die Element-ID muss eine Ganzzahl sein.',
    'sections.*.items.*.id.exists' => 'Element nicht gefunden.',
    'sections.*.items.*.title.required_with' => 'Der Elementtitel ist erforderlich.',
    'sections.*.items.*.title.string' => 'Der Elementtitel muss ein Text sein.',
    'sections.*.items.*.title.max' => 'Der Elementtitel darf nicht länger als 255 Zeichen sein.',
    'sections.*.items.*.content.max' => 'Der Elementinhalt darf nicht länger als 65535 Zeichen sein.',
    'sections.*.items.*.image_path.image' => 'Die Datei muss ein Bild sein.',
    'sections.*.items.*.image_path.mimes' => 'Das Bild muss im Format jpeg, png, jpg oder webp sein.',
    'sections.*.items.*.image_path.max' => 'Die maximale Bildgröße beträgt 2 MB.',

    'partners.array' => 'Partner müssen ein Array sein.',
    'partners.*.image' => 'Jeder Partner muss ein Bild sein.',
    'partners.*.max' => 'Die maximale Bildgröße für einen Partner beträgt 2 MB.',

    'files.array' => 'Dateien müssen ein Array sein.',
    'files.*.file' => 'Jede Datei muss gültig sein.',
    'files.*.max' => 'Die maximale Dateigröße beträgt 2 MB.',

    'delete_media_ids.array' => 'Die zu löschenden Medien-IDs müssen ein Array sein.',
    'delete_media_ids.*.integer' => 'Die Medien-ID muss eine Ganzzahl sein.',
    'delete_media_ids.*.exists' => 'Einige der angegebenen Medien wurden nicht gefunden.',
];
