<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', new Enum(UserRole::class)],
            'store_id' => [
                Rule::requiredIf($this->input('role') === UserRole::StoreStaff->value),
                'nullable',
                Rule::exists('stores', 'id')->where('company_id', $this->user()->company_id),
            ],
            'staff_master_id' => [
                'nullable',
                Rule::exists('staff_master', 'id')->where('company_id', $this->user()->company_id),
            ],
        ];
    }
}
