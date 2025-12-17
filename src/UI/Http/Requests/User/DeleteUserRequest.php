<?php

namespace InnoSoft\AuthCore\UI\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

final class DeleteUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:users,id'],
        ];
    }
}