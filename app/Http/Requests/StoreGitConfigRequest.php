<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGitConfigRequest extends FormRequest
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
            'repository_url' => ['required', 'url', 'max:255'],
            'branch' => ['required', 'string', 'max:100'],
            'base_directory' => ['nullable', 'string', 'starts_with:/', 'max:255'],
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
            'repository_url.required' => 'La URL del repositorio es obligatoria.',
            'repository_url.url' => 'La URL del repositorio debe ser válida.',
            'branch.required' => 'La rama es obligatoria.',
            'base_directory.starts_with' => 'El directorio base debe comenzar con "/".',
        ];
    }
}
