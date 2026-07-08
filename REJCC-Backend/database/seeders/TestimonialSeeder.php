<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            ['name' => 'Jean-Marc Koffi', 'role' => 'Fondateur, AgriTech', 'initials' => 'JK', 'quote' => "Le REJCC m'a ouvert un réseau que je n'aurais jamais atteint seul. En six mois, j'ai trouvé deux partenaires et un mentor."],
            ['name' => 'Aïcha Brou', 'role' => 'Dirigeante, Studio créatif', 'initials' => 'AB', 'quote' => "Entreprendre avec des valeurs partagées change tout. Ici, la foi et l'excellence avancent ensemble."],
            ['name' => 'Grâce Adjoua', 'role' => 'Porteuse de projet, E-commerce', 'initials' => 'GA', 'quote' => "Les formations et le mentorat m'ont aidée à structurer mon projet et à le rendre rentable."],
        ];

        foreach ($testimonials as $i => $t) {
            Testimonial::updateOrCreate(['name' => $t['name']], $t + ['ordre' => $i]);
        }
    }
}
