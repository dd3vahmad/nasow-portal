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
     * Validation notifiers
     * @param mixed $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $creditHours = $this->input('credit_hours');
            $activityId = $this->input('activity_id');

            if (!empty($creditHours) && !empty($activityId)) {
                $validator->errors()->add('credit_hours', 'You cannot provide both credit hours and an activity.');
                $validator->errors()->add('activity_id', 'You cannot provide both an activity and credit hours.');
            }

            if (empty($creditHours) && empty($activityId)) {
                $validator->errors()->add('credit_hours', 'Either credit hours or activity must be provided.');
                $validator->errors()->add('activity_id', 'Either credit hours or activity must be provided.');
            }
        });
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
            'credit_hours' => [
                'nullable',
                'numeric',
                'between:1,999.9',
                'regex:/^\d{1,3}(\.\d)?$/',
                'required_without:activity_id',
            ],
            'completed_at' => 'required|date|before_or_equal:today',
            'activity_id' => [
                'nullable',
                'exists:cpd_activities,id',
                'required_without:credit_hours',
            ],
            'certificate' => 'nullable|file|max:2048',
        ];
    }
}
