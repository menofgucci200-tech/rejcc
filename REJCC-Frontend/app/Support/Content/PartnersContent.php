<?php

namespace App\Support\Content;

class PartnersContent
{
    public static function partnershipTypes(): array
    {
        return ['Partenaire financier', 'Partenaire technique', 'Partenaire institutionnel', 'Partenaire média', 'Mécénat / Don', 'Autre'];
    }

    public static function partnershipBenefits(): array
    {
        return [
            ['title' => 'Visibilité', 'text' => "Associez votre marque à un réseau dynamique de jeunes entrepreneurs catholiques."],
            ['title' => 'Impact', 'text' => "Soutenez concrètement la création de richesse et d'emplois en Côte d'Ivoire."],
            ['title' => 'Accès aux talents', 'text' => 'Rencontrez des porteurs de projets et des entrepreneurs prometteurs.'],
            ['title' => 'Sens & valeurs', 'text' => 'Inscrivez votre engagement dans une démarche éthique et solidaire.'],
        ];
    }
}
