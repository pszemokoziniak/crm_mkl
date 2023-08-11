<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasswordExpiredRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'password' => [
                'required',
                'min:8',
                'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/',
                ],
        ];
    }
    public function messages() {
        return [
            'password.required'  => 'Hasło jest wymagane.',
            'password.regex' => 'Hasło musi zawierać dużą literę, znak specjalny, cyfrę',
            'password.min' => 'Hasło musi zawierać 8 znaków',
        ];
    }
}
