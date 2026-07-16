<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $campaignId = $this->route('campaign');

        return [
            'name' => ['required', 'string', 'max:255'],
            'extension_id' => ['required', 'exists:extensions,id'],
            'voice_message_id' => ['nullable', 'exists:voice_messages,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Campaign name is required.',
            'extension_id.required' => 'Extension is required.',
        ];
    }
}