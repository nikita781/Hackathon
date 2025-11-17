<?php

return [
    'title.required' => 'Le champ "Titre" est obligatoire.',
    'title.max' => 'Le champ "Titre" ne doit pas dépasser 255 caractères.',
    'title.min' => 'Le champ "Titre" doit contenir au moins 5 caractères.',

    'image_path.required' => 'Une image est requise.',
    'image_path.image' => 'Le fichier doit être une image.',
    'image_path.mimes' => 'L’image doit être dans l’un des formats suivants : jpeg, png, jpg, webp.',
    'image_path.max' => 'La taille maximale de l’image est de 10 Mo.',

    'format.required' => 'Le champ "Format" est obligatoire.',
    'format.in' => 'Le format sélectionné est invalide.',

    'type.required' => 'Le champ "Type de participation" est obligatoire.',
    'type.in' => 'Le type de participation sélectionné est invalide (individual, team).',

    'min_team_size.required_if' => 'La taille minimale de l’équipe est requise pour une participation en équipe.',
    'min_team_size.integer' => 'La taille minimale de l’équipe doit être un nombre entier.',
    'min_team_size.lte' => 'La taille minimale de l’équipe ne peut pas dépasser la taille maximale.',
    'min_team_size.min' => 'La taille minimale de l’équipe doit être au moins 1.',
    'min_team_size.exclude_if' => 'La taille minimale de l’équipe n’est pas utilisée pour une participation individuelle.',

    'max_team_size.required_if' => 'La taille maximale de l’équipe est requise pour une participation en équipe.',
    'max_team_size.integer' => 'La taille maximale de l’équipe doit être un nombre entier.',
    'max_team_size.gte' => 'La taille maximale de l’équipe ne peut pas être inférieure à la taille minimale.',
    'max_team_size.min' => 'La taille maximale de l’équipe doit être au moins 1.',
    'max_team_size.exclude_if' => 'La taille maximale de l’équipe n’est pas utilisée pour une participation individuelle.',

    'registration_start.date' => 'La date de début d’enregistrement doit être une date valide.',
    'registration_start.before' => 'La date de début d’enregistrement doit être avant la date de fin d’enregistrement.',
    'registration_start.after' => 'La date de début d’inscription ne peut pas être antérieure à aujourd’hui.',

    'registration_end.required' => 'La date de fin d’enregistrement est obligatoire.',
    'registration_end.date' => 'La date de fin d’enregistrement doit être une date valide.',
    'registration_end.before_or_equal' => 'La date de fin d’enregistrement doit être antérieure ou égale à la date de début de l’événement.',
    'registration_end.after' => 'La date de fin d’inscription ne peut pas être dans le passé.',

    'event_start.required' => 'La date de début de l’événement est obligatoire.',
    'event_start.date' => 'La date de début de l’événement doit être une date valide.',
    'event_start.before' => 'La date de début de l’événement doit être avant la date de fin de l’événement.',

    'event_end.required' => 'La date de fin de l’événement est obligatoire.',
    'event_end.date' => 'La date de fin de l’événement doit être une date valide.',

    'work_time_start.required' => 'La date de début du travail est obligatoire.',
    'work_time_start.date' => 'La date de début du travail doit être une date valide.',
    'work_time_start.after_or_equal' => 'La date de début du travail doit être égale ou postérieure à la date de début de l’événement.',
    'work_time_start.before_or_equal' => 'La date de début du travail doit être antérieure ou égale à la date de fin de l’événement.',
    'work_time_start.before_or_equal_work_time_end' => 'La date de début du travail doit être avant la date de fin du travail.',

    'work_time_end.required' => 'La date de fin du travail est obligatoire.',
    'work_time_end.date' => 'La date de fin du travail doit être une date valide.',
    'work_time_end.after_or_equal' => 'La date de fin du travail doit être égale ou postérieure à la date de début du travail.',
    'work_time_end.before_or_equal' => 'La date de fin du travail doit être antérieure ou égale à la date de fin de l’événement.',

    'evaluation_start.required' => 'La date de début de l’évaluation est obligatoire.',
    'evaluation_start.date' => 'La date de début de l’évaluation doit être une date valide.',
    'evaluation_start.after_or_equal' => 'La date de début de l’évaluation doit être égale ou postérieure à la date de fin du travail.',
    'evaluation_start.before_or_equal' => 'La date de début de l’évaluation doit être avant ou égale à la date de fin de l’évaluation.',

    'evaluation_end.required' => 'La date de fin de l’évaluation est obligatoire.',
    'evaluation_end.date' => 'La date de fin de l’évaluation doit être une date valide.',
    'evaluation_end.after_or_equal' => 'La date de fin de l’évaluation doit être égale ou postérieure à la date de début de l’évaluation.',
    'evaluation_end.before_or_equal' => 'La date de fin de l’évaluation doit être avant ou égale à la date de fin de l’événement.',

    'prize_type.required' => 'Le champ "Type de prix" est obligatoire.',
    'prize_type.in' => 'Le type de prix sélectionné est invalide.',

    'prize_pool.numeric' => 'Le montant du prix doit être un nombre.',
    'prize_pool.max' => 'Le montant maximal du prix est de 10 000 000.',
    'prize_pool.string' => 'La description du prix non-monétaire doit être une chaîne de caractères.',
    'prize_pool.max_string' => 'La description du prix non-monétaire ne doit pas dépasser 255 caractères.',

    'tags.array' => 'Les tags doivent être un tableau.',
    'tags.*.integer' => 'L’ID du tag doit être un nombre entier.',
    'tags.*.exists' => 'Un ou plusieurs tags sélectionnés n’existent pas.',
];
