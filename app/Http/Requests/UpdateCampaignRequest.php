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
            'ivr_id' => ['required'],
            'ivr_name' => ['required'],
            'trunk_channalId' => ['required'],
            'no_of_calls' => ['required', 'integer', 'min:1'],
            'csv_file' => ['nullable', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Campaign name is required.',
            'ivr_id.required' => 'IVR is required.',
        ];
    }
}