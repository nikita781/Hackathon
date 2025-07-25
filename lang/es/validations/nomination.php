<?php

return [
    'title.required' => 'El campo "Título de la nominación" es obligatorio.',
    'title.max' => 'El título de la nominación no debe exceder los 255 caracteres.',

    'prize.required' => 'El campo "Premio de la nominación" es obligatorio.',
    'prize.max' => 'El premio de la nominación no debe exceder los 255 caracteres.',

    'places.array' => 'El campo "Lugares premiados" debe ser un arreglo.',

    'places.*.place.required_with' => 'El campo "Lugar" es obligatorio cuando se especifican lugares premiados.',
    'places.*.place.integer' => 'El campo "Lugar" debe ser un número.',

    'places.*.prize.required_with' => 'El campo "Premio por lugar" es obligatorio cuando se especifican lugares premiados.',
    'places.*.prize.max' => 'El premio por lugar no debe exceder los 255 caracteres.',
];
