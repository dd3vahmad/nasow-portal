<?php

namespace App\Http\Requests\CPD;

use App\Http\Requests\BaseRequest;

class LogCpdActivityRequest extends BaseRequest
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
            'credit_hours' => ['required', 'numeric', 'between:1,999.9', 'regex:/^\d{1,3}(\.\d)?$/'],
            'completed_at' => 'required|date|before_or_equal:today',
            'activity_id' => 'required|exists:cpd_activities,id',
            'certificate' => 'nullable|file|max:2048',
        ];
    }
}
