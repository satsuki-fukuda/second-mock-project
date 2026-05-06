<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectionRequestRequest extends FormRequest
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
        'clock_in' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i|after:clock_in', // 1の条件
        
        // 2・3の条件：既存・新規の休憩すべてに対してチェック
        'breaks.*.start'   => 'nullable|date_format:H:i|after:clock_in|before:end_time',
        'breaks.*.end'     => 'nullable|date_format:H:i|after:clock_in|before:end_time',
        'new_break_start'  => 'nullable|date_format:H:i|after:clock_in|before:end_time',
        'new_break_end'    => 'nullable|date_format:H:i|after:clock_in|before:end_time',

            'note' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
        'end_time.after' => '出勤時間もしくは退勤時間が不適切な値です',
        'breaks.*.start.before' => '休憩時間が不適切な値です',
        'breaks.*.start.after'  => '休憩時間が不適切な値です',
        'breaks.*.end.before'   => '休憩時間もしくは退勤時間が不適切な値です',
        'breaks.*.end.after'    => '休憩時間もしくは退勤時間が不適切な値です',
        'new_break_start.after' => '休憩時間が不適切な値です',
        'new_break_end.before'  => '休憩時間もしくは退勤時間が不適切な値です',


            'note.required' => '備考を記入してください',
        ];
    }
}
