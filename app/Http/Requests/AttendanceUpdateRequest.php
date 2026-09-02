<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceUpdateRequest extends FormRequest
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
            'new_clock_in' => 'required|date_format:H:i',
            'new_clock_out' => 'required|date_format:H:i',
            'new_break_in.*' => 'nullable|date_format:H:i',
            'new_break_out.*' => 'nullable|date_format:H:i',
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

            $clockInTime = CarbonImmutable::createFromFormat('H:i', mb_convert_kana($clockIn, 'ask'));
            $clockOutTime = CarbonImmutable::createFromFormat('H:i', mb_convert_kana($clockOut, 'ask'));

            if ($clockOutTime->lessThanOrEqualTo($clockInTime)) {
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

                if (! preg_match('/^\d{2}:\d{2}$/', $bI) || ! preg_match('/^\d{2}:\d{2}$/', $bO)) {
                    return;
                }

                $bITime = CarbonImmutable::createFromFormat('H:i', $bI);
                $bOTime = CarbonImmutable::createFromFormat('H:i', $bO);

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
