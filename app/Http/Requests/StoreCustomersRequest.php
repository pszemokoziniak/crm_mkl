<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Request;

class StoreCustomersRequest extends FormRequest
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
            'first_name' => ['required', 'max:150'],
            'last_name' => ['required', 'max:150'],
            'birth_date' => ['required'],
            'pesel' => 'required | numeric | unique:contacts',
            'idCard_number' => ['nullable'],
            'idCard_date' => ['nullable'],
            'funkcja_id' => ['required'],
            'work_start' => ['required'],
            'work_end' => ['required'],
            'ekuz' => ['nullable'],
            'miejsce_urodzenia' => ['nullable'],
            'organization_id' => ['nullable'],
            'email' => 'required | max:150| email | unique:contacts',
            'phone' => ['required', 'max:50', 'string'],
            'address' => ['nullable'],
        ];
    }

    public function messages() {
        return [
            'required'  => 'Pole :attribute jest wymagane.',
            'unique' => 'Nazwa użyta',
            'numeric' => 'Pole :attribute może zawierać tylko cyfry',
        ];
    }

    public function attributes()
    {
        return [
            'first_name' => 'Imię',
            'last_name' => 'Nazwisko',
            'birth_date' => 'Data Urodzenia',
            'pesel' => 'PESEL',
            'idCard_number' =>  'Nr Dowodu',
            'idCard_date' => 'Data Dowodu',
            'work_start' => 'Początek Umowy',
            'work_end' => 'Koniec Umowy',
            'ekuz' => 'EKUZ',
            'miejsce_urodzenia' => 'Miejsce Urodznia',
            'email' => 'Email',
            'phone' => 'Telefon',
            'address' => 'Adres',
            'funkcja_id' => 'Stanowisko',
        ];
    }
}
