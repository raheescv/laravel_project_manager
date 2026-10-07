<?php

namespace App\Actions\V1\Technician\Checklist;

use App\Actions\RentOut\Checklist\GeneratePdfAction;
use App\Actions\V1\Technician\Concerns\InteractsWithChecklist;

/**
 * The hand-over checklist PDF for a rent-out the user coordinates — the same
 * document as the web tab's "Download PDF".
 */
class PdfAction
{
    use InteractsWithChecklist;

    public function __construct(private readonly GeneratePdfAction $action = new GeneratePdfAction()) {}

    /**
     * @return array{pdf: string, filename: string}
     */
    public function execute(int $id): array
    {
        $rentOut = $this->findOwnedRentOut($id);
        $rentOut->loadMissing('property');

        $unit = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($rentOut->property?->number ?: $rentOut->id));

        return [
            'pdf' => $this->action->execute($rentOut->id),
            'filename' => "handover-checklist-{$unit}.pdf",
        ];
    }
}
