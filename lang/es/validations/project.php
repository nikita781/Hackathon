<?php

return [
    'title.required' => 'El campo "Título" es obligatorio.',
    'title.max' => 'El campo "Título" no debe exceder los 255 caracteres.',

    'description.required' => 'El campo "Descripción" es obligatorio.',

    'preview.required' => 'Se requiere una imagen de vista previa.',
    'preview.image' => 'El archivo debe ser una imagen.',
    'preview.mimes' => 'La imagen debe estar en formato jpeg, png, jpg o webp.',
    'preview.max' => 'El tamaño máximo de la imagen es de 5 MB.',

    'about.nullable' => 'El campo "Acerca de" es opcional.',
    'stack.max' => 'El campo "Stack tecnológico" no debe exceder los 255 caracteres.',

    'project_link.url' => 'El enlace del proyecto debe ser una URL válida.',
    'project_link.starts_with' => 'El enlace del proyecto debe comenzar con https://github.com/',

    'presentation.file' => 'La presentación debe ser un archivo.',
    'presentation.mimes' => 'La presentación debe estar en formato PDF, PPT o PPTX.',
    'presentation.max' => 'El tamaño máximo del archivo de presentación es de 10 MB.',

    'video_link.url' => 'El enlace del video debe ser una URL válida.',
    'video_link.starts_with' => 'El enlace del video debe comenzar con https://vkvideo.ru/video o https://rutube.ru/video/',

    'gallery.array' => 'La galería debe ser un arreglo.',
    'gallery.*.image' => 'Cada archivo en la galería debe ser una imagen.',
    'gallery.*.mimes' => 'Cada imagen debe estar en formato jpeg, png, jpg o webp.',
    'gallery.*.max' => 'El tamaño máximo de cada imagen es de 5 MB.',

    'status.in' => 'El estado seleccionado no es válido.',

    'delete_media_ids.array' => 'Los IDs de medios a eliminar deben estar en un arreglo.',
    'delete_media_ids.*.integer' => 'Cada ID de medio debe ser un número entero.',
    'delete_media_ids.*.exists' => 'Algunos de los medios especificados no fueron encontrados.',

    'title.min' => 'El título debe tener al menos 5 caracteres.',
    'description.min' => 'La descripción debe tener al menos 10 caracteres.',
    'about.min' => 'El campo "Sobre el proyecto" debe tener al menos 10 caracteres.',
];
