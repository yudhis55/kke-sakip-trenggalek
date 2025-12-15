<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Logout extends Component
{
    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return $this->redirect('/login');
    }
    public function render()
    {
        return <<<'HTML'
        <div>
            <a wire:click="logout" class="dropdown-item" style="cursor: pointer;">
                <i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i>
                <span class="align-middle" data-key="t-logout">Logout</span>
            </a>
        </div>
        HTML;
    }
}
