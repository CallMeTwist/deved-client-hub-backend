<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_clients');
    }

    public function rules(): array
    {
        return [
            'first_name'                    => ['required', 'string', 'max:100'],
            'last_name'                     => ['required', 'string', 'max:100'],
            'email'                         => ['nullable', 'email', 'max:255'],
            'phone'                         => ['nullable', 'string', 'max:30'],
            'date_of_birth'                 => ['nullable', 'date', 'before:today'],
            'gender'                        => ['nullable', Rule::in(['male','female','other','prefer_not_to_say'])],
            'address'                       => ['nullable', 'string', 'max:500'],
            'city'                          => ['nullable', 'string', 'max:100'],
            'state'                         => ['nullable', 'string', 'max:100'],
            'country'                       => ['nullable', 'string', 'max:100'],
            'postal_code'                   => ['nullable', 'string', 'max:20'],
            'emergency_contact_name'        => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone'       => ['nullable', 'string', 'max:30'],
            'emergency_contact_relationship'=> ['nullable', 'string', 'max:100'],
            'metadata'                      => ['nullable', 'array'],
        ];
    }
}
