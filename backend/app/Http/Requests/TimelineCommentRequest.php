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
            'user_id' => [$is_patch ? 'sometimes' : 'required', 'exists:users,id'],
            'created_at' => [$is_patch ? 'sometimes' : 'nullable', 'nullable', 'date']
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
