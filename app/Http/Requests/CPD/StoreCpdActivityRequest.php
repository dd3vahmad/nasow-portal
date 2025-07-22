<?php

namespace App\Http\Requests\CPD;

use App\Enums\CpdActivityType;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCPDActivityRequest extends BaseRequest
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
            'type' => ['required', new Enum(CpdActivityType::class)],
            'credit_hours' => ['required', 'numeric', 'between:1,999.9', 'regex:/^\d{1,3}(\.\d)?$/'],
            'hosting_body' => 'nullable|string',
            'certificate' => 'nullable|file|max:2048',
        ];
    }
}
