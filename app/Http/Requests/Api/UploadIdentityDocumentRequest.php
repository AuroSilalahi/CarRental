<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UploadIdentityDocumentRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimes:jpeg,png,pdf', 'max:5120'], // 5MB = 5*1024 KB
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
            'file.required' => 'File dokumen identitas wajib diunggah.',
            'file.file'     => 'File yang diunggah tidak valid.',
            'file.mimes'    => 'File dokumen identitas harus dalam format JPEG, PNG, atau PDF.',
            'file.max'      => 'Ukuran file dokumen identitas tidak boleh melebihi 5 MB.',
        ];
    }
}
