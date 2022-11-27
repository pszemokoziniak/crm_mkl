<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FindPracownicyRequest extends FormRequest
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
            'start' => 'required|date|before_or_equal:end',
            'end' => 'required|date',
        ];
    }
    public function messages() {
        return [
            'required'  => ':attribute jest wymagane.',
            'before_or_equal' => 'Pole :attribute musi być mniejsze niż Koniec pracy na budowie.'
        ];
    }
    public function attributes()
    {
        return [
            'start' => 'Początek pracy na budowie',
            'end' => 'Koniec pracy na budowie',
        ];
    }
}
