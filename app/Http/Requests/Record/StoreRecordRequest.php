<?php

namespace App\Http\Requests\Record;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_records');
    }

    public function rules(): array
    {
        return [
            'template_key' => ['required', 'string'],
            'data'         => ['required', 'array'],
            'notes'        => ['nullable', 'string', 'max:5000'],
            'recorded_at'  => ['nullable', 'date'],
        ];
    }
}
