<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypGodzinyRequest extends FormRequest
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
            'title' => 'required | max:100',
            'code' => 'required | max:5',
        ];
    }
    public function messages() {
        return [
            'required'  => 'Pole :attribute jest wymagane.',
//            'max'  => 'Pole :attribute jest wymagane.',
        ];
    }
    public function attributes()
    {
        return [
            'title' => 'Nazwa',
//            'code' => 'Code'
            ];
    }
}
