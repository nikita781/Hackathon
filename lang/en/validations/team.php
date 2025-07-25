<?php

return [
    'title.required' => 'The "Team name" field is required.',
    'title.max' => 'The "Team name" must not exceed 255 characters.',

    'members.required' => 'Team members must be specified.',
    'members.array' => 'Members must be an array.',

    'members.*.member_id.required_with' => 'Member ID is required when member data is present.',
    'members.*.member_id.exists' => 'The specified member was not found in the team.',

    'members.*.position_id.required_with' => 'Member position is required when member data is present.',
    'members.*.position_id.exists' => 'The specified member position is invalid.',
];
