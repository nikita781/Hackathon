<?php

return [
    'title.required' => '“团队名称”字段为必填项。',
    'title.max' => '“团队名称”不能超过255个字符。',
    'members.required' => '必须指定团队成员。',
    'members.array' => '成员必须是数组。',
    'members.*.member_id.required_with' => '存在成员数据时必须填写成员ID。',
    'members.*.member_id.exists' => '团队中未找到指定的成员。',
    'members.*.position_id.required_with' => '存在成员数据时必须填写职位。',
    'members.*.position_id.exists' => '所选成员职位无效。',
];
