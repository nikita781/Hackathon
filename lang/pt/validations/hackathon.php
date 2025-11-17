<?php

return [
    'title.required' => 'O campo "Nome" é obrigatório.',
    'title.string' => 'O nome deve ser uma cadeia de caracteres.',
    'title.max' => 'O nome não deve exceder 255 caracteres.',
    'title.min' => 'O nome deve conter pelo menos 5 caracteres.',

    'image_path.required' => 'É necessário carregar uma imagem.',
    'image_path.image' => 'O ficheiro deve ser uma imagem.',
    'image_path.mimes' => 'A imagem deve estar no formato: jpeg, png, jpg ou webp.',
    'image_path.max' => 'O tamanho máximo da imagem é de 10 MB.',

    'format.required' => 'O campo "Formato" é obrigatório.',
    'format.in' => 'O formato selecionado é inválido.',

    'type.required' => 'O campo "Tipo de participação" é obrigatório.',
    'type.in' => 'O tipo de participação selecionado é inválido.',

    'min_team_size.required_if' => 'O tamanho mínimo da equipa é obrigatório.',
    'min_team_size.integer' => 'O tamanho mínimo da equipa deve ser um número.',
    'min_team_size.min' => 'O tamanho mínimo da equipa deve ser pelo menos 1.',
    'min_team_size.lte' => 'O tamanho mínimo da equipa não pode exceder o máximo.',
    'min_team_size.exclude_if' => 'Para participação individual, o tamanho mínimo da equipa não é especificado.',

    'max_team_size.required_if' => 'O tamanho máximo da equipa é obrigatório.',
    'max_team_size.integer' => 'O tamanho máximo da equipa deve ser um número.',
    'max_team_size.min' => 'O tamanho máximo da equipa deve ser pelo menos 1.',
    'max_team_size.gte' => 'O tamanho máximo da equipa não pode ser inferior ao mínimo.',
    'max_team_size.exclude_if' => 'Para participação individual, o tamanho máximo da equipa não é especificado.',

    'registration_start.date' => 'A data de início da inscrição deve ser válida.',
    'registration_start.before' => 'A data de início da inscrição deve ser anterior à data de término.',
    'registration_start.after' => 'A data de início da inscrição não pode ser anterior a hoje.',

    'registration_end.required' => 'A data de término da inscrição é obrigatória.',
    'registration_end.date' => 'A data de término da inscrição deve ser válida.',
    'registration_end.before_or_equal' => 'A data de término da inscrição deve ser anterior ou igual à data de início do evento.',
    'registration_end.after' => 'A data de término da inscrição não pode estar no passado.',

    'event_start.required' => 'A data de início do evento é obrigatória.',
    'event_start.date' => 'A data de início do evento deve ser válida.',
    'event_start.before' => 'A data de início do evento deve ser anterior à data de término.',

    'event_end.required' => 'A data de término do evento é obrigatória.',
    'event_end.date' => 'A data de término do evento deve ser válida.',

    'work_time_start.required' => 'A data de início do trabalho é obrigatória.',
    'work_time_start.date' => 'A data de início do trabalho deve ser válida.',
    'work_time_start.after_or_equal' => 'A data de início do trabalho não pode ser anterior ao início do evento.',
    'work_time_start.before_or_equal' => 'A data de início do trabalho não pode ser posterior ao término do evento.',

    'work_time_end.required' => 'A data de término do trabalho é obrigatória.',
    'work_time_end.date' => 'A data de término do trabalho deve ser válida.',
    'work_time_end.after_or_equal' => 'A data de término do trabalho não pode ser anterior ao início do trabalho.',
    'work_time_end.before_or_equal' => 'A data de término do trabalho não pode ser posterior ao término do evento.',

    'evaluation_start.required' => 'A data de início da avaliação é obrigatória.',
    'evaluation_start.date' => 'A data de início da avaliação deve ser válida.',
    'evaluation_start.after_or_equal' => 'A data de início da avaliação não pode ser anterior ao término do trabalho.',
    'evaluation_start.before_or_equal' => 'A data de início da avaliação não pode ser posterior à data de término da avaliação.',

    'evaluation_end.required' => 'A data de término da avaliação é obrigatória.',
    'evaluation_end.date' => 'A data de término da avaliação deve ser válida.',
    'evaluation_end.after_or_equal' => 'A data de término da avaliação não pode ser anterior ao início da avaliação.',
    'evaluation_end.before_or_equal' => 'A data de término da avaliação não pode ser posterior ao término do evento.',

    'prize_type.required' => 'O campo "Tipo de prémio" é obrigatório.',
    'prize_type.in' => 'O tipo de prémio selecionado é inválido.',

    'prize_pool.numeric' => 'O prémio deve ser um número.',
    'prize_pool.max' => 'O prémio máximo é 10 000 000.',
    'prize_pool.string' => 'Para prémios não monetários, é necessário indicar texto.',

    'tags.array' => 'As etiquetas devem ser uma matriz.',
    'tags.*.integer' => 'O ID da etiqueta deve ser um número.',
    'tags.*.exists' => 'Uma ou mais etiquetas não existem.',
];
