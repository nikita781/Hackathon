<?php

return [
    'title.required' => 'El campo "Título de la pestaña" es obligatorio.',
    'title.in' => 'El título de la pestaña no es válido.',

    'sections.array' => 'Las secciones deben ser un arreglo.',
    'sections.*.id.integer' => 'El ID de la sección debe ser un número entero.',
    'sections.*.id.exists' => 'Sección no encontrada.',
    'sections.*.title.required_with' => 'El título de la sección es obligatorio.',
    'sections.*.title.string' => 'El título de la sección debe ser una cadena.',
    'sections.*.title.max' => 'El título de la sección no debe exceder los 255 caracteres.',
    'sections.*.content.string' => 'El contenido de la sección debe ser una cadena.',
    'sections.*.content.max' => 'El contenido de la sección no debe exceder los 65535 caracteres.',

    'sections.*.items.array' => 'Los elementos de la sección deben ser un arreglo.',
    'sections.*.items.*.id.integer' => 'El ID del elemento debe ser un número entero.',
    'sections.*.items.*.id.exists' => 'Elemento no encontrado.',
    'sections.*.items.*.title.required_with' => 'El título del elemento es obligatorio.',
    'sections.*.items.*.title.string' => 'El título del elemento debe ser una cadena.',
    'sections.*.items.*.title.max' => 'El título del elemento no debe exceder los 255 caracteres.',
    'sections.*.items.*.content.max' => 'El contenido del elemento no debe exceder los 65535 caracteres.',
    'sections.*.items.*.image_path.image' => 'El archivo debe ser una imagen.',
    'sections.*.items.*.image_path.mimes' => 'La imagen debe estar en formato jpeg, png, jpg o webp.',
    'sections.*.items.*.image_path.max' => 'El tamaño máximo de la imagen es de 2 MB.',

    'partners.array' => 'Los socios deben ser un arreglo.',
    'partners.*.image' => 'Cada socio debe ser una imagen.',
    'partners.*.max' => 'El tamaño máximo de la imagen de un socio es de 2 MB.',

    'files.array' => 'Los archivos deben ser un arreglo.',
    'files.*.file' => 'Cada archivo debe ser válido.',
    'files.*.max' => 'El tamaño máximo de un archivo es de 2 MB.',

    'delete_media_ids.array' => 'Los IDs de medios para eliminar deben ser un arreglo.',
    'delete_media_ids.*.integer' => 'El ID del medio debe ser un número entero.',
    'delete_media_ids.*.exists' => 'Algunos de los medios especificados no se encontraron.',
];
