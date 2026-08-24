<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateRentalRequest extends FormRequest
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
     * Requirements: 7.1, 7.5
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'car_id'           => ['required', 'integer', 'exists:cars,id'],
            'start_date'       => ['required', 'date', 'after_or_equal:today'],
            'end_date'         => ['required', 'date', 'after:start_date'],
            'pickup_location'  => ['required', 'string', 'max:500'],
            'return_location'  => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'car_id.required'          => 'Kendaraan wajib dipilih.',
            'car_id.exists'            => 'Kendaraan yang dipilih tidak ditemukan.',
            'start_date.required'      => 'Tanggal mulai wajib diisi.',
            'start_date.date'          => 'Tanggal mulai harus berupa tanggal yang valid.',
            'start_date.after_or_equal'=> 'Tanggal mulai harus hari ini atau setelahnya.',
            'end_date.required'        => 'Tanggal selesai wajib diisi.',
            'end_date.date'            => 'Tanggal selesai harus berupa tanggal yang valid.',
            'end_date.after'           => 'Tanggal selesai harus setelah tanggal mulai.',
            'pickup_location.required' => 'Lokasi pengambilan wajib diisi.',
            'return_location.required' => 'Lokasi pengembalian wajib diisi.',
        ];
    }
}
