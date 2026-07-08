<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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

    public function accept(int $id): void
    {
        Api::post("/admin/membership-applications/{$id}/accept", [], Api::token());
    }

    public function reject(int $id): void
    {
        Api::post("/admin/membership-applications/{$id}/reject", [], Api::token());
    }

    public function render()
    {
        $q = trim($this->query);
        $token = Api::token();

        $applications = Collection::make(Api::get('/admin/membership-applications', ['q' => $q], $token)['applications'] ?? [])
            ->map(function ($a) {
                $a['created_at'] = Carbon::parse($a['created_at']);

                return (object) $a;
            });

        $viewingApplication = null;
        if ($this->viewing) {
            $result = Api::get("/admin/membership-applications/{$this->viewing}", [], $token);
            $viewingApplication = ($result['ok'] ?? false) ? (object) $result['application'] : null;
        }

        return view('livewire.admin.membership-applications', [
            'applications' => $applications,
            'viewingApplication' => $viewingApplication,
        ]);
    }
}
