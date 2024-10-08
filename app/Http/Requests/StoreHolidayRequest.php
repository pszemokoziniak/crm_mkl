<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHolidayRequest extends FormRequest
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
            'start' => 'required | date | before_or_equal:end',
            'end' => 'required | date | after_or_equal:start',
        ];
    }

    public function messages() {
        return [
            'required'  => 'Pole jest wymagane.',
            'start.before' => 'Pole :attribute musi być mniejsze niż pole Koniec',
            'end.after' => 'Pole :attribute musi być większe niż pola Początek',
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
