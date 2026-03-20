<?php

namespace App\Http\Requests\Template;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_templates');
    }

    public function rules(): array
    {
        return [
            'name'                       => ['required', 'string', 'max:150'],
            'key'                        => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'description'                => ['nullable', 'string'],
            'schema'                     => ['required', 'array'],
            'schema.fields'              => ['required', 'array', 'min:1'],
            'schema.fields.*.name'       => ['required', 'string'],
            'schema.fields.*.label'      => ['required', 'string'],
            'schema.fields.*.type'       => ['required', 'string', 'in:text,number,boolean,select,textarea,date,scale'],
            'schema.fields.*.required'   => ['sometimes', 'boolean'],
            'schema.fields.*.options'    => ['sometimes', 'array'],
            'schema.fields.*.options.*'  => ['string'],
            'schema.fields.*.validation' => ['sometimes', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.regex' => 'The key must contain only lowercase letters, numbers, and underscores.',
        ];
    }
}
