<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Team Sync Source
    |--------------------------------------------------------------------------
    |
    | Main external source for teams. Teams from this source are mirrored to
    | the local database and treated as read-only on Hackathon.
    |
    */
    'source' => env('TEAM_SYNC_SOURCE', 'main_site'),

    /*
    |--------------------------------------------------------------------------
    | Team Management Read-Only Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, local team management endpoints are disabled and teams are
    | expected to be managed only on the external source.
    |
    */
    'readonly' => (bool) env('TEAM_SYNC_READONLY', true),
];
