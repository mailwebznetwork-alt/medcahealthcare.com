<?php

namespace App\Livewire\Modules;

use App\Models\Vacancy;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class JobPortal extends Component
{
    public function render(): View
    {
        $vacancies = Vacancy::query()
            ->careersListing()
            ->limit(20)
            ->get();

        return view('livewire.modules.job-portal', compact('vacancies'));
    }
}
