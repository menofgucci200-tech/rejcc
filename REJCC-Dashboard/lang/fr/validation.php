<?php

/*
 * Messages de validation en français. Le site tourne avec APP_LOCALE=fr et
 * APP_FALLBACK_LOCALE=fr : sans ce fichier, Laravel affichait les clés brutes
 * (« validation.required ») au lieu d'un message lisible.
 */

return [

    'accepted' => 'Ce champ doit être accepté.',
    'array' => 'Ce champ doit être une liste.',
    'boolean' => 'Ce champ doit être vrai ou faux.',
    'confirmed' => 'La confirmation ne correspond pas.',
    'date' => 'Ce champ doit être une date valide.',
    'different' => 'Ce champ doit être différent de :other.',
    'digits' => 'Ce champ doit contenir :digits chiffres.',
    'email' => 'Saisissez une adresse e-mail valide.',
    'exists' => 'La valeur sélectionnée est invalide.',
    'file' => 'Ce champ doit être un fichier.',
    'image' => 'Le fichier doit être une image (JPG, PNG, WebP…).',
    'in' => 'La valeur sélectionnée est invalide.',
    'integer' => 'Ce champ doit être un nombre entier.',
    'mimes' => 'Formats de fichier acceptés : :values.',
    'numeric' => 'Ce champ doit être un nombre.',
    'regex' => 'Le format de ce champ est invalide.',
    'required' => 'Ce champ est obligatoire.',
    'required_if' => 'Ce champ est obligatoire.',
    'same' => 'Ce champ doit correspondre à :other.',
    'string' => 'Ce champ doit être un texte.',
    'unique' => 'Cette valeur est déjà utilisée.',
    'uploaded' => 'Le fichier n\'a pas pu être envoyé : il est probablement trop volumineux ou votre connexion a été interrompue. Réessayez avec un fichier plus léger (voir la limite indiquée sous le champ).',
    'url' => 'Le lien doit être une adresse valide (https://…).',

    'min' => [
        'array' => 'Choisissez au moins :min élément(s).',
        'file' => 'Le fichier doit faire au moins :min Ko.',
        'numeric' => 'La valeur doit être supérieure ou égale à :min.',
        'string' => 'Ce champ doit contenir au moins :min caractères.',
    ],
    'max' => [
        'array' => 'Choisissez au plus :max éléments.',
        'file' => 'Le fichier ne doit pas dépasser :max Ko.',
        'numeric' => 'La valeur doit être inférieure ou égale à :max.',
        'string' => 'Ce champ ne doit pas dépasser :max caractères.',
    ],
    'between' => [
        'numeric' => 'La valeur doit être comprise entre :min et :max.',
        'string' => 'Ce champ doit contenir entre :min et :max caractères.',
    ],
    'size' => [
        'string' => 'Ce champ doit contenir :size caractères.',
    ],

    'attributes' => [
        'whatsapp' => 'téléphone',
        'telephone' => 'téléphone',
    ],
];
