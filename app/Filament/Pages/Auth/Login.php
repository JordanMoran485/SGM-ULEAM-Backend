<?php

namespace App\Filament\Pages\Auth;

use App\Models\User; 
use Filament\Auth\Pages\Login as BaseLogin; 
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'email' => $data['email'],
            'password' => $data['password'],
            'active_state' => true, 
        ];
    }

    protected function throwFailureValidationException(): never
    {
        $user = \App\Models\User::where('email', $this->form->getState()['email'])->first();

        if ($user && !$user->active_state) {
            throw ValidationException::withMessages([
                'data.email' => __('Tu cuenta de la Uleam está desactivada. Por favor, contacta a soporte técnico.'),
            ]);
        }

        parent::throwFailureValidationException();
    }
    }
    
