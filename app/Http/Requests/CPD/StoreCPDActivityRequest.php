<?php

namespace App\Http\Requests\CPD;

use Illuminate\Foundation\Http\FormRequest;

class StoreCPDActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:50',
            'description' => 'required|string|max:255',
            'type' => 'required|string|in:seminar,course,workshop,conference,webinar,other',
            'credit_hours' => 'required|decimal:1.0,999.9',
            'hosting_body' => 'nullable|string',
            'certificate' => 'nullable|file|max:2048'
        ];
    }
}
