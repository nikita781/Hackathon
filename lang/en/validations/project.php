<?php

return [
    'title.required' => 'The "Title" field is required.',
    'title.max' => 'The "Title" field must not exceed 255 characters.',

    'description.required' => 'The "Description" field is required.',

    'preview.required' => 'The "Preview" image is required.',
    'preview.image' => 'The file must be an image.',
    'preview.mimes' => 'The image must be in jpeg, png, jpg, or webp format.',
    'preview.max' => 'The maximum image size is 5 MB.',

    'about.nullable' => 'The "About" field must be optional.',
    'stack.max' => 'The "Stack" field must not exceed 255 characters.',

    'project_link.url' => 'The "Project link" must be a valid URL.',
    'project_link.starts_with' => 'The project link must start with https://github.com/.',

    'presentation.file' => 'The "Presentation" must be a file.',
    'presentation.mimes' => 'The presentation must be in PDF, PPT, or PPTX format.',
    'presentation.max' => 'The maximum presentation file size is 10 MB.',

    'video_link.url' => 'The "Video link" must be a valid URL.',
    'video_link.starts_with' => 'The video link must start with https://vkvideo.ru/video or https://rutube.ru/video/.',

    'gallery.array' => 'The "Gallery" must be an array.',
    'gallery.*.image' => 'Each gallery file must be an image.',
    'gallery.*.mimes' => 'Each image must be in jpeg, png, jpg, or webp format.',
    'gallery.*.max' => 'Each image must not exceed 5 MB.',

    'status.in' => 'The selected status is invalid.',

    'delete_media_ids.array' => 'The "Media IDs for deletion" must be an array.',
    'delete_media_ids.*.integer' => 'Each media ID must be an integer.',
    'delete_media_ids.*.exists' => 'Some of the selected media were not found.',

    'title.min' => 'Title must be at least 5 characters long.',
    'description.min' => 'Description must be at least 10 characters long.',
    'about.min' => '"About the project" must be at least 10 characters long.',
];
