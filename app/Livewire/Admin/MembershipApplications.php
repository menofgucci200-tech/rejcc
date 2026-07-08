<?php

namespace App\Livewire\Admin;

use App\Models\MembershipApplication;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class MembershipApplications extends Component
{
    public string $query = '';

    public ?int $viewing = null;

    public function view(int $id): void
    {
        $this->viewing = $id;
    }

    public function closeView(): void
    {
        $this->viewing = null;
    }

    public function toggleTraite(int $id): void
    {
        $application = MembershipApplication::findOrFail($id);
        $application->update(['traite' => ! $application->traite]);
    }

    public function render()
    {
        $q = trim($this->query);

        $applications = MembershipApplication::orderByDesc('created_at')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nom_prenoms', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->get();

        return view('livewire.admin.membership-applications', [
            'applications' => $applications,
            'viewingApplication' => $this->viewing ? MembershipApplication::find($this->viewing) : null,
        ]);
    }
}
