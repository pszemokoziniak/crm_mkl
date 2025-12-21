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
            'inzynier_id' => 'nullable | max:50',
            'zaklad' => 'nullable | max:500',
            'country_id' => 'required',
            'addressBud' => 'nullable | max:2500',
            'addressKwat' => 'nullable | max:2500',

            'kierownikBud_ids' => ['nullable', 'array'],
            'kierownikBud_ids.*' => ['integer', 'exists:contacts,id'],

            'inzynier_ids' => ['nullable', 'array'],
            'inzynier_ids.*' => ['integer', 'exists:contacts,id'],
        ];
    }
    public function messages() {
        return [
            'required'  => 'Pole :attribute jest wymagane.',
            'unique' => 'Nazwa użyta',
        ];
    }
}
