<?php

namespace App\Http\Requests\Operations\PinCodes;

use App\Models\PinCode;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmPinCodeImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('import', PinCode::class) ?? false;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'confirm_import' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'confirm_import' => __('confirmation'),
        ];
    }
}
