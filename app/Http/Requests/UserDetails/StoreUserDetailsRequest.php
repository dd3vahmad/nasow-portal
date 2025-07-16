<?php

namespace App\Http\Requests\UserDetails;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreUserDetailsRequest extends BaseRequest {
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
        return [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'other_name' => 'nullable|string|max:50',
            'specialization' => 'nullable|string|max:50',
            'gender' => ['required', 'string', Rule::in(['MALE', 'FEMALE'])],
            'dob' => 'required|date_format:Y-m-d',
            'address' => 'required|string|max:225',
            'phone' => 'required|string|max:50',
            'state' => ['required', 'string', Rule::in(config('states'))],
            'category' => ['required', 'string', Rule::in(config('member_categories'))],
        ];
    }

}
