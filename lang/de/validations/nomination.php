<?php

return [
    'title.required' => 'Das Feld "Titel der Nominierung" ist erforderlich.',
    'title.max' => 'Der Titel der Nominierung darf nicht länger als 255 Zeichen sein.',

    'prize.required' => 'Das Feld "Preis der Nominierung" ist erforderlich.',
    'prize.max' => 'Der Preis der Nominierung darf nicht länger als 255 Zeichen sein.',

    'places.array' => 'Das Feld "Preisplätze" muss ein Array sein.',

    'places.*.place.required_with' => 'Das Feld "Platz" ist erforderlich, wenn Preisplätze angegeben sind.',
    'places.*.place.integer' => 'Das Feld "Platz" muss eine Zahl sein.',

    'places.*.prize.required_with' => 'Das Feld "Preis für den Platz" ist erforderlich, wenn Preisplätze angegeben sind.',
    'places.*.prize.max' => 'Der Preis für den Platz darf nicht länger als 255 Zeichen sein.',
];
