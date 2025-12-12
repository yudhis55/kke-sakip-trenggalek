<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.auth')]
#[Title('Login - KKE SAKIP Trenggalek')]
class Login extends Component
{
    public $email, $password;

    public function login()
    {
        $this->validate();
        $credentials = [
            'email' => $this->email,
            'password' => $this->password,
        ];
        if (Auth::attempt($credentials)) {
            session()->regenerate();
            return $this->redirectIntended('/dashboard');
        }
        $this->addError('email', 'Email atau password salah.');
    }

    protected function rules()
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
        ];
    }
}
