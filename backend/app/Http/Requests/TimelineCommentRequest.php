<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimelineCommentRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'body' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'user_id' => [$is_patch ? 'sometimes' : 'required', 'exists:users,id']
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
