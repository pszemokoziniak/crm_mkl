<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoredestroyStoreRequest extends FormRequest
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
            'end' => 'required | after:' . date('2022-10-04')
        ];
    }

    public function messages(): array
    {
        return [
            'end' => 'Data nie może być wsteczna',
        ];
    }
}
