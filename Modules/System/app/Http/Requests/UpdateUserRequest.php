<?php

declare(strict_types=1);

namespace Modules\System\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\System\Models\User;

/**
 * Validates the update of an existing system user.
 */
class UpdateUserRequest extends FormRequest
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
     * Returns:
     *     An array of validation rules.
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user instanceof User ? $user->id : (int) $user),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
        ];
    }
}
