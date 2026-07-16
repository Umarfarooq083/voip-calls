<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'extension_id' => ['required', 'exists:extensions,id'],
            'voice_message_id' => ['nullable', 'exists:voice_messages,id'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Campaign name is required.',
            'extension_id.required' => 'Extension is required.',
            'csv_file.required' => 'CSV file is required.',
            'csv_file.mimes' => 'CSV file must be a CSV or TXT file.',
        ];
    }
}