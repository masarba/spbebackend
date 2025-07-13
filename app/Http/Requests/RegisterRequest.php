<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Pastikan user diizinkan untuk membuat request ini
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    
{
    return [
        'username' => 'required|string|max:150',
        'email' => 'required|email|max:150|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
    ];
}

public function getData(): array
{
    $data = $this->validated();
    $data['password'] = Hash::make($data['password']);
    $data['role'] = 'auditee'; // Set default role to auditee
    return $data;
}

}
