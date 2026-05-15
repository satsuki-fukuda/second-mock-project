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
            'end_time' => 'required|date_format:H:i|after:clock_in',
            
            // 既存の休憩チェック
            'breaks.*.start' => 'nullable|date_format:H:i|after:clock_in|before:end_time',
            // 💡 修正点1: 終了時間は開始時間より後であることを必須にする（after:breaks.*.start）
            // 💡 修正点2: 開始が入力されていたら終了も必須にする（required_with）
            'breaks.*.end'   => 'nullable|required_with:breaks.*.start|date_format:H:i|after:breaks.*.start|before:end_time',
            
            // 新規の休憩チェック
            'new_break_start' => 'nullable|date_format:H:i|after:clock_in|before:end_time',
            // 💡 修正点1&2: 新規休憩も同様に開始・終了の連動と前後関係をチェック
            'new_break_end'   => 'nullable|required_with:new_break_start|date_format:H:i|after:new_break_start|before:end_time',

            'note' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in.required' => '出勤時間を入力してください',
            'end_time.required' => '退勤時間を入力してください',
            'end_time.after'    => '出勤時間もしくは退勤時間が不適切な値です',
            
            // 既存休憩のメッセージ（💡 漏れていたパターンも共通メッセージに集約）
            'breaks.*.start.after'   => '休憩時間が不適切な値です',
            'breaks.*.start.before'  => '休憩時間が不適切な値です',
            'breaks.*.end.required_with' => '休憩の終了時間を入力してください',
            'breaks.*.end.after'     => '休憩時間もしくは退勤時間が不適切な値です',
            'breaks.*.end.before'    => '休憩時間もしくは退勤時間が不適切な値です',
            
            // 新規休憩のメッセージ（💡 漏れていた条件のメッセージを追加）
            'new_break_start.after'  => '休憩時間が不適切な値です',
            'new_break_start.before' => '休憩時間が不適切な値です',
            'new_break_end.required_with' => '休憩の終了時間を入力してください',
            'new_break_end.after'    => '休憩時間もしくは退勤時間が不適切な値です',
            'new_break_end.before'   => '休憩時間もしくは退勤時間が不適切な値です',

            'note.required' => '備考を記入してください',
        ];
    }
}