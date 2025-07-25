<?php

return [
    'title.required' => 'O campo "Título da nomeação" é obrigatório.',
    'title.max' => 'O título da nomeação não pode exceder 255 caracteres.',

    'prize.required' => 'O campo "Prêmio da nomeação" é obrigatório.',
    'prize.max' => 'O prêmio da nomeação não pode exceder 255 caracteres.',

    'places.array' => 'O campo "Lugares dos prêmios" deve ser um array.',

    'places.*.place.required_with' => 'O campo "Lugar" é obrigatório quando os lugares dos prêmios são especificados.',
    'places.*.place.integer' => 'O campo "Lugar" deve ser um número.',

    'places.*.prize.required_with' => 'O campo "Prêmio para o lugar" é obrigatório quando os lugares dos prêmios são especificados.',
    'places.*.prize.max' => 'O prêmio para o lugar não pode exceder 255 caracteres.',
];
