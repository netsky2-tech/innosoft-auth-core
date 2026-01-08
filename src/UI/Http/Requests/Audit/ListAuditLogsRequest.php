<?php

namespace InnoSoft\AuthCore\UI\Http\Requests\Audit;

use Illuminate\Foundation\Http\FormRequest;

class ListAuditLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['integer', 'min:1'],
            'per_page' => ['integer', 'min:1', 'max:100'],
            'user_id' => ['nullable', 'string'],
            'subject_id' => ['nullable', 'string'],
            'subject_type' => ['nullable', 'string'],
            'event' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
