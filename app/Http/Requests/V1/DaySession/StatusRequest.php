<?php

namespace App\Http\Requests\V1\DaySession;

use App\Models\SaleDaySession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A request about one branch's day session — the status check, and through
 * [ToggleRequest] the open/close toggle.
 *
 * The branch is the one the app is operating as: the `branch_id` it attaches
 * to every request once a branch is picked in Settings, the same value the
 * dashboard and report endpoints already filter on. Without one the cashier's
 * own branch stands, exactly as before — a build that never sends it, or a
 * user who never switched, is unaffected. Answering for the default branch
 * regardless put one branch's takings beside another branch's day on the
 * dashboard, and let the Day Session screen name one branch while opening
 * the other's day.
 */
class StatusRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'branch_id' => $this->branchRules(),
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'branch_id.exists' => 'The selected branch does not exist.',
        ];
    }

    /**
     * Scoped to the caller's tenant: a bare `exists:branches,id` would let an
     * id from another tenant through, and the toggle would then create a day
     * session against it.
     *
     * @return array<int, mixed>
     */
    protected function branchRules(): array
    {
        return [
            'nullable',
            'integer',
            Rule::exists('branches', 'id')->where('tenant_id', $this->user()?->tenant_id),
        ];
    }

    /**
     * The branch this request is about: the operating branch when the app
     * named one, else the user's default branch.
     *
     * Read from the raw input rather than the validated set because
     * [ToggleRequest]'s own rules need it *while* the rules are being built.
     * A value that fails the `branch_id` rule is rejected before any action
     * runs, so nothing downstream ever sees an unknown branch.
     */
    public function branchId(): ?int
    {
        $requested = $this->input('branch_id');

        if (is_numeric($requested) && (int) $requested > 0) {
            return (int) $requested;
        }

        return $this->user()?->default_branch_id;
    }

    public function openSession(): ?SaleDaySession
    {
        $branchId = $this->branchId();

        return $branchId ? SaleDaySession::getOpenSessionForBranch($branchId) : null;
    }
}
