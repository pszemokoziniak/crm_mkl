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
            'pesel' => ['required', 'numeric', 'unique:contacts', 'digits:11'],
            'idCard_number' => ['nullable'],
            'idCard_date' => ['nullable'],
            'funkcja_id' => ['required'],
            'work_start' => 'required | date | before:work_end',
            'work_end' => 'required | date | after:work_start',
            'ekuz' => ['nullable'],
            'miejsce_urodzenia' => ['nullable'],
            'organization_id' => ['nullable'],
            'email' => 'nullable | max:150| email | unique:contacts',
            'phone' => ['required', 'max:50', 'string'],
            'address' => ['nullable'],
            'status_zatrudnienia' => ['nullable', 'in:Aktywny,Zwolniony'],
        ];
    }

    public function messages() {
        return [
            'required'  => 'Pole :attribute jest wymagane.',
            'unique' => 'Nazwa użyta',
            'numeric' => 'Pole :attribute może zawierać tylko cyfry',
            'digits' => 'PESEL musi mieć 11 cyfr',
            'date' => 'Pole musi zawierać datę',
            'work_start.before' => 'Pole :attribute musi być mniejsze niż pole Koniec umowy',
            'work_end.after' => 'Pole :attribute musi być większe niż pole Początek umowy',
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
            'status_zatrudnienia' => 'Status zatrudnienia',
        ];
    }
}
