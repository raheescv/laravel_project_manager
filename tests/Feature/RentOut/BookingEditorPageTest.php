<?php

use App\Livewire\RentOut\Page;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The booking / agreement editor is a "Summary Rail" form: numbered sections
 * on the left, a live unit + money summary and every action on the right.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);
});

it('renders the sale booking editor with its sections and summary rail', function (): void {
    Livewire::test(Page::class, ['type' => 'Booking', 'agreementType' => 'lease'])
        ->assertSee('New Sale Booking')
        ->assertSeeInOrder(['Property &amp; Customer', 'Sale Details', 'Down Payment &amp; Collection', 'Remarks &amp; Terms'], false)
        ->assertSee('Contract total')
        ->assertSee('Select a unit')
        ->assertDontSee('Included amenities');
});

it('keeps the rail figures in step with the form', function (): void {
    Livewire::test(Page::class, ['type' => 'Booking', 'agreementType' => 'lease'])
        ->set('rent_outs.rent', 1000)
        ->set('rent_outs.no_of_terms', 12)
        ->set('rent_outs.down_payment', 2000)
        ->assertSet('rent_outs.total', 12000)
        ->assertSeeInOrder(['Contract total', '12,000.00', 'Down 17%', 'Down payment', '2,000.00', 'Balance', '10,000.00']);
});

it('picks frequency and payment mode with a tap and only asks for bank details off cash', function (): void {
    Livewire::test(Page::class, ['type' => 'Booking', 'agreementType' => 'lease'])
        ->assertDontSee('Cheque starting no.')
        ->call('$set', 'rent_outs.payment_frequency', 'Quarterly')
        ->assertSet('rent_outs.payment_frequency', 'Quarterly')
        ->call('$set', 'rent_outs.collection_payment_mode', 'cheque')
        ->assertSee('Bank name')
        ->assertSee('Cheque starting no.');
});

it('shows rental-only booking type and amenity toggles on a rental booking', function (): void {
    Livewire::test(Page::class, ['type' => 'Booking', 'agreementType' => 'rental'])
        ->assertSee('New Rental Booking')
        ->assertSee('Included amenities')
        ->assertSee('Monthly Collection')
        ->assertDontSee('Down Payment &amp; Collection', false)
        ->call('$set', 'rent_outs.include_wifi', 'Excluded')
        ->assertSee('WiFi: Excluded');
});
