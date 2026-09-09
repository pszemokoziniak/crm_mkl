<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreA1Request extends FormRequest
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
        $rules = [
            // Równe daty są dopuszczalne: wpis jednodniowy.
            'start' => ['required', 'date', 'before_or_equal:end'],
            'end' => ['required', 'date', 'after_or_equal:start'],
            // 20 MB — tyle samo, co przy wgrywaniu w zakładce Dokumenty.
            'skan' => ['nullable', 'file', 'max:20480'],
        ];

        if ($this->isMethod('post')) {
            // Osobne domknięcie, nie kolejne after_or_equal: obie reguły
            // dzieliłyby klucz `end.after_or_equal`, więc przy odwróconych
            // datach użytkownik dostawałby komunikat o przeszłości.
            $rules['end'][] = function ($pole, $wartosc, $blad) {
                if ($wartosc && strtotime((string) $wartosc) < strtotime('today')) {
                    $blad('Data wygaśnięcia nie może być z przeszłości.');
                }
            };
        }

        return $rules;
    }

    public function messages() {
        return [
            'required'  => 'Pole :attribute jest wymagane.',
            'start.before_or_equal' => 'Pole :attribute nie może być późniejsze niż data końcowa.',
            'end.after_or_equal' => 'Pole :attribute nie może być wcześniejsze niż data początkowa.',
        ];
    }
    public function attributes()
    {
        return [
            'start' => 'Start',
            'end' => 'Koniec',
        ];
    }
}
