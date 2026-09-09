<?php

namespace App\Http\Requests;

use App\Models\Funkcja;
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
    /**
     * Kierownik projektu to opiekun kontraktu, nie pracownik budowy: nie ma
     * dat zatrudnienia ani badań, a PESEL-u i telefonu do tej roli nie
     * potrzebujemy — więc ich nie zbieramy. Wystarczą imię, nazwisko
     * i stanowisko.
     */
    private function tylkoOpiekunKontraktu(): bool
    {
        $funkcjaId = Funkcja::kierownikProjektuId();

        return $funkcjaId !== null && (int) $this->input('funkcja_id') === $funkcjaId;
    }

    public function rules()
    {
        $opiekun = $this->tylkoOpiekunKontraktu();
        $wymagane = $opiekun ? 'nullable' : 'required';

        return [
            'first_name' => ['required', 'max:150'],
            'last_name' => ['required', 'max:150'],
            'birth_date' => [$wymagane],
            'pesel' => array_filter([$wymagane, $opiekun ? null : 'numeric', 'unique:contacts', $opiekun ? null : 'digits:11']),
            'idCard_number' => ['nullable'],
            'idCard_date' => ['nullable'],
            'funkcja_id' => ['required'],
            'work_start' => $wymagane.' | date | before:work_end',
            'work_end' => $wymagane.' | date | after:work_start',
            'ekuz' => ['nullable'],
            'miejsce_urodzenia' => ['nullable'],
            'organization_id' => ['nullable'],
            'email' => 'nullable | max:150| email | unique:contacts',
            'phone' => [$wymagane, 'max:50', 'string'],
            'address' => ['nullable'],
            'photo_path' => ['nullable', 'image'],
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
