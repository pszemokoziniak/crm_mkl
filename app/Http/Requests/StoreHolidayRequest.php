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
            'start' =>'required | date',
            'end' =>'required | date',
            'contact_id' =>'required',
        ];
    }

    public function messages() {
        return [
            'required'  => 'Pole jest wymagane.',
        ];
    }
}
