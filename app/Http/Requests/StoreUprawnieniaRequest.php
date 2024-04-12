<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUprawnieniaRequest extends FormRequest
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
            'start' => 'required | date | before:end',
            'end' => 'required | date | after:start',
            'uprawnieniaTyp_id' => 'required',
        ];
    }
    public function messages() {
        return [
            'required'  => 'Pole :attribute jest wymagane.',
            'start.before' => 'Pole :attribute musi być mniejsze niż pole Koniec',
            'end.after' => 'Pole :attribute musi być większe niż pola Początek',
        ];
    }
    public function attributes()
    {
        return [
            'start' => 'Start',
            'end' => 'Koniec',
            'uprawnieniaTyp_id' => 'Nazwa',
        ];
    }
}
