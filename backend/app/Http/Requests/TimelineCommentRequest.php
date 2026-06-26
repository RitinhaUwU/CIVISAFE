<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimelineCommentRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'body' => [$is_patch ? 'sometimes' : 'required', 'string', 'min:1'],
            'start_datetime' => [$is_patch ? 'sometimes' : 'nullable', 'date']
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
