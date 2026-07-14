<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Uploads temporaires
    |---------------------------------------------------------------------------
    |
    | Seule section publiée (le reste garde les valeurs par défaut de Livewire).
    | La règle par défaut plafonne les fichiers temporaires à 12 Mo, ce qui
    | rejetait silencieusement (« failed to upload ») les vidéos et photos
    | volumineuses avant même nos règles de validation par champ (20 Mo max
    | pour les médias marketplace). On relève le plafond à 25 Mo : les limites
    | réelles restent celles validées champ par champ dans les composants.
    |
    */

    'temporary_file_upload' => [
        'disk' => null,
        'rules' => ['required', 'file', 'max:25600'], // 25 Mo
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],
];
