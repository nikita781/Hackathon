<?php

return [
    'title.required' => 'El campo "Nombre del equipo" es obligatorio.',
    'title.max' => 'El "Nombre del equipo" no debe exceder los 255 caracteres.',

    'members.required' => 'Se deben especificar los miembros del equipo.',
    'members.array' => 'Los miembros deben ser un arreglo.',

    'members.*.member_id.required_with' => 'El ID del miembro es obligatorio cuando hay datos del miembro.',
    'members.*.member_id.exists' => 'El miembro especificado no fue encontrado en el equipo.',

    'members.*.position_id.required_with' => 'El cargo del miembro es obligatorio cuando hay datos del miembro.',
    'members.*.position_id.exists' => 'El cargo del miembro especificado no es válido.',
];
