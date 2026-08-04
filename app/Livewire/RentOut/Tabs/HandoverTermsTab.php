<?php

namespace App\Livewire\RentOut\Tabs;

use App\Enums\RentOut\AgreementType;
use App\Models\RentOut;
use App\Support\RentOutHandoverTerms;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Checklist tab → Handover Terms.
 *
 * A repeater over the bilingual clauses printed under the inventory table of
 * this booking's Unit Handover & Snagging checklist (rent_outs.handover_terms).
 *
 * Clauses are held in a map keyed by a throwaway id rather than in a list. The
 * bodies are edited with <x-rich-text-editor>, which is wire:ignore'd: with a
 * list, deleting clause 2 would shift clause 3 into index 2 and every editor
 * below the deleted row would keep showing the previous row's text. A stable key
 * keeps each editor bound to the same clause for as long as it exists.
 */
class HandoverTermsTab extends Component
{
    public $rentOutId;

    public string $heading_en = '';

    public string $heading_ar = '';

    /** clauseKey => ['title_en', 'title_ar', 'body_en', 'body_ar'] */
    public array $clauses = [];

    public function mount($rentOutId): void
    {
        $this->rentOutId = $rentOutId;
        $this->loadData();
    }

    #[On('rent-out-updated')]
    public function refresh(): void
    {
        $this->loadData();
        $this->refreshEditors();
    }

    public function loadData(): void
    {
        $this->hydrateFrom(RentOutHandoverTerms::of($this->rentOut()));

        if ($this->clauses === []) {
            $this->addClause();
        }
    }

    public function addClause(): void
    {
        $this->clauses[$this->newKey()] = RentOutHandoverTerms::emptyClause();
    }

    public function removeClause(string $key): void
    {
        unset($this->clauses[$key]);

        if ($this->clauses === []) {
            $this->addClause();
        }
    }

    /** Fill the form with the shipped starter clauses — nothing is stored until Save. */
    public function loadSample(): void
    {
        $sample = RentOutHandoverTerms::sample();

        if ($sample['clauses'] === []) {
            $this->dispatch('error', message: 'No sample clauses are available.');

            return;
        }

        $this->hydrateFrom($sample);
        $this->refreshEditors();
        $this->dispatch('success', message: 'Sample clauses loaded — review them and press Save.');
    }

    public function save(): void
    {
        $rentOut = $this->rentOut();
        abort_unless(Auth::user()?->can($rentOut?->agreement_type === AgreementType::Lease ? 'rent out lease.edit' : 'rent out.edit'), 403);

        try {
            DB::beginTransaction();

            RentOutHandoverTerms::saveFor($rentOut, [
                'heading_en' => $this->heading_en,
                'heading_ar' => $this->heading_ar,
                'clauses' => array_values($this->clauses),
            ]);

            DB::commit();

            // Re-read so the form shows the sanitised, blank-rows-dropped result.
            $this->loadData();
            $this->refreshEditors();

            $this->dispatch('success', message: 'Handover terms saved successfully.');
        } catch (\Exception $exception) {
            DB::rollback();
            $this->dispatch('error', message: $exception->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.handover-terms-tab');
    }

    private function rentOut(): ?RentOut
    {
        return RentOut::withTrashed()->find($this->rentOutId);
    }

    private function hydrateFrom(array $terms): void
    {
        $this->heading_en = $terms['heading_en'];
        $this->heading_ar = $terms['heading_ar'];

        $this->clauses = [];
        foreach ($terms['clauses'] as $clause) {
            $this->clauses[$this->newKey()] = $clause;
        }
    }

    /** The editors are wire:ignore'd, so they have to be told to re-read. */
    private function refreshEditors(): void
    {
        $this->dispatch('rich-text:refresh');
    }

    private function newKey(): string
    {
        return 'c'.Str::random(12);
    }
}
