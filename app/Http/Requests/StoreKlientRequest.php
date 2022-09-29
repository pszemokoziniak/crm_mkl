<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKlientRequest extends FormRequest
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
            'organization_id' => 'required',
            'nameFirma' =>'required',
            'adres' =>'required',
            'city' =>'required',
            'country_id' =>'required',
            'nameKontakt' =>'required',
            'phone' =>'required',
            'email' =>'required',
            'funkcja_id' => 'required',
        ];
    }

    public function messages() {
        return [
            'required'  => 'Pole :attribute jest wymagane.',
            'unique' => 'Nazwa użyta',
        ];
    }
}
