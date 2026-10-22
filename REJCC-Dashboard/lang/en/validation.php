<?php

/*
 * Surcharges des messages de validation (fusionnées clé par clé avec les
 * messages par défaut du framework). Le site est en français : on traduit
 * ici les messages des règles utilisées par les uploads, notamment
 * « uploaded », renvoyé par Livewire quand l'envoi du fichier temporaire
 * échoue (fichier trop lourd pour le serveur, connexion interrompue…).
 */

return [

    'uploaded' => 'Le fichier n\'a pas pu être envoyé : il est probablement trop volumineux ou votre connexion a été interrompue. Réessayez avec un fichier plus léger (voir la limite indiquée sous le champ).',

    'image' => 'Le fichier doit être une image (JPG, PNG, WebP…).',

    'mimes' => 'Formats de fichier acceptés : :values.',

    'max' => [
        'file' => 'Le fichier ne doit pas dépasser :max Ko.',
    ],

    'url' => 'Le lien doit être une adresse valide (https://…).',
];
