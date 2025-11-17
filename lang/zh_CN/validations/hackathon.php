<?php

return [
    'title.required' => '“名称”字段是必填项。',
    'title.string' => '名称必须是字符串。',
    'title.max' => '名称不得超过 255 个字符。',
    'title.min' => '名称必须至少包含 5 个字符。',

    'image_path.required' => '必须上传图片。',
    'image_path.image' => '文件必须是图片。',
    'image_path.mimes' => '图片格式必须为：jpeg、png、jpg 或 webp。',
    'image_path.max' => '图片大小不得超过 10 MB。',

    'format.required' => '“格式”字段是必填项。',
    'format.in' => '选择的格式无效。',

    'type.required' => '“参与类型”字段是必填项。',
    'type.in' => '选择的参与类型无效。',

    'min_team_size.required_if' => '团队最小人数是必填项。',
    'min_team_size.integer' => '团队最小人数必须是数字。',
    'min_team_size.min' => '团队最小人数不得少于 1。',
    'min_team_size.lte' => '团队最小人数不得大于最大人数。',
    'min_team_size.exclude_if' => '对于个人参与，不需要指定团队最小人数。',

    'max_team_size.required_if' => '团队最大人数是必填项。',
    'max_team_size.integer' => '团队最大人数必须是数字。',
    'max_team_size.min' => '团队最大人数不得少于 1。',
    'max_team_size.gte' => '团队最大人数不得小于最小人数。',
    'max_team_size.exclude_if' => '对于个人参与，不需要指定团队最大人数。',

    'registration_start.date' => '注册开始日期必须是有效日期。',
    'registration_start.before' => '注册开始日期必须早于注册结束日期。',
    'registration_start.after' => '报名开始日期不能早于今天。',

    'registration_end.required' => '注册结束日期是必填项。',
    'registration_end.date' => '注册结束日期必须是有效日期。',
    'registration_end.before_or_equal' => '注册结束日期必须早于或等于活动开始日期。',
    'registration_end.after' => '报名截止日期不能是过去的时间。',

    'event_start.required' => '活动开始日期是必填项。',
    'event_start.date' => '活动开始日期必须是有效日期。',
    'event_start.before' => '活动开始日期必须早于活动结束日期。',

    'event_end.required' => '活动结束日期是必填项。',
    'event_end.date' => '活动结束日期必须是有效日期。',

    'work_time_start.required' => '工作开始日期是必填项。',
    'work_time_start.date' => '工作开始日期必须是有效日期。',
    'work_time_start.after_or_equal' => '工作开始日期不得早于活动开始日期。',
    'work_time_start.before_or_equal' => '工作开始日期不得晚于活动结束日期。',

    'work_time_end.required' => '工作结束日期是必填项。',
    'work_time_end.date' => '工作结束日期必须是有效日期。',
    'work_time_end.after_or_equal' => '工作结束日期不得早于工作开始日期。',
    'work_time_end.before_or_equal' => '工作结束日期不得晚于活动结束日期。',

    'evaluation_start.required' => '评审开始日期是必填项。',
    'evaluation_start.date' => '评审开始日期必须是有效日期。',
    'evaluation_start.after_or_equal' => '评审开始日期不得早于工作结束日期。',
    'evaluation_start.before_or_equal' => '评审开始日期不得晚于评审结束日期。',

    'evaluation_end.required' => '评审结束日期是必填项。',
    'evaluation_end.date' => '评审结束日期必须是有效日期。',
    'evaluation_end.after_or_equal' => '评审结束日期不得早于评审开始日期。',
    'evaluation_end.before_or_equal' => '评审结束日期不得晚于活动结束日期。',

    'prize_type.required' => '“奖项类型”字段是必填项。',
    'prize_type.in' => '选择的奖项类型无效。',

    'prize_pool.numeric' => '奖金额必须是数字。',
    'prize_pool.max' => '最大奖金额为 10,000,000。',
    'prize_pool.string' => '非金钱奖项必须填写文本说明。',

    'tags.array' => '标签必须是数组。',
    'tags.*.integer' => '标签 ID 必须是数字。',
    'tags.*.exists' => '一个或多个标签不存在。',
];
