<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NomorAntreanRequest extends FormRequest
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
            'kd_dokter' => 'required|string',
            'tgl_antrean' => 'required|date',
        ];
    }
}
