<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CorrectStoreRequest extends FormRequest
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
            'new_clock_in' => 'required|regex:/^\d{2}:\d{2}(:\d{2})?$/',
            'new_clock_out' => 'required|regex:/^\d{2}:\d{2}(:\d{2})?$/',
            'new_break_in.*' => 'nullable|regex:/^\d{2}:\d{2}(:\d{2})?$/',
            'new_break_out.*' => 'nullable|regex:/^\d{2}:\d{2}(:\d{2})?$/',
            'comment' => 'required|max:255',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $preBO = null;

            $clockIn = $this->new_clock_in;
            $clockOut = $this->new_clock_out;

            if ($clockIn == null || $clockOut == null) {
                return;
            }

            $clockInTime = CarbonImmutable::parse($clockIn)->format('H:i');
            $clockOutTime = CarbonImmutable::parse($clockOut)->format('H:i');

            if ($clockOutTime->lessThan($clockInTime)) {
                $validator->errors()->add('new_clock_out', '出勤時間もしくは退勤時間が不適切な値です');
            }

            $breakIns = $this->new_break_in;
            $breakOuts = $this->new_break_out;

            if ($breakIns == null && $breakOuts == null) {
                return;
            }

            foreach ($breakIns as $key => $bI) {
                $bO = $breakOuts[$key] ?? null;

                if ($bI == null && $bO == null) {
                    return;
                }

                if ($bI == null || $bO == null) {
                    $validator->errors()->add("new_break_in.$key", '休憩の入りと戻りはセットで入力してください');
                }

                $bITime = CarbonImmutable::parse($bI)->formmat('H:i');
                $bOTime = CarbonImmutable::parse($bO)->formmat('H:i');

                if ($preBO && $bITime->lessThan($preBO)) {
                    $validator->errors()->add("new_break_in.$key", '休憩は前の休憩戻りより後に開始してください');
                }

                if (($bITime->lessThan($clockInTime)) || $bITime->greaterThan($clockOutTime)) {
                    $validator->errors()->add("new_break_in.$key", '休憩時間が不適切な値です');
                }

                if ($bOTime->greaterThan($clockOutTime)) {
                    $validator->errors()->add("new_break_in.$key", '休憩時間もしくは退勤時間が不適切な値です');
                }

            }
        });
    }

    public function messages(): array
    {
        return [
            'new_clock_in.required' => '出勤時間は必須です',
            'new_clock_out.required' => '退勤時間は必須です',
            'new_clock_in.date_format' => '出勤時間はH:i形式(例：21：05)で入力してください',
            'new_clock_out.date_format' => '退勤時間はH:i形式(例：21：05)で入力してください',
            'new_break_in.*.date_format' => '休憩入時間はH:i形式(例：21：05)で入力してください',
            'new_break_out.*.date_format' => '休憩戻時間はH:i形式(例：21：05)で入力してください',
            'comment.required' => '備考を記入してください',
            'comment.max' => '備考は255文字以内で入力してください',
        ];
    }
}
