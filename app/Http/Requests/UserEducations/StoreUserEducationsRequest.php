<?php

namespace App\Http\Requests\UserEducations;

use App\Http\Requests\BaseRequest;

class StoreUserEducationsRequest extends BaseRequest {
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
        $maxGraduationYear = date('Y') + 5;

        return [
            '*.institution' => 'required|string|max:50',
            '*.course_of_study' => 'required|string|max:50',
            '*.qualification' => 'required|string|max:50',
            '*.year_of_graduation' => [
                'required',
                'integer',
                'min:1900',
                'max:' . $maxGraduationYear,
            ],
        ];
    }
}
