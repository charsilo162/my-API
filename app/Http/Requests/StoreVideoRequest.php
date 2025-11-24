<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'video_file' => 'required|file|mimes:mp4,mov,avi,wmv|max:102400',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'duration' => 'nullable|integer|min:1',
            'order_index' => 'required|integer|min:1',
        ];
    }
}