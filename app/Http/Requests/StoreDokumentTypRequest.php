<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDokumentTypRequest extends FormRequest
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
            'name' =>'required|min:2|unique:dokumenty_typs,name',
        ];
    }

    public function messages() {
        return [
            'required'  => 'Pole :attribute jest wymagane.',
            'min'  => 'Wymagane 2 znaki.',
            'unique' => 'Nazwa użyta',
        ];
    }

    public function attributes() {
        return [
            'name' => 'Nazwa'
        ];
    }
}
