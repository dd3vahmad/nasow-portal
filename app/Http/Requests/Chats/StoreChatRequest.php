<?php

namespace App\Http\Requests\Chats;

use App\Http\Requests\BaseRequest;

class StoreChatRequest extends BaseRequest
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
            'name' => 'nullable|string|max:255',
            'type' => 'required|in:private,group',
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:users,id',
        ];
    }
}
