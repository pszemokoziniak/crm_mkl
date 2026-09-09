<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePosRequest extends FormRequest
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
            'name' =>'required|min:3|unique:funkcjas,name',
            'kierownictwo' => 'boolean',
            // Kolumna listy budów, do której trafiają osoby z tym stanowiskiem.
            'rola_budowy' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(\App\Models\Funkcja::ROLE_BUDOWY))],
        ];
    }

    public function messages() {
        return [
            'required'  => 'Pole :attribute jest wymagane.',
            'min'  => 'Wymagane 3 znaki.',
            'unique' => 'Nazwa użyta',
        ];
    }

    public function attributes() {
        return [
            'name' => 'Nazwa'
        ];
    }
}
