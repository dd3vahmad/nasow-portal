<?php

namespace App\Http\Requests\Ticket;

use App\Http\Requests\BaseRequest;
use App\Models\User;

class AssignTicketRequest extends BaseRequest {
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
            'ticket_id' => 'required|integer|exists:tickets,id',
            'support_id' => [
                'required',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::find($value);
                    if (!$user || !$user->hasRole('support-staff')) {
                        $fail('The selected support user must have the support-staff role.');
                    }
                },
            ],
        ];
    }
}
