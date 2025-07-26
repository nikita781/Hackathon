<?php

return [
    'title.required' => 'Le champ "Titre" est obligatoire.',
    'title.max' => 'Le champ "Titre" ne doit pas dépasser 255 caractères.',

    'description.required' => 'Le champ "Description" est obligatoire.',

    'preview.required' => 'L’image de prévisualisation est obligatoire.',
    'preview.image' => 'Le fichier doit être une image.',
    'preview.mimes' => 'L’image doit être au format jpeg, png, jpg ou webp.',
    'preview.max' => 'La taille maximale de l’image est de 5 Mo.',

    'about.nullable' => 'Le champ "À propos" doit être facultatif.',
    'stack.max' => 'Le champ "Technologie" ne doit pas dépasser 255 caractères.',

    'project_link.url' => 'Le lien du projet doit être une URL valide.',
    'project_link.starts_with' => 'Le lien du projet doit commencer par https://github.com/.',

    'presentation.file' => 'La présentation doit être un fichier.',
    'presentation.mimes' => 'La présentation doit être au format PDF, PPT ou PPTX.',
    'presentation.max' => 'La taille maximale du fichier de présentation est de 10 Mo.',

    'video_link.url' => 'Le lien vidéo doit être une URL valide.',
    'video_link.starts_with' => 'Le lien vidéo doit commencer par https://vkvideo.ru/video ou https://rutube.ru/video/.',

    'gallery.array' => 'La galerie doit être un tableau.',
    'gallery.*.image' => 'Chaque fichier de la galerie doit être une image.',
    'gallery.*.mimes' => 'Chaque image doit être au format jpeg, png, jpg ou webp.',
    'gallery.*.max' => 'Chaque image ne doit pas dépasser 5 Mo.',

    'status.in' => 'Le statut sélectionné est invalide.',

    'delete_media_ids.array' => 'Les identifiants des médias à supprimer doivent être un tableau.',
    'delete_media_ids.*.integer' => 'Chaque identifiant de média doit être un nombre entier.',
    'delete_media_ids.*.exists' => 'Certains médias sélectionnés sont introuvables.',
];
