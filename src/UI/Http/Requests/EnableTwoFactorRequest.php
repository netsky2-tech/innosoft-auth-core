<?php

namespace InnoSoft\AuthCore\UI\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnableTwoFactorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [

        ];
    }
}