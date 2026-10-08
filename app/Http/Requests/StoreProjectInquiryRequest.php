<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreProjectInquiryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Public inquiry form is accessible to all site visitors.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     * Sanitizes inputs to neutralize XSS vectors and normalizes aliases.
     */
    protected function prepareForValidation(): void
    {
        $fullName = $this->input('full_name') ?? $this->input('name');
        $telephone = $this->input('telephone') ?? $this->input('phone');
        $category = $this->input('project_category') ?? $this->input('projectType') ?? $this->input('project_type');
        $vision = $this->input('project_vision') ?? $this->input('message');

        $this->merge([
            // Strip HTML/scripts to prevent persistent XSS
            'full_name' => is_string($fullName) ? strip_tags(trim($fullName)) : $fullName,
            'email' => is_string($this->email) ? strtolower(trim($this->email)) : $this->email,
            'telephone' => is_string($telephone) ? strip_tags(trim($telephone)) : null,
            'project_category' => is_string($category) ? strip_tags(trim($category)) : $category,
            'project_vision' => is_string($vision) ? strip_tags(trim($vision)) : $vision,
        ]);
    }

    /**
     * Strict validation rules preventing malformed data, injection, and invalid formats.
     */
    public function rules(): array
    {
        return [
            'full_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                // Allows international unicode letters, spaces, hyphens, and apostrophes
                'regex:/^[\pL\s\-.\'’]+$/u',
            ],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:150',
            ],
            'telephone' => [
                'nullable',
                'string',
                'max:35',
                // Allows international telephone numbers: +, digits, spaces, parentheses, hyphens
                'regex:/^([0-9\s\-\+\(\)\.]{7,30})$/',
            ],
            'project_category' => [
                'required',
                'string',
                'max:100',
            ],
            'project_vision' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
        ];
    }

    /**
     * Custom user-friendly validation error messages.
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Please provide your full name.',
            'full_name.regex' => 'The full name contains invalid characters.',
            'email.required' => 'An email address is required so we can reply with your project brief.',
            'email.email' => 'Please provide a valid, well-formed email address.',
            'telephone.regex' => 'Please enter a valid telephone number format.',
            'project_category.required' => 'Please select an architectural or design project category.',
            'project_vision.required' => 'Please share a brief description of your project vision.',
            'project_vision.min' => 'Please share at least 10 characters detailing your project requirements.',
            'project_vision.max' => 'The project vision text exceeds the 5,000 character limit.',
        ];
    }

    /**
     * Handle a failed validation attempt with a structured JSON response.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Validation failed. Please review the highlighted fields.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
