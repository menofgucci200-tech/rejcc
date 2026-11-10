<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class SiteLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        // Image de partage déjà au format 1200×630 (dimensions annoncées à Facebook/WhatsApp).
        public bool $imageLarge = false,
        public ?string $imageAlt = null,
        public string $type = 'website',
        // Données structurées schema.org propres à la page (Event, JobPosting…).
        public ?array $schema = null,
        public bool $noindex = false,
    ) {
    }

    public function render(): View
    {
        return view('layouts.site');
    }
}
