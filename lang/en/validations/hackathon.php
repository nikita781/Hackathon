<?php

return [
    'title.required' => 'The "Title" field is required.',
    'title.max' => 'The "Title" field must not exceed 255 characters.',
    'title.min' => 'The "Title" field must be at least 5 characters.',

    'image_path.required' => 'An image is required.',
    'image_path.image' => 'The file must be an image.',
    'image_path.mimes' => 'The image must be in one of the following formats: jpeg, png, jpg, webp.',
    'image_path.max' => 'The maximum image size is 10 MB.',

    'format.required' => 'The "Format" field is required.',
    'format.in' => 'The selected format is invalid.',

    'type.required' => 'The "Participation type" field is required.',
    'type.in' => 'The selected participation type is invalid (individual, team).',

    'min_team_size.required_if' => 'The minimum team size is required for team participation.',
    'min_team_size.integer' => 'The minimum team size must be an integer.',
    'min_team_size.lte' => 'The minimum team size cannot be greater than the maximum.',
    'min_team_size.min' => 'The minimum team size cannot be less than 1.',
    'min_team_size.exclude_if' => 'Minimum team size is not used for individual participation.',

    'max_team_size.required_if' => 'The maximum team size is required for team participation.',
    'max_team_size.integer' => 'The maximum team size must be an integer.',
    'max_team_size.gte' => 'The maximum team size cannot be smaller than the minimum.',
    'max_team_size.min' => 'The maximum team size cannot be less than 1.',
    'max_team_size.exclude_if' => 'Maximum team size is not used for individual participation.',

    'registration_start.date' => 'The registration start date must be a valid date.',
    'registration_start.before' => 'The registration start date must be before the registration end date.',

    'registration_end.required' => 'The registration end date is required.',
    'registration_end.date' => 'The registration end date must be a valid date.',
    'registration_end.before_or_equal' => 'The registration end date must be before or equal to the event start date.',

    'event_start.required' => 'The event start date is required.',
    'event_start.date' => 'The event start date must be a valid date.',
    'event_start.before' => 'The event start date must be before the event end date.',

    'event_end.required' => 'The event end date is required.',
    'event_end.date' => 'The event end date must be a valid date.',

    'work_time_start.required' => 'The work start time is required.',
    'work_time_start.date' => 'The work start time must be a valid date.',
    'work_time_start.after_or_equal' => 'The work start time must be on or after the event start date.',
    'work_time_start.before_or_equal' => 'The work start time must be before or equal to the event end date.',
    'work_time_start.before_or_equal_work_time_end' => 'The work start time must be before the work end time.',

    'work_time_end.required' => 'The work end time is required.',
    'work_time_end.date' => 'The work end time must be a valid date.',
    'work_time_end.after_or_equal' => 'The work end time must be after or equal to the work start time.',
    'work_time_end.before_or_equal' => 'The work end time must be before or equal to the event end date.',

    'evaluation_start.required' => 'The evaluation start date is required.',
    'evaluation_start.date' => 'The evaluation start date must be a valid date.',
    'evaluation_start.after_or_equal' => 'The evaluation start must be after or equal to the work end date.',
    'evaluation_start.before_or_equal' => 'The evaluation start must be before or equal to the evaluation end date.',

    'evaluation_end.required' => 'The evaluation end date is required.',
    'evaluation_end.date' => 'The evaluation end date must be a valid date.',
    'evaluation_end.after_or_equal' => 'The evaluation end must be after or equal to the evaluation start date.',
    'evaluation_end.before_or_equal' => 'The evaluation end must be before or equal to the event end date.',

    'prize_type.required' => 'The "Prize type" field is required.',
    'prize_type.in' => 'The selected prize type is invalid.',

    'prize_pool.numeric' => 'The prize pool must be a number.',
    'prize_pool.max' => 'The maximum prize pool is 10,000,000.',
    'prize_pool.string' => 'The non-cash prize description must be a string.',
    'prize_pool.max_string' => 'The non-cash prize description must not exceed 255 characters.',

    'tags.array' => 'Tags must be an array.',
    'tags.*.integer' => 'Tag ID must be an integer.',
    'tags.*.exists' => 'One or more selected tags do not exist.',
];
