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
            'bhpTyp_id' => 'required',
            'start' => 'required|date|before:end',
            'end' => 'required|date|after:start',
        ];
    }

    public function messages()
    {
        return [
            'required' => 'Pole :attribute jest wymagane.',
            'start.before' => 'Pole :attribute musi być wcześniejsze niż data końcowa.',
            'end.after' => 'Pole :attribute musi być późniejsze niż data początkowa.',
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
