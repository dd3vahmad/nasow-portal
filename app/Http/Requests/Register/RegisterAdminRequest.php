<?php

namespace App\Http\Requests\Register;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class RegisterAdminRequest extends BaseRequest {
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
            'email' => 'required|email',
            'password' => 'required|string|min:8|max:50',
            'gender' => ['required', 'string', Rule::in(['MALE', 'FEMALE'])],
            'dob' => 'required|date_format:Y-m-d',
            'address' => 'required|string|max:225',
            'avatar' => 'nullable|file|max:2048',
            'phone' => 'required|string|max:50',
            'as' => 'required|string|in:national,state,support,case',
            'state' => ['required', 'string', Rule::in(config('states'))],
        ];
    }
}
