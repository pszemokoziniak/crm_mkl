<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
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
            'name' => 'required | max:1200',
            'nazwaBud' => 'nullable | max:1200',
            'numerBud' => 'nullable |  max:500',
            'city' => 'nullable | max:2000',
            'kierownikBud_id' => 'nullable | max:50',
            'zaklad' => 'nullable | max:500',
            'country_id' => 'nullable | max:1000',
            'addressBud' => 'nullable | max:2500',
            'addressKwat' => 'nullable | max:2500',
        ];
    }
    public function messages() {
        return [
            'required'  => 'Pole :attribute jest wymagane.',
            'unique' => 'Nazwa użyta',
        ];
    }
}
