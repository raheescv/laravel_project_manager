<?php

namespace App\Http\Controllers\ProjectContract;

use App\Actions\ProjectContract\Document\CreateAction as DocumentCreateAction;
use App\Actions\ProjectContract\Document\DeleteAction as DocumentDeleteAction;
use App\Actions\ProjectContract\Extension\CreateAction as ExtensionCreateAction;
use App\Actions\ProjectContract\Note\CreateAction as NoteCreateAction;
use App\Actions\ProjectContract\Note\DeleteAction as NoteDeleteAction;
use App\Actions\ProjectContract\Note\UpdateAction as NoteUpdateAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ProjectContract\Concerns\RespondsWithContract;
use App\Http\Requests\ProjectContract\DocumentRequest;
use App\Http\Requests\ProjectContract\ExtensionRequest;
use App\Http\Requests\ProjectContract\NoteRequest;
use App\Models\ProjectContract;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/** Extensions, documents and notes on a contract. */
class ContractRecordController extends Controller
{
    use ApiResponseTrait;
    use RespondsWithContract;

    public function extend(int $id, ExtensionRequest $request, ExtensionCreateAction $action): JsonResponse
    {
        $contract = ProjectContract::findOrFail($id);
        $data = ['project_contract_id' => $contract->id] + $request->safe()->except('document');

        return $this->result($action->execute($data, Auth::id(), $request->file('document')), $contract->id);
    }

    public function storeDocuments(int $id, DocumentRequest $request, DocumentCreateAction $action): JsonResponse
    {
        $contract = ProjectContract::findOrFail($id);

        return $this->result($action->execute($contract->id, $request->file('files', []), $request->input('document_type'), Auth::id()), $contract->id);
    }

    public function destroyDocument(int $id, int $documentId, DocumentDeleteAction $action): JsonResponse
    {
        $contract = ProjectContract::findOrFail($id);

        return $this->result($action->execute($documentId, $contract->id), $contract->id);
    }

    public function storeNote(int $id, NoteRequest $request, NoteCreateAction $action): JsonResponse
    {
        $contract = ProjectContract::findOrFail($id);

        return $this->result($action->execute($contract->id, $request->input('note'), Auth::id()), $contract->id);
    }

    public function updateNote(int $id, int $noteId, NoteRequest $request, NoteUpdateAction $action): JsonResponse
    {
        $contract = ProjectContract::findOrFail($id);

        return $this->result($action->execute($noteId, $contract->id, $request->input('note'), Auth::id()), $contract->id);
    }

    public function destroyNote(int $id, int $noteId, NoteDeleteAction $action): JsonResponse
    {
        $contract = ProjectContract::findOrFail($id);

        return $this->result($action->execute($noteId, $contract->id, Auth::id()), $contract->id);
    }
}
