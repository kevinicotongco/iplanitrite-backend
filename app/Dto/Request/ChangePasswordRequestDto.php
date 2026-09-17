<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class ChangePasswordRequestDto extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|object>>
     */
    public function rules(): array
    {
        return [
            'currentPassword' => [
                'required',
                'min:8',
                function ($attribute, $value, $fail) {
                    $user = auth()->user();
                    if ($user && !Hash::check($value, $user->password)) {
                        $fail('The current password is incorrect.');
                    }
                },
            ],
            'newPassword' => [
                'required',
                'min:8',
                function ($attribute, $value, $fail) {
                    if ($value !== $this->input('newPasswordConfirmation')) {
                        $fail('The new password field confirmation does not match.');
                    }
                },
            ],
            'newPasswordConfirmation' => ['required'],
        ];
    }
}
