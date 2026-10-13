<?php

namespace App\Http\Controllers\ProjectContract;

use App\Actions\ProjectContract\Security\CreateAction;
use App\Actions\ProjectContract\Security\DeleteAction;
use App\Actions\ProjectContract\Security\SettleAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ProjectContract\Concerns\RespondsWithContract;
use App\Http\Requests\ProjectContract\SecurityRequest;
use App\Http\Requests\ProjectContract\SettlementRequest;
use App\Models\ProjectContract;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ContractSecurityController extends Controller
{
    use ApiResponseTrait;
    use RespondsWithContract;

    public function store(int $id, SecurityRequest $request, CreateAction $action): JsonResponse
    {
        $contract = ProjectContract::findOrFail($id);
        $data = ['project_contract_id' => $contract->id] + $request->safe()->except('document');

        return $this->result($action->execute($data, Auth::id(), $request->file('document')), $contract->id);
    }

    public function destroy(int $id, int $securityId, DeleteAction $action): JsonResponse
    {
        $contract = ProjectContract::findOrFail($id);
        $security = $contract->securities()->findOrFail($securityId);

        return $this->result($action->execute($security->id, Auth::id()), $contract->id);
    }

    public function settle(int $id, int $securityId, SettlementRequest $request, SettleAction $action): JsonResponse
    {
        $contract = ProjectContract::findOrFail($id);
        $security = $contract->securities()->findOrFail($securityId);

        return $this->result($action->execute(['project_contract_security_id' => $security->id] + $request->validated(), Auth::id()), $contract->id);
    }
}
