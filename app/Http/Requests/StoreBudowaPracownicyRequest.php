<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudowaPracownicyRequest extends FormRequest
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
            'checkedValues' => ['required'],
            'start' => 'required|date|before_or_equal:end',
            'end' => ['required', 'date'],

        ];
    }
    public function messages() {
        return [
            'required'  => 'Pole :attribute jest wymagane.',
            'before_or_equal' => 'Pole :attribute musi być mniejsze niż koniec.'
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
