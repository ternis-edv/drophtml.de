<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaAssetRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:102400'], // Max 100MB
            'site_id' => ['nullable', 'string', 'exists:sites,id'],
            'storage_driver' => ['nullable', 'string', 'in:asset_storage,code'],
            'custom_subdir' => ['nullable', 'string', 'max:100'],
        ];
    }
}
