<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit_clients');
    }

    public function rules(): array
    {
        return [
            'first_name'                    => ['sometimes', 'string', 'max:100'],
            'last_name'                     => ['sometimes', 'string', 'max:100'],
            'email'                         => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone'                         => ['sometimes', 'nullable', 'string', 'max:30'],
            'date_of_birth'                 => ['sometimes', 'nullable', 'date', 'before:today'],
            'gender'                        => ['sometimes', 'nullable', Rule::in(['male','female','other','prefer_not_to_say'])],
            'address'                       => ['sometimes', 'nullable', 'string', 'max:500'],
            'city'                          => ['sometimes', 'nullable', 'string', 'max:100'],
            'state'                         => ['sometimes', 'nullable', 'string', 'max:100'],
            'country'                       => ['sometimes', 'nullable', 'string', 'max:100'],
            'postal_code'                   => ['sometimes', 'nullable', 'string', 'max:20'],
            'emergency_contact_name'        => ['sometimes', 'nullable', 'string', 'max:150'],
            'emergency_contact_phone'       => ['sometimes', 'nullable', 'string', 'max:30'],
            'emergency_contact_relationship'=> ['sometimes', 'nullable', 'string', 'max:100'],
            'status'                        => ['sometimes', Rule::in(['active','inactive','archived'])],
            'metadata'                      => ['sometimes', 'nullable', 'array'],
        ];
    }
}
