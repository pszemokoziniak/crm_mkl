<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBhpRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // 20 MB — tyle samo, co przy wgrywaniu w zakładce Dokumenty.
            'skan' => ['nullable', 'file', 'max:20480'],
            'bhpTyp_id' => 'required',
            'start' => 'required|date|before_or_equal:end',
            'end' => 'required|date|after_or_equal:start',
        ];
    }

    public function messages()
    {
        return [
            'required' => 'Pole :attribute jest wymagane.',
            'start.before_or_equal' => 'Pole :attribute nie może być późniejsze niż data końcowa.',
            'end.after_or_equal' => 'Pole :attribute nie może być wcześniejsze niż data początkowa.',
        ];
    }

    public function attributes()
    {
        return [
            'bhpTyp_id' => 'Typ szkolenia BHP',
            'start' => 'Data początkowa',
            'end' => 'Data końcowa',
        ];
    }
}
