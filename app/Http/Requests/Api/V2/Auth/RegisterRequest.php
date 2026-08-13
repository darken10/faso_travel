<?php

namespace App\Http\Requests\Api\V2\Auth;

use App\Enums\OtpChannelType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
            'name'                  => 'required|string|max:255',
            'email'                 => 'nullable|required_without:numero|string|email|max:255|unique:users',
            'password'              => 'required|string|min:8|confirmed',
            'first_name'            => 'nullable|string|max:255',
            'last_name'             => 'nullable|string|max:255',
            'sexe'                  => 'nullable|string',
            'numero'                => 'nullable|required_without:email|integer',
            'numero_identifiant'    => 'nullable|required_with:numero|string|max:10',
            'role'                  => 'nullable|string',
            'compagnie_id'          => 'nullable|exists:compagnies,id',
            'verification_method'   => ['nullable', Rule::in(OtpChannelType::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required_without'  => 'L\'adresse email est requise si aucun numéro n\'est fourni.',
            'email.email'             => 'L\'adresse email est invalide.',
            'email.unique'            => 'Cette adresse email est déjà utilisée.',
            'numero.required_without' => 'Le numéro de téléphone est requis si aucun email n\'est fourni.',
            'password.required'       => 'Le mot de passe est requis.',
            'password.min'            => 'Le mot de passe doit comporter au moins 8 caractères.',
            'password.confirmed'      => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }
}
