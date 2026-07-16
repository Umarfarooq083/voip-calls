<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExtensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:extensions,name'],
            'extension' => ['required', 'string', 'max:20', 'unique:extensions,extension'],
            'secret' => ['required', 'string', 'min:4', 'max:128'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'context' => ['nullable', 'string', 'max:128'],
            'transport' => ['nullable', 'string', 'max:128'],
            'caller_id_name' => ['nullable', 'string', 'max:80'],
            'caller_id_num' => ['nullable', 'string', 'max:80'],
            'mailbox' => ['nullable', 'string', 'max:20'],
            'vm_context' => ['nullable', 'string', 'max:80'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:300'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'The extension name already exists.',
            'extension.unique' => 'The extension number already exists.',
            'secret.required' => 'A secret/password is required for SIP authentication.',
            'secret.min' => 'The secret must be at least 4 characters.',
        ];
    }
}
