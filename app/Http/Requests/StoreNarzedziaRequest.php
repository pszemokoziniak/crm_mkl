<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNarzedziaRequest extends FormRequest
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
            'name' =>'required|min:5|unique:narzedzias,name',
            'numer_seryjny' =>'nullable',
            'waznosc_badan' =>'nullable|date',
            'ilosc_all' =>'nullable|numeric',
            'photos.*' => 'nullable|image|max:5120',
            'documents.*' => 'nullable|file|max:10240',
        ];
    }

    public function messages() {
        return [
            'required'  => 'Pole :attribute jest wymagane.',
            'min'  => 'Wymagane 5 znaki.',
            'unique' => 'Nazwa użyta',
            'numeric' => 'Pole musi zawierać cyfrę',
            'image' => 'Plik musi być obrazem.',
            'max' => 'Plik jest zbyt duży.',
        ];
    }

    public function attributes() {
        return [
            'name' => 'Nazwa',
            'ilosc' => 'Ilość',
            'photos' => 'Zdjęcia',
            'documents' => 'Dokumenty'
        ];
    }
}
