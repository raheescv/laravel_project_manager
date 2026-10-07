<?php

namespace App\Actions\V1\Technician\Checklist;

use App\Actions\RentOut\Checklist\SaveSignatureAction;
use App\Actions\V1\Technician\Concerns\InteractsWithChecklist;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Enums\RentOut\ChecklistPhase;
use App\Enums\RentOut\ChecklistSignatoryRole;
use App\Models\RentOut;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Mobile wrapper: capture a hand-over signature. A coordinator signs their own
 * role(s) under their account, and takes the lessee's signature in person on
 * their device. The other coordinator's role is theirs alone to sign.
 */
class SignAction
{
    use InteractsWithChecklist, LogsApiActivity;

    public function __construct(private readonly SaveSignatureAction $action = new SaveSignatureAction()) {}

    public function execute(int $id, ChecklistPhase $phase, ChecklistSignatoryRole $role, string $signature, ?string $signerName = null): RentOut
    {
        return $this->withApiLog('Technician Checklist Sign', ['rent_out_id' => $id, 'phase' => $phase->value, 'role' => $role->value], function () use ($id, $phase, $role, $signature, $signerName) {
            $rentOut = $this->findOwnedRentOutFor($id, $phase);
            $rentOut->loadMissing('account');

            $isOwnRole = in_array($role, $this->rolesOf($rentOut), true);
            if (! $isOwnRole && $role !== ChecklistSignatoryRole::Lessee) {
                $label = $role->labelFor($rentOut->agreement_type);
                throw new AccessDeniedHttpException("Only the assigned {$label} can sign this role.");
            }

            $this->runShared($this->action->execute([
                'rent_out_id' => $rentOut->id,
                'phase' => $phase->value,
                'role' => $role->value,
                'user_id' => $isOwnRole ? Auth::id() : null,
                'signer_name' => filled($signerName) ? trim($signerName) : ($isOwnRole ? Auth::user()?->name : $rentOut->account?->name),
                'signature' => $signature,
            ]));

            return $this->findOwnedRentOutWithDetail($id);
        });
    }
}
