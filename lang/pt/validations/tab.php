<?php

return [
    'title.required' => 'O campo "Título da aba" é obrigatório.',
    'title.in' => 'O título da aba é inválido.',

    'sections.array' => 'As seções devem ser um array.',
    'sections.*.id.integer' => 'O ID da seção deve ser um número inteiro.',
    'sections.*.id.exists' => 'Seção não encontrada.',
    'sections.*.title.required_with' => 'O título da seção é obrigatório.',
    'sections.*.title.string' => 'O título da seção deve ser uma string.',
    'sections.*.title.max' => 'O título da seção não pode exceder 255 caracteres.',
    'sections.*.content.string' => 'O conteúdo da seção deve ser uma string.',
    'sections.*.content.max' => 'O conteúdo da seção não pode exceder 65535 caracteres.',

    'sections.*.items.array' => 'Os itens da seção devem ser um array.',
    'sections.*.items.*.id.integer' => 'O ID do item deve ser um número inteiro.',
    'sections.*.items.*.id.exists' => 'Item não encontrado.',
    'sections.*.items.*.title.required_with' => 'O título do item é obrigatório.',
    'sections.*.items.*.title.string' => 'O título do item deve ser uma string.',
    'sections.*.items.*.title.max' => 'O título do item não pode exceder 255 caracteres.',
    'sections.*.items.*.content.max' => 'O conteúdo do item não pode exceder 65535 caracteres.',
    'sections.*.items.*.image_path.image' => 'O arquivo deve ser uma imagem.',
    'sections.*.items.*.image_path.mimes' => 'A imagem deve estar em formato jpeg, png, jpg ou webp.',
    'sections.*.items.*.image_path.max' => 'O tamanho máximo da imagem é 2 MB.',

    'partners.array' => 'Os parceiros devem ser um array.',
    'partners.*.image' => 'Cada parceiro deve ser uma imagem.',
    'partners.*.max' => 'O tamanho máximo da imagem para um parceiro é 2 MB.',

    'files.array' => 'Os arquivos devem ser um array.',
    'files.*.file' => 'Cada arquivo deve ser válido.',
    'files.*.max' => 'O tamanho máximo do arquivo é 2 MB.',

    'delete_media_ids.array' => 'Os IDs de mídia para exclusão devem ser um array.',
    'delete_media_ids.*.integer' => 'O ID de mídia deve ser um número inteiro.',
    'delete_media_ids.*.exists' => 'Algumas das mídias especificadas não foram encontradas.',
];
