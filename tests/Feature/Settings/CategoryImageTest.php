<?php

use App\Actions\Settings\Category\DeleteAction;
use App\Livewire\Settings\Category\Page;
use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Settings → Category now carries an image. The file lives on the `public`
 * disk under `categories/`, so the risky parts are the ones that touch storage:
 * replacing a picture has to drop the old file, removing one has to null the
 * column, and cancelling has to leave the stored file alone.
 */
beforeEach(function (): void {
    Storage::fake('public');
    $this->world = PosWorld::create();

    foreach (['category.create', 'category.edit', 'category.delete'] as $name) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate([
            'tenant_id' => $this->world->tenant->id,
            'name' => $name,
            'guard_name' => 'web',
        ]));
    }

    $this->actingAs($this->world->user);
});

it('shows an empty picker on a new category and the current picture on an existing one', function (): void {
    Livewire::test(Page::class)
        ->assertOk()
        ->assertSee('Category Image')
        ->assertDontSee('Remove')
        ->assertSet('categories.image_path', null);

    $category = Category::create(['name' => 'Rings', 'image_path' => 'categories/rings.jpg']);

    Livewire::test(Page::class, ['table_id' => $category->id])
        ->assertOk()
        ->assertSee('storage/categories/rings.jpg')
        ->assertSee('Remove');
});

it('stores an image with a new category', function (): void {
    Livewire::test(Page::class)
        ->set('categories.name', 'Watches')
        ->set('image', UploadedFile::fake()->image('watches.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::firstWhere('name', 'Watches');

    expect($category->image_path)->toStartWith('categories/')
        ->and($category->image_url)->toContain($category->image_path);
    Storage::disk('public')->assertExists($category->image_path);
});

it('drops the previous file when the image is replaced', function (): void {
    $category = Category::create(['name' => 'Bags', 'image_path' => 'categories/old.jpg']);
    Storage::disk('public')->put('categories/old.jpg', 'old');

    Livewire::test(Page::class, ['table_id' => $category->id])
        ->set('image', UploadedFile::fake()->image('new.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk('public')->assertMissing('categories/old.jpg');
    Storage::disk('public')->assertExists($category->fresh()->image_path);
});

it('clears the image when it is removed and saved', function (): void {
    $category = Category::create(['name' => 'Shoes', 'image_path' => 'categories/shoes.jpg']);
    Storage::disk('public')->put('categories/shoes.jpg', 'shoes');

    Livewire::test(Page::class, ['table_id' => $category->id])
        ->call('removeImage')
        ->assertSet('categories.image_path', null)
        ->call('save')
        ->assertHasNoErrors();

    expect($category->fresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing('categories/shoes.jpg');
});

it('keeps the stored file when a removal is never saved', function (): void {
    $category = Category::create(['name' => 'Belts', 'image_path' => 'categories/belts.jpg']);
    Storage::disk('public')->put('categories/belts.jpg', 'belts');

    Livewire::test(Page::class, ['table_id' => $category->id])->call('removeImage');

    expect($category->fresh()->image_path)->toBe('categories/belts.jpg');
    Storage::disk('public')->assertExists('categories/belts.jpg');
});

it('rejects a non image upload', function (): void {
    Livewire::test(Page::class)
        ->set('categories.name', 'Caps')
        ->set('image', UploadedFile::fake()->create('price-list.pdf', 20, 'application/pdf'))
        ->call('save')
        ->assertHasErrors(['image' => 'image']);

    expect(Category::firstWhere('name', 'Caps'))->toBeNull();
});

it('removes the file from disk when the category is deleted', function (): void {
    $category = Category::create(['name' => 'Scarves', 'image_path' => 'categories/scarves.jpg']);
    Storage::disk('public')->put('categories/scarves.jpg', 'scarves');

    expect((new DeleteAction())->execute($category->id)['success'])->toBeTrue();
    Storage::disk('public')->assertMissing('categories/scarves.jpg');
});
