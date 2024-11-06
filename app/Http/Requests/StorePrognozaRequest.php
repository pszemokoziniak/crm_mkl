<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrognozaRequest extends FormRequest
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
            'prognoza_dates_id' => 'required',
            'workers_count' =>'required | gt:0',
        ];
    }

    public function messages() {
        return [
            'required'  => 'Pole :attribute jest wymagane.',
            'gt:0' => 'Pole :attribute musi być wieksze od zera.',
        ];
    }

    public function attributes() {
        return [
            'prognoza_dates_id' => 'Wybierz daty',
            'workers_count' => 'Ilość pracowników'
        ];
    }
}
