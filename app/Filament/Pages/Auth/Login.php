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
        $user = User::where('email', $this->form->getState()['email'])->first();

        if ($user) {
            $message = $user->getSystemAccessDenialMessage();

            if ($message) {
                throw ValidationException::withMessages([
                    'data.email' => $message,
                ]);
            }
        }

        parent::throwFailureValidationException();
    }
}
