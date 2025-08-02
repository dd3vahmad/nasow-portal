<?php

namespace App\Http\Requests\Chats;

use App\Http\Requests\BaseRequest;


class StoreMessageRequest extends BaseRequest
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
            'content' => 'required_without:attachments|string',
            'type' => 'in:text,file,image',
            'reply_to' => 'nullable|exists:messages,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ];
    }
}
