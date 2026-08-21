<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'comment' => ['required'],
            'new_clock_out' => ['after_or_equal:new_clock_in'],
            'new_break_in.*' => ['after:new_clock_in', 'before:new_clock_out',],
            'new_break_out.*' => ['before:new_clock_out'],

        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => '備考を記入してください',
            'new_clock_out.after_or_equal' =>
            '出勤時間もしくは退勤時間が不適切な値です',
            'new_break_in.*.after' => '休憩時間が不適切な値です',
            'new_break_out.*.before' => '休憩時間もしくは退勤時間が不適切な値です',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $breakIns = $this->input('new_break_in', []);
            $breakOuts = $this->input('new_break_out', []);

            foreach ($breakIns as $index => $breakIn) {
                $breakOut = $breakOuts[$index] ?? null;
                if ($breakIn && $breakOut && $breakOut <= $breakIn) {
                    $validator->errors()->add(
                        "new_break_out.$index",
                        '休憩時間が不適切な値です'
                    );
                }
            }
        });
    }
}
