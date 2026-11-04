<?php

declare(strict_types=1);

namespace Modules\System\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMachineCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>|string>
     */
    public function rules(): array
    {
        return [
            'command' => ['required', 'string', 'in:start,stop,emergency_stop,set_rpm,toggle_relay,reset_alarm,set_alarm_state'],
            'rpm' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'relay_index' => ['nullable', 'integer', 'min:1', 'max:8'],
            'relay_state' => ['nullable', 'boolean'],
            'state' => ['nullable', 'string', 'max:50'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
