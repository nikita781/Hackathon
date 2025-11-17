<?php

return [
    'title.required' => 'Das Feld „Titel“ ist erforderlich.',
    'title.max' => 'Der Titel darf nicht länger als 255 Zeichen sein.',
    'title.min' => 'Der Titel muss mindestens 5 Zeichen enthalten.',

    'image_path.required' => 'Ein Bild muss hochgeladen werden.',
    'image_path.image' => 'Die Datei muss ein Bild sein.',
    'image_path.mimes' => 'Das Bild muss eines der folgenden Formate haben: jpeg, png, jpg, webp.',
    'image_path.max' => 'Die maximale Bildgröße beträgt 10 MB.',

    'format.required' => 'Das Feld „Format“ ist erforderlich.',
    'format.in' => 'Das ausgewählte Format ist ungültig.',

    'type.required' => 'Das Feld „Teilnahmetyp“ ist erforderlich.',
    'type.in' => 'Der ausgewählte Teilnahmetyp ist ungültig (individual, team).',

    'min_team_size.required_if' => 'Die minimale Teamgröße ist für Teamteilnahme erforderlich.',
    'min_team_size.integer' => 'Die minimale Teamgröße muss eine ganze Zahl sein.',
    'min_team_size.lte' => 'Die minimale Teamgröße darf die maximale Teamgröße nicht überschreiten.',
    'min_team_size.min' => 'Die minimale Teamgröße muss mindestens 1 sein.',
    'min_team_size.exclude_if' => 'Die minimale Teamgröße wird bei individueller Teilnahme nicht verwendet.',

    'max_team_size.required_if' => 'Die maximale Teamgröße ist für Teamteilnahme erforderlich.',
    'max_team_size.integer' => 'Die maximale Teamgröße muss eine ganze Zahl sein.',
    'max_team_size.gte' => 'Die maximale Teamgröße darf nicht kleiner als die minimale Teamgröße sein.',
    'max_team_size.min' => 'Die maximale Teamgröße muss mindestens 1 sein.',
    'max_team_size.exclude_if' => 'Die maximale Teamgröße wird bei individueller Teilnahme nicht verwendet.',

    'registration_start.date' => 'Das Startdatum der Registrierung muss ein gültiges Datum sein.',
    'registration_start.before' => 'Das Startdatum der Registrierung muss vor dem Enddatum der Registrierung liegen.',
    'registration_start.after' => 'Das Startdatum der Registrierung darf nicht vor dem heutigen Tag liegen.',

    'registration_end.required' => 'Das Enddatum der Registrierung ist erforderlich.',
    'registration_end.date' => 'Das Enddatum der Registrierung muss ein gültiges Datum sein.',
    'registration_end.before_or_equal' => 'Das Enddatum der Registrierung muss vor oder gleich dem Veranstaltungsbeginn liegen.',
    'registration_end.after' => 'Das Registrierungsende darf nicht in der Vergangenheit liegen.',

    'event_start.required' => 'Das Startdatum der Veranstaltung ist erforderlich.',
    'event_start.date' => 'Das Startdatum der Veranstaltung muss ein gültiges Datum sein.',
    'event_start.before' => 'Das Startdatum der Veranstaltung muss vor dem Enddatum der Veranstaltung liegen.',

    'event_end.required' => 'Das Enddatum der Veranstaltung ist erforderlich.',
    'event_end.date' => 'Das Enddatum der Veranstaltung muss ein gültiges Datum sein.',

    'work_time_start.required' => 'Das Startdatum der Arbeitsphase ist erforderlich.',
    'work_time_start.date' => 'Das Startdatum der Arbeitsphase muss ein gültiges Datum sein.',
    'work_time_start.after_or_equal' => 'Das Startdatum der Arbeitsphase muss gleich oder nach dem Veranstaltungsbeginn liegen.',
    'work_time_start.before_or_equal' => 'Das Startdatum der Arbeitsphase muss vor oder gleich dem Veranstaltungsende liegen.',
    'work_time_start.before_or_equal_work_time_end' => 'Das Startdatum der Arbeitsphase muss vor dem Ende der Arbeitsphase liegen.',

    'work_time_end.required' => 'Das Enddatum der Arbeitsphase ist erforderlich.',
    'work_time_end.date' => 'Das Enddatum der Arbeitsphase muss ein gültiges Datum sein.',
    'work_time_end.after_or_equal' => 'Das Enddatum der Arbeitsphase muss gleich oder nach dem Beginn der Arbeitsphase liegen.',
    'work_time_end.before_or_equal' => 'Das Enddatum der Arbeitsphase muss vor oder gleich dem Veranstaltungsende liegen.',

    'evaluation_start.required' => 'Das Startdatum der Bewertung ist erforderlich.',
    'evaluation_start.date' => 'Das Startdatum der Bewertung muss ein gültiges Datum sein.',
    'evaluation_start.after_or_equal' => 'Das Startdatum der Bewertung muss gleich oder nach dem Ende der Arbeitsphase liegen.',
    'evaluation_start.before_or_equal' => 'Das Startdatum der Bewertung muss vor oder gleich dem Enddatum der Bewertung liegen.',

    'evaluation_end.required' => 'Das Enddatum der Bewertung ist erforderlich.',
    'evaluation_end.date' => 'Das Enddatum der Bewertung muss ein gültiges Datum sein.',
    'evaluation_end.after_or_equal' => 'Das Enddatum der Bewertung muss gleich oder nach dem Beginn der Bewertung liegen.',
    'evaluation_end.before_or_equal' => 'Das Enddatum der Bewertung muss vor oder gleich dem Veranstaltungsende liegen.',

    'prize_type.required' => 'Das Feld „Preisart“ ist erforderlich.',
    'prize_type.in' => 'Die ausgewählte Preisart ist ungültig.',

    'prize_pool.numeric' => 'Der Preisbetrag muss eine Zahl sein.',
    'prize_pool.max' => 'Der maximale Preisbetrag beträgt 10.000.000.',
    'prize_pool.string' => 'Die Beschreibung eines nicht-monetären Preises muss eine Zeichenkette sein.',
    'prize_pool.max_string' => 'Die Beschreibung eines nicht-monetären Preises darf 255 Zeichen nicht überschreiten.',

    'tags.array' => 'Die Tags müssen ein Array sein.',
    'tags.*.integer' => 'Die Tag-ID muss eine ganze Zahl sein.',
    'tags.*.exists' => 'Ein oder mehrere ausgewählte Tags existieren nicht.',
];
