<?php

return [
    'title.required' => 'Le champ "Titre de la nomination" est requis.',
    'title.max' => 'Le titre de la nomination ne doit pas dépasser 255 caractères.',

    'prize.required' => 'Le champ "Prix de la nomination" est requis.',
    'prize.max' => 'Le prix de la nomination ne doit pas dépasser 255 caractères.',

    'places.array' => 'Le champ "Places récompensées" doit être un tableau.',

    'places.*.place.required_with' => 'Le champ "Place" est requis lorsque des places sont spécifiées.',
    'places.*.place.integer' => 'Le champ "Place" doit être un nombre.',

    'places.*.prize.required_with' => 'Le champ "Prix pour la place" est requis lorsque des places sont spécifiées.',
    'places.*.prize.max' => 'Le prix pour la place ne doit pas dépasser 255 caractères.',
];
