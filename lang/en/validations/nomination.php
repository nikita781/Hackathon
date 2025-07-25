<?php

return [
    'title.required' => 'The "Nomination title" field is required.',
    'title.max' => 'The nomination title must not exceed 255 characters.',

    'prize.required' => 'The "Nomination prize" field is required.',
    'prize.max' => 'The nomination prize must not exceed 255 characters.',

    'places.array' => 'The "Prize places" field must be an array.',

    'places.*.place.required_with' => 'The "Place" field is required when prize places are specified.',
    'places.*.place.integer' => 'The "Place" field must be a number.',

    'places.*.prize.required_with' => 'The "Prize for place" field is required when prize places are specified.',
    'places.*.prize.max' => 'The prize for the place must not exceed 255 characters.',
];
