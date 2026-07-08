<?php

namespace App\Support\Content;

class AdhesionApplicationOptions
{
    public static function sexes(): array
    {
        return ['Homme', 'Femme'];
    }

    public static function tranchesAge(): array
    {
        return [
            'Moins de 18 ans', '18 à 24 ans', '25 à 30 ans', '31 à 35 ans',
            '36 à 40 ans', '40 à 50 ans', 'Plus de 50 ans',
        ];
    }

    public static function connotationsReligieuses(): array
    {
        return ['Catholique', 'Évangélique', 'Musulman', 'Autre'];
    }

    public static function paroisses(): array
    {
        return [
            'Ste Elisabeth (Ananeraie)', 'St Jean Baptiste Manmie Adjoua', 'St Mathieu (Cité Verte)',
            'Ste Rita de Cascia (Niangon Nord)', 'St Pierre (Niangon Sud)', 'St Thomas (Quartier Résidentiel)',
            "Ste Jeanne d'Arc (Cité Caistab)", 'Ste Catherine de Sienne (Académie)',
            'Notre Dame des Douleurs (Lokoua)', 'St Sauveur Miséricordieux (Millionnaire)',
            "Notre Dame de l'Annonciation (Nouveau Quartier)", 'St Jean Paul II (Agbayaté)',
            'St Vincent de Paul (Abobodoumé)', 'St André (Sicogi)', 'St Timothée (Cité EECI)',
            'Saint Joseph (Siporex)', 'St Mathias Kalemba Mulumba (Assanvon)', 'St Laurent (Kouté)',
            'Saint Pierre et Paul (Beago)', 'Sainte Trinité (Beago)', "Saint François d'Assise (Micao)",
            'Saint Barthélemy (Cité du Banco)', "Notre Dame de l'Assomption (Gesco)", 'Saint Andréas (Andokoi)',
            "Sainte Thérèse d'Avila (Cité Maca)", 'St Jacques (Allokoi)', 'St Jean (Azito)',
            'Ste Marie Madeleine (Camp Militaire)', 'Stes Perpétue et Félicité (Chapoulie)',
            'St Marc (Toit Rouge)', 'St Michel Archange (Cité BAE)', 'St Jean-Marie Vianney (Koweït)',
            'St Alphonse (Elibou)', 'St Raphael (Sikensi A)', 'St Gabriel (Sikensi)',
            'St Anne et Joachim (Gomon)', 'Saint Augustin (Quartier Hévéa)', 'St Clément (Abadjin Kouté)',
            "Ste Famille d'Audouin (Odouin)", 'St Pierre (Yassap)', 'Ste Philomène (IRCA IRFA)',
            'St Joseph (Niangon Attié)', 'St Paul (Pépinière)', 'Vieux Badiem',
            "Ste Maria Goretti (N'djem)", 'Institut Pasteur', 'Autre',
        ];
    }

    public static function statutsActuels(): array
    {
        return [
            'Élève', 'Étudiant', 'Salarié du privé', 'Fonctionnaire', 'Entrepreneur',
            'Travailleur indépendant', 'Sans emploi', 'Autre',
        ];
    }

    public static function niveauxEtudes(): array
    {
        return ['Primaire', 'Collège', 'Lycée', 'BTS', 'Licence', 'Master', 'Doctorat', 'Autre'];
    }

    public static function competences(): array
    {
        return [
            'Informatique', 'Développement web', 'Graphisme', 'Communication', 'Marketing',
            'Comptabilité', 'Finance', 'Agriculture', 'Élevage', 'Commerce', 'Transport',
            'BTP / Construction', 'Artisanat', 'Couture', 'Événementiel', 'Enseignement',
            'Juridique', 'Santé', 'Gestion de projet', 'Autre',
        ];
    }

    public static function secteursActivite(): array
    {
        return [
            'Agriculture', 'Élevage', 'Pêche et aquaculture', 'Agroalimentaire / Transformation alimentaire',
            'Commerce et distribution', 'Import - Export', 'Restauration', 'Hôtellerie et tourisme',
            'Transport et logistique', 'Informatique et numérique', 'Développement web et mobile',
            'Cybersécurité et réseaux', 'Communication et marketing', 'Médias et audiovisuel',
            'Graphisme et design', 'Imprimerie et sérigraphie', 'Finance et comptabilité',
            'Banque et assurance', 'Ressources humaines', 'Juridique et conseil', 'Éducation et formation',
            'Santé et pharmacie', 'BTP et construction', 'Architecture et urbanisme', 'Immobilier',
            'Électricité', 'Plomberie', 'Menuiserie', 'Mécanique automobile', 'Maintenance industrielle',
            'Artisanat', 'Couture et mode', 'Beauté et esthétique', 'Services à la personne',
            'Nettoyage et pressing', 'Événementiel', 'Sécurité privée', 'Environnement et recyclage',
            'Énergies renouvelables', 'Administration publique', 'ONG et développement communautaire',
            'Commerce en ligne (E-commerce)', 'Autre',
        ];
    }

    public static function anciennetes(): array
    {
        return ["Moins d'un an", '1 à 2 ans', '3 à 5 ans', '6 à 10 ans', 'Plus de 10 ans'];
    }

    public static function attentes(): array
    {
        return [
            'Développer mon entreprise', 'Trouver des clients', 'Trouver des partenaires',
            'Trouver un emploi', 'Me former', 'Être accompagné dans un projet',
            'Accéder à des financements', 'Développer mon réseau professionnel',
            'Participer à des événements', "Découvrir des opportunités d'affaires", 'Autre',
        ];
    }

    public static function formationsInteret(): array
    {
        return [
            "Création d'entreprise", 'Élaboration de business plan', 'Marketing digital',
            'Vente et négociation', 'Gestion financière', 'Comptabilité', 'Leadership',
            'Recherche de financement', 'Développement personnel', 'Intelligence artificielle',
            'Agriculture', 'Élevage', 'Autre',
        ];
    }

    public static function defis(): array
    {
        return [
            'Manque de financement', 'Manque de clients', 'Manque de formation',
            'Chômage', 'Manque de réseau',
        ];
    }

    public static function revenus(): array
    {
        return [
            'Moins de 50 000 FCFA', 'Moins de 100 000 FCFA', '100 000 à 250 000 FCFA',
            '250 000 à 500 000 FCFA', 'De 500 000 FCFA à 1 000 000 FCFA', 'Plus de 1 000 000 FCFA',
            'Je préfère ne pas répondre',
        ];
    }
}
