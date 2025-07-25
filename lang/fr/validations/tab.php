<?php

return [
    'title.required' => 'Le champ "Titre de l\'onglet" est requis.',
    'title.in' => 'Le titre de l\'onglet est invalide.',

    'sections.array' => 'Les sections doivent être un tableau.',
    'sections.*.id.integer' => 'L\'ID de la section doit être un entier.',
    'sections.*.id.exists' => 'Section non trouvée.',
    'sections.*.title.required_with' => 'Le titre de la section est requis.',
    'sections.*.title.string' => 'Le titre de la section doit être une chaîne de caractères.',
    'sections.*.title.max' => 'Le titre de la section ne doit pas dépasser 255 caractères.',
    'sections.*.content.string' => 'Le contenu de la section doit être une chaîne de caractères.',
    'sections.*.content.max' => 'Le contenu de la section ne doit pas dépasser 65535 caractères.',

    'sections.*.items.array' => 'Les éléments de la section doivent être un tableau.',
    'sections.*.items.*.id.integer' => 'L\'ID de l\'élément doit être un entier.',
    'sections.*.items.*.id.exists' => 'Élément non trouvé.',
    'sections.*.items.*.title.required_with' => 'Le titre de l\'élément est requis.',
    'sections.*.items.*.title.string' => 'Le titre de l\'élément doit être une chaîne de caractères.',
    'sections.*.items.*.title.max' => 'Le titre de l\'élément ne doit pas dépasser 255 caractères.',
    'sections.*.items.*.content.max' => 'Le contenu de l\'élément ne doit pas dépasser 65535 caractères.',
    'sections.*.items.*.image_path.image' => 'Le fichier doit être une image.',
    'sections.*.items.*.image_path.mimes' => 'L\'image doit être au format jpeg, png, jpg ou webp.',
    'sections.*.items.*.image_path.max' => 'La taille maximale de l\'image est de 2 Mo.',

    'partners.array' => 'Les partenaires doivent être un tableau.',
    'partners.*.image' => 'Chaque partenaire doit être une image.',
    'partners.*.max' => 'La taille maximale de l\'image d\'un partenaire est de 2 Mo.',

    'files.array' => 'Les fichiers doivent être un tableau.',
    'files.*.file' => 'Chaque fichier doit être valide.',
    'files.*.max' => 'La taille maximale du fichier est de 2 Mo.',

    'delete_media_ids.array' => 'Les ID des médias à supprimer doivent être un tableau.',
    'delete_media_ids.*.integer' => 'L\'ID du média doit être un entier.',
    'delete_media_ids.*.exists' => 'Certains médias spécifiés sont introuvables.',
];
