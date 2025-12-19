<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDomainRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => [
                'sometimes',
                'string',
                'max:255',
                'unique:domains,url,' . $this->route('domain'),
                'regex:/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/',
            ],
            'is_active' => ['sometimes', 'boolean'],
            'ssl_status' => ['sometimes', 'string', 'in:pending,issued,failed,expired'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.unique' => 'Este dominio ya está en uso.',
            'url.regex' => 'El formato del dominio no es válido.',
            'ssl_status.in' => 'El estado SSL debe ser: pending, issued, failed o expired.',
        ];
    }
}
