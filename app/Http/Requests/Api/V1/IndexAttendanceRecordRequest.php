<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexAttendanceRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['integer', 'nullable'],
            'date' => ['date', 'nullable'],
            'month' => ['date_format:Y-m', 'nullable'],
            'month' => ['string', 'date_format:Y-m', 'nullable'],
            'page' => ['integer', 'nullable'],
            'per_page' => ['integer', 'nullable', 'max:100'],
        ];
    }
}
