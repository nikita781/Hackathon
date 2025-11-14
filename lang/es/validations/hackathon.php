<?php

return [
    'title.required' => 'El campo "Título" es obligatorio.',
    'title.max' => 'El título no puede tener más de 255 caracteres.',
    'title.min' => 'El título debe tener al menos 5 caracteres.',

    'image_path.required' => 'Es necesario cargar una imagen.',
    'image_path.image' => 'El archivo debe ser una imagen.',
    'image_path.mimes' => 'La imagen debe estar en uno de los siguientes formatos: jpeg, png, jpg, webp.',
    'image_path.max' => 'El tamaño máximo de la imagen es 10 MB.',

    'format.required' => 'El campo "Formato" es obligatorio.',
    'format.in' => 'El formato seleccionado no es válido.',

    'type.required' => 'El campo "Tipo de participación" es obligatorio.',
    'type.in' => 'El tipo de participación seleccionado no es válido (individual, team).',

    'min_team_size.required_if' => 'El tamaño mínimo del equipo es obligatorio para la participación en equipo.',
    'min_team_size.integer' => 'El tamaño mínimo del equipo debe ser un número entero.',
    'min_team_size.lte' => 'El tamaño mínimo del equipo no puede superar al tamaño máximo.',
    'min_team_size.min' => 'El tamaño mínimo del equipo debe ser al menos 1.',
    'min_team_size.exclude_if' => 'El tamaño mínimo del equipo no se especifica para participación individual.',

    'max_team_size.required_if' => 'El tamaño máximo del equipo es obligatorio para la participación en equipo.',
    'max_team_size.integer' => 'El tamaño máximo del equipo debe ser un número entero.',
    'max_team_size.gte' => 'El tamaño máximo del equipo no puede ser menor que el tamaño mínimo.',
    'max_team_size.min' => 'El tamaño máximo del equipo debe ser al menos 1.',
    'max_team_size.exclude_if' => 'El tamaño máximo del equipo no se especifica para participación individual.',

    'registration_start.date' => 'La fecha de inicio de registro debe ser una fecha válida.',
    'registration_start.before' => 'La fecha de inicio de registro debe ser anterior a la fecha de finalización del registro.',

    'registration_end.required' => 'La fecha de finalización del registro es obligatoria.',
    'registration_end.date' => 'La fecha de finalización del registro debe ser una fecha válida.',
    'registration_end.before_or_equal' => 'La fecha de finalización del registro debe ser anterior o igual al inicio del evento.',

    'event_start.required' => 'La fecha de inicio del evento es obligatoria.',
    'event_start.date' => 'La fecha de inicio del evento debe ser una fecha válida.',
    'event_start.before' => 'La fecha de inicio del evento debe ser anterior a la fecha de finalización del evento.',

    'event_end.required' => 'La fecha de finalización del evento es obligatoria.',
    'event_end.date' => 'La fecha de finalización del evento debe ser una fecha válida.',

    'work_time_start.required' => 'La fecha de inicio del trabajo es obligatoria.',
    'work_time_start.date' => 'La fecha de inicio del trabajo debe ser válida.',
    'work_time_start.after_or_equal' => 'El inicio del trabajo debe ser igual o posterior al inicio del evento.',
    'work_time_start.before_or_equal' => 'El inicio del trabajo debe ser igual o anterior al final del evento.',
    'work_time_start.before_or_equal_work_time_end' => 'El inicio del trabajo debe ser anterior al final del trabajo.',

    'work_time_end.required' => 'La fecha de finalización del trabajo es obligatoria.',
    'work_time_end.date' => 'La fecha de finalización del trabajo debe ser válida.',
    'work_time_end.after_or_equal' => 'El final del trabajo debe ser igual o posterior al inicio del trabajo.',
    'work_time_end.before_or_equal' => 'El final del trabajo debe ser igual o anterior al final del evento.',

    'evaluation_start.required' => 'La fecha de inicio de la evaluación es obligatoria.',
    'evaluation_start.date' => 'La fecha de inicio de la evaluación debe ser válida.',
    'evaluation_start.after_or_equal' => 'El inicio de la evaluación debe ser posterior o igual al final del trabajo.',
    'evaluation_start.before_or_equal' => 'El inicio de la evaluación debe ser anterior o igual al final de la evaluación.',

    'evaluation_end.required' => 'La fecha de finalización de la evaluación es obligatoria.',
    'evaluation_end.date' => 'La fecha de finalización de la evaluación debe ser válida.',
    'evaluation_end.after_or_equal' => 'El final de la evaluación debe ser posterior o igual al inicio de la evaluación.',
    'evaluation_end.before_or_equal' => 'El final de la evaluación debe ser anterior o igual al final del evento.',

    'prize_type.required' => 'El campo "Tipo de premio" es obligatorio.',
    'prize_type.in' => 'El tipo de premio seleccionado no es válido.',

    'prize_pool.numeric' => 'El monto del premio debe ser un número.',
    'prize_pool.max' => 'El monto máximo del premio es 10.000.000.',
    'prize_pool.string' => 'La descripción del premio no monetario debe ser una cadena.',
    'prize_pool.max_string' => 'La descripción del premio no monetario no puede superar los 255 caracteres.',

    'tags.array' => 'Los tags deben ser un arreglo.',
    'tags.*.integer' => 'El ID del tag debe ser un número entero.',
    'tags.*.exists' => 'Uno o varios tags seleccionados no existen.',
];
