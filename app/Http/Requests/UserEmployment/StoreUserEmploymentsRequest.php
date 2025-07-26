<?php

namespace App\Http\Requests\UserEmployment;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Validator;

class StoreUserEmploymentsRequest extends BaseRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            foreach ($data as $index => $employment) {
                $isCurrent = $employment['is_current'] ?? false;
                $endDate = $employment['end_date'] ?? null;
                $startDate = $employment['start_date'] ?? null;

                // If is_current is true, end_date must be null
                if ($isCurrent && $endDate !== null) {
                    $validator->errors()->add("{$index}.end_date", 'End date must be null if currently employed.');
                }

                // If is_current is false, end_date is required
                if (!$isCurrent && empty($endDate)) {
                    $validator->errors()->add("{$index}.end_date", 'End date is required if not currently employed.');
                }

                // start_date must not be after end_date (only if both exist)
                if (!$isCurrent && $startDate && $endDate && $startDate > $endDate) {
                    $validator->errors()->add("{$index}.end_date", 'End date must be after or equal to start date.');
                }
            }
        });
    }

    public function rules()
    {
        $maxEmploymentDate = now()->toDateString();

        return [
            '*.employer' => 'required|string|max:50',
            '*.role' => 'required|string|max:50',
            '*.employer_address' => 'required|string|max:255',
            '*.is_current' => 'required|boolean',
            '*.start_date' => [
                'required',
                'date',
                'before_or_equal:' . $maxEmploymentDate,
            ],
            '*.end_date' => [
                'nullable',
                'date',
                'before_or_equal:' . $maxEmploymentDate,
            ],
        ];
    }
}
