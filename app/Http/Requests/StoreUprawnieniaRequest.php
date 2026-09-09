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
            'start' => 'required | date | before_or_equal:end',
            'end' => 'required | date | after_or_equal:start',
            'uprawnieniaTyp_id' => 'required',
            // 20 MB — tyle samo, co przy wgrywaniu w zakładce Dokumenty.
            'skan' => ['nullable', 'file', 'max:20480'],
        ];
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
            'uprawnieniaTyp_id' => 'Nazwa',
        ];
    }
}
