<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSiteRequest extends FormRequest
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
            'original_name' => ['required', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:51200'], // Max 50MB
            'html' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'files' => ['nullable', 'array'],
            'custom_slug' => ['nullable', 'string', 'max:64', 'alpha_dash'],
            'github_repo' => ['nullable', 'string', 'max:255'],
            'github_repo_full_name' => ['nullable', 'string', 'max:255'],
            'github_branch' => ['nullable', 'string', 'max:255'],
            'auto_deploy' => ['boolean'],
            'is_permanent' => ['boolean'],
            'is_password_protected' => ['boolean'],
            'site_password' => ['nullable', 'string', 'min:4'],
        ];
    }
}
