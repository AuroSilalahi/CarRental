<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
     * All fields are optional (partial update), but if provided they must pass validation.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => ['sometimes', 'string', 'max:255'],
            'phone'    => ['sometimes', 'string', 'regex:/^(\+62|08)[0-9]{7,12}$/'],
            'address'  => ['sometimes', 'string'],
            'city'     => ['sometimes', 'string', 'max:100'],
            'province' => ['sometimes', 'string', 'max:100'],
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
            'phone.regex' => 'Format nomor telepon tidak valid. Gunakan format Indonesia (contoh: +6281234567890 atau 081234567890).',
        ];
    }
}
