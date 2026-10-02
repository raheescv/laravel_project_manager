<?php

use App\Livewire\Settings\RentOutConfiguration;
use App\Models\Configuration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Settings → Rent Out is a section console: a nav rail where every section
 * reports its own status, and one save bar that applies all of them.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);
});

it('lists every section with its status in the nav', function (): void {
    Configuration::updateOrCreate(['key' => 'rental_reservation_logo'], ['value' => 'rent_out_logos/rental.png']);

    Livewire::test(RentOutConfiguration::class)
        ->assertSeeInOrder(['Mandatory Documents', 'Checklist Notes', 'Print Layout', 'Agreement Logos', 'Annex Pages', 'LPO Header'])
        ->assertSee('1 of 5 set')
        ->assertSee('Logos shown')
        ->assertSee('All sections saved')
        ->assertSeeHtml('rent_out_logos/rental.png');
});

it('previews a newly picked logo before it is saved', function (): void {
    Storage::fake('public');

    Livewire::test(RentOutConfiguration::class)
        ->set('lpo_header_image_file', UploadedFile::fake()->image('header.png'))
        ->assertSee('Applies on save')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Set');

    expect(Configuration::where('key', 'lpo_header_image')->value('value'))->toStartWith('rent_out_logos/');
});

it('flags the section whose upload failed validation', function (): void {
    Storage::fake('public');

    Livewire::test(RentOutConfiguration::class)
        ->set('rental_reservation_logo_file', UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'))
        ->call('save')
        ->assertHasErrors('rental_reservation_logo_file')
        ->assertSee('Needs attention');
});

it('opens the settings page with the main navigation collapsed', function (): void {
    expect(view('settings.index')->render())
        ->toContain('class="root mn--min tm--expanded-hd"')
        ->toContain('data-settings-search')
        ->toContain('data-bs-target="#tabsWorkingDay"');
});

it('keeps the main navigation expanded on other pages', function (): void {
    expect((string) $this->blade('<x-app-layout>Body</x-app-layout>'))
        ->toContain('class="root mn--max tm--expanded-hd"');
});
