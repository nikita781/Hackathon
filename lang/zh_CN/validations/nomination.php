<?php

return [
    'title.required' => '“提名标题”字段为必填项。',
    'title.max' => '提名标题不能超过255个字符。',
    'prize.required' => '“提名奖品”字段为必填项。',
    'prize.max' => '提名奖品不能超过255个字符。',
    'places.array' => '“获奖名次”字段必须是数组。',
    'places.*.place.required_with' => '指定奖项名次时，“名次”字段为必填项。',
    'places.*.place.integer' => '“名次”字段必须是数字。',
    'places.*.prize.required_with' => '指定奖项名次时，“奖品”字段为必填项。',
    'places.*.prize.max' => '奖项不能超过255个字符。',
];
