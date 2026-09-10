<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('site'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'in:active,suspended'],
            'is_permanent' => ['sometimes', 'boolean'],
            'auto_deploy' => ['sometimes', 'boolean'],
            'is_password_protected' => ['sometimes', 'boolean'],
            'site_password' => ['nullable', 'string', 'min:4'],
        ];
    }
}
