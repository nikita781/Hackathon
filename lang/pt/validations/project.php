<?php

return [
    'title.required' => 'O campo "Título" é obrigatório.',
    'title.max' => 'O campo "Título" não deve exceder 255 caracteres.',

    'description.required' => 'O campo "Descrição" é obrigatório.',

    'preview.required' => 'A imagem de pré-visualização é obrigatória.',
    'preview.image' => 'O arquivo deve ser uma imagem.',
    'preview.mimes' => 'A imagem deve estar no formato jpeg, png, jpg ou webp.',
    'preview.max' => 'O tamanho máximo da imagem é 5 MB.',

    'about.nullable' => 'O campo "Sobre" é opcional.',
    'stack.max' => 'O campo "Stack tecnológico" não deve exceder 255 caracteres.',

    'project_link.url' => 'O link do projeto deve ser uma URL válida.',
    'project_link.starts_with' => 'O link do projeto deve começar com https://github.com/',

    'presentation.file' => 'A apresentação deve ser um arquivo.',
    'presentation.mimes' => 'A apresentação deve estar no formato PDF, PPT ou PPTX.',
    'presentation.max' => 'O tamanho máximo do arquivo de apresentação é 10 MB.',

    'video_link.url' => 'O link do vídeo deve ser uma URL válida.',
    'video_link.starts_with' => 'O link do vídeo deve começar com https://vkvideo.ru/video ou https://rutube.ru/video/',

    'gallery.array' => 'A galeria deve ser um array.',
    'gallery.*.image' => 'Cada arquivo na galeria deve ser uma imagem.',
    'gallery.*.mimes' => 'Cada imagem deve estar no formato jpeg, png, jpg ou webp.',
    'gallery.*.max' => 'O tamanho máximo de cada imagem é 5 MB.',

    'status.in' => 'O status selecionado é inválido.',

    'delete_media_ids.array' => 'Os IDs de mídia para exclusão devem ser um array.',
    'delete_media_ids.*.integer' => 'Cada ID de mídia deve ser um número inteiro.',
    'delete_media_ids.*.exists' => 'Alguns dos arquivos de mídia especificados não foram encontrados.',

    'title.min' => 'O título deve ter pelo menos 5 caracteres.',
    'description.min' => 'A descrição deve ter pelo menos 10 caracteres.',
    'about.min' => 'O campo "Sobre o projeto" deve ter pelo menos 10 caracteres.',
];
