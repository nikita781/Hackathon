<?php

return [
    'title.required' => 'Le champ "Nom de l\'équipe" est requis.',
    'title.max' => 'Le "Nom de l\'équipe" ne doit pas dépasser 255 caractères.',

    'members.required' => 'Les membres de l\'équipe doivent être spécifiés.',
    'members.array' => 'Les membres doivent être un tableau.',

    'members.*.member_id.required_with' => 'L\'ID du membre est requis lorsque des données sont présentes.',
    'members.*.member_id.exists' => 'Le membre spécifié est introuvable dans l\'équipe.',

    'members.*.position_id.required_with' => 'Le poste du membre est requis lorsque des données sont présentes.',
    'members.*.position_id.exists' => 'Le poste du membre spécifié est invalide.',
];
