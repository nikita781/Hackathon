<?php

return [
    'title.required' => 'Das Feld "Teamname" ist erforderlich.',
    'title.max' => 'Der "Teamname" darf nicht länger als 255 Zeichen sein.',

    'members.required' => 'Teammitglieder müssen angegeben werden.',
    'members.array' => 'Mitglieder müssen ein Array sein.',

    'members.*.member_id.required_with' => 'Die Mitglieds-ID ist erforderlich, wenn Mitgliedsdaten vorhanden sind.',
    'members.*.member_id.exists' => 'Das angegebene Mitglied wurde im Team nicht gefunden.',

    'members.*.position_id.required_with' => 'Die Position des Mitglieds ist erforderlich, wenn Mitgliedsdaten vorhanden sind.',
    'members.*.position_id.exists' => 'Die angegebene Position des Mitglieds ist ungültig.',
];
