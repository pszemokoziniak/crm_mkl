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
            'start' => 'required | date | before:end',
            'end' => 'required | date | after:start',
        ];

        if ($this->isMethod('post')) {
            $rules['end'] .= ' | after_or_equal:today';
        }

        return $rules;
    }

    public function messages() {
        return [
            'required'  => 'Pole :attribute jest wymagane.',
            'start.before' => 'Pole :attribute musi być mniejsze niż pole Koniec',
            'end.after' => 'Pole :attribute musi być większe niż pola Początek',
            'end.after_or_equal' => 'Data wygaśnięcia nie może być z przeszłości.',
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
