<?php

return [
    // Abonnements obligatoires par défaut (avant tout réglage depuis le tableau
    // de bord admin). Désactivé : tout le monde accède à tout.
    'subscriptions_enforced' => (bool) env('SUBSCRIPTIONS_ENFORCED', false),
];
