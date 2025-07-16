<?php

namespace App\Http\Requests\UserEmployment;

use App\Http\Requests\BaseRequest;

class StoreUserEmploymentsRequest extends BaseRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $maxEmploymentYear = now()->year;

        return [
            '' => 'required|array|min:1',
            '*.employer' => 'required|string|max:50',
            '*.role' => 'required|string|max:50',
            '*.employer_address' => 'required|string|max:255',
            '*.is_current' => 'required|boolean',
            '*.year' => [
                'required',
                'integer',
                'min:1900',
                'max:' . $maxEmploymentYear
            ]
        ];
    }
}
