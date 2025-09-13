<?php

return [
    'title.required' => '“标题”字段是必填项。',
    'title.max' => '“标题”字段不能超过255个字符。',

    'description.required' => '“描述”字段是必填项。',

    'preview.required' => '必须提供预览图像。',
    'preview.image' => '文件必须是图像。',
    'preview.mimes' => '图像格式必须为 jpeg、png、jpg 或 webp。',
    'preview.max' => '图像最大大小为 5MB。',

    'about.nullable' => '“关于”字段是可选的。',
    'stack.max' => '“技术栈”字段不能超过255个字符。',

    'project_link.url' => '项目链接必须是有效的URL。',
    'project_link.starts_with' => '项目链接必须以 https://github.com/ 开头。',

    'presentation.file' => '演示文件必须是一个文件。',
    'presentation.mimes' => '演示文件格式必须为 PDF、PPT 或 PPTX。',
    'presentation.max' => '演示文件最大大小为 10MB。',

    'video_link.url' => '视频链接必须是有效的URL。',
    'video_link.starts_with' => '视频链接必须以 https://vkvideo.ru/video 或 https://rutube.ru/video/ 开头。',

    'gallery.array' => '图库必须是一个数组。',
    'gallery.*.image' => '图库中的每个文件必须是图像。',
    'gallery.*.mimes' => '每张图片格式必须为 jpeg、png、jpg 或 webp。',
    'gallery.*.max' => '每张图片最大大小为 5MB。',

    'status.in' => '所选状态无效。',

    'delete_media_ids.array' => '要删除的媒体ID必须是一个数组。',
    'delete_media_ids.*.integer' => '每个媒体ID必须是整数。',
    'delete_media_ids.*.exists' => '找不到指定的媒体文件。',

    'title.min' => '标题必须至少包含5个字符。',
    'description.min' => '描述必须至少包含10个字符。',
    'about.min' => '“关于项目”字段必须至少包含10个字符。',
];
