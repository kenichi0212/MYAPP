<?php

namespace App\Http\Requests;

use App\Models\ExpiryCheckLog;
use App\Models\Store;
use App\Rules\NotPastExpiryDate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpiryCheckLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $store = Store::find($this->input('store_id'));

        if (! $store) {
            return false;
        }

        return $this->user()->can('create', [ExpiryCheckLog::class, $store]);
    }

    public function rules(): array
    {
        return [
            'store_id' => [
                'required',
                Rule::exists('stores', 'id')->where('company_id', $this->user()->company_id),
            ],
            'jan_code' => ['required', 'regex:/\A\d{8}\z|\A\d{13}\z/'],
            'product_name' => ['required', 'string', 'max:255'],
            'maker_name' => ['nullable', 'string', 'max:255'],
            'name_source' => ['required', Rule::in(['master', 'api', 'manual'])],
            'expiry_date' => ['required', 'date', new NotPastExpiryDate()],
            'quantity' => ['required', 'integer', 'min:0'],
            'is_zero_report' => ['boolean'],
        ];
    }
}
