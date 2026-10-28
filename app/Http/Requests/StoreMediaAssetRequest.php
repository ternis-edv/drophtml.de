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
            'file' => ['nullable', 'file', 'max:102400'], // Max 100MB
            'data' => ['nullable', 'string'],
            'content_base64' => ['nullable', 'string'],
            'original_name' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'site_id' => ['nullable', 'string', 'exists:sites,id'],
            'storage_driver' => ['nullable', 'string', 'in:asset_storage,code'],
            'custom_subdir' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->hasFile('file') && ! $this->filled('data') && ! $this->filled('content_base64')) {
                $validator->errors()->add('file', 'A file upload or base64 data string is required.');
            }
        });
    }
}
