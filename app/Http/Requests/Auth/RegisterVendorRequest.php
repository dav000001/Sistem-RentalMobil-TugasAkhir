<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'min:3', 'max:100'],
            'email'               => ['required', 'email', 'max:120', 'unique:users,email'],
            'phone'               => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
            'password'            => ['required', 'confirmed', Password::defaults()],
            'business_name'       => ['required', 'string', 'min:3', 'max:120'],
            'business_type'       => ['required', Rule::in(['perorangan', 'cv', 'pt', 'komunitas'])],
            'city_id'             => ['required_without:city_name', 'nullable', 'exists:cities,id'],
            'city_name'           => ['required_without:city_id', 'nullable', 'string', 'min:2', 'max:100'],
            'fleet_size_estimate' => ['required', Rule::in(['1-2', '3-5', '6-10', '>10'])],
            'source'              => ['nullable', 'string', 'max:50'],
            'accept_terms'        => ['accepted'],
            'accept_privacy'      => ['accepted'],
            'newsletter'          => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'city.required_without'      => 'Pilih kota atau tulis nama kota Anda.',
            'city_name.required_without' => 'Tulis nama kota jika tidak ada di daftar.',
            'phone.regex'                => 'Format nomor WhatsApp tidak valid. Contoh: 08123456789 atau +628123456789',
            'accept_terms.accepted'      => 'Anda harus menyetujui Syarat & Ketentuan.',
            'accept_privacy.accepted'    => 'Anda harus menyetujui Kebijakan Privasi.',
            'fleet_size_estimate.required' => 'Pilih estimasi jumlah armada.',
            'business_type.required'     => 'Pilih tipe bisnis.',
        ];
    }
}
