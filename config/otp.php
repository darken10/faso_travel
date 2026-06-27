<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mode "log uniquement" (développement)
    |--------------------------------------------------------------------------
    | Quand activé, l'OTP des canaux téléphoniques (SMS / WhatsApp / Telegram)
    | n'est pas réellement envoyé mais écrit dans les logs
    | (storage/logs/laravel.log). Pratique en local sans compte Twilio valide.
    | L'e-mail reste toujours envoyé normalement.
    | À laisser à false en production.
    */
    'log_only' => env('OTP_LOG_ONLY', false),
];
