<?php

return [
    'title.required' => 'The "Tab title" field is required.',
    'title.in' => 'The tab title is invalid.',

    'sections.array' => 'Sections must be an array.',
    'sections.*.id.integer' => 'Section ID must be an integer.',
    'sections.*.id.exists' => 'Section not found.',
    'sections.*.title.required_with' => 'Section title is required.',
    'sections.*.title.string' => 'Section title must be a string.',
    'sections.*.title.max' => 'Section title must not exceed 255 characters.',
    'sections.*.content.string' => 'Section content must be a string.',
    'sections.*.content.max' => 'Section content must not exceed 65535 characters.',

    'sections.*.items.array' => 'Section items must be an array.',
    'sections.*.items.*.id.integer' => 'Item ID must be an integer.',
    'sections.*.items.*.id.exists' => 'Item not found.',
    'sections.*.items.*.title.required_with' => 'Item title is required.',
    'sections.*.items.*.title.string' => 'Item title must be a string.',
    'sections.*.items.*.title.max' => 'Item title must not exceed 255 characters.',
    'sections.*.items.*.content.max' => 'Item content must not exceed 65535 characters.',
    'sections.*.items.*.image_path.image' => 'The file must be an image.',
    'sections.*.items.*.image_path.mimes' => 'The image must be in jpeg, png, jpg, or webp format.',
    'sections.*.items.*.image_path.max' => 'The maximum image size is 2 MB.',

    'partners.array' => 'Partners must be an array.',
    'partners.*.image' => 'Each partner must be an image.',
    'partners.*.max' => 'The maximum image size for a partner is 2 MB.',

    'files.array' => 'Files must be an array.',
    'files.*.file' => 'Each file must be valid.',
    'files.*.max' => 'The maximum file size is 2 MB.',

    'delete_media_ids.array' => 'Media IDs for deletion must be an array.',
    'delete_media_ids.*.integer' => 'Media ID must be an integer.',
    'delete_media_ids.*.exists' => 'Some of the specified media were not found.',
];
