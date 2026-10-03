<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application des contrôles
    |--------------------------------------------------------------------------
    |
    | À `false`, le middleware d'autorisation journalise les refus sans bloquer :
    | c'est le mode observation, qui sert à mesurer l'usage réel des écrans avant de
    | fermer les accès. À `true`, un refus devient un 403.
    |
    | Cette bascule est la seule procédure de retour arrière après l'activation des
    | contrôles : aucune migration n'est à défaire.
    |
    */

    'enforce' => env('RBAC_ENFORCE', false),

    /*
    |--------------------------------------------------------------------------
    | Durée de vie du cache des permissions
    |--------------------------------------------------------------------------
    |
    | Les permissions effectives d'un compte sont mises en cache sous une clé
    | versionnée, pour ne pas rejouer deux jointures à chaque contrôle. Le cache est
    | invalidé par incrément de version dès qu'un rôle ou une dérogation change ; le
    | délai ci-dessous n'est qu'un filet de sécurité.
    |
    */

    'cache_ttl' => env('RBAC_CACHE_TTL', 600),

    /*
    |--------------------------------------------------------------------------
    | Canal de journalisation
    |--------------------------------------------------------------------------
    */

    'log_channel' => env('RBAC_LOG_CHANNEL', 'rbac'),

];
