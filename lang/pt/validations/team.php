<?php

return [
    'title.required' => 'O campo "Nome da equipe" é obrigatório.',
    'title.max' => 'O "Nome da equipe" não pode exceder 255 caracteres.',

    'members.required' => 'Os membros da equipe devem ser especificados.',
    'members.array' => 'Os membros devem ser um array.',

    'members.*.member_id.required_with' => 'O ID do membro é obrigatório quando os dados do membro estão presentes.',
    'members.*.member_id.exists' => 'O membro especificado não foi encontrado na equipe.',

    'members.*.position_id.required_with' => 'A posição do membro é obrigatória quando os dados do membro estão presentes.',
    'members.*.position_id.exists' => 'A posição do membro especificada é inválida.',
];
