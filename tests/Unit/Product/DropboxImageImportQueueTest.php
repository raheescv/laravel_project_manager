<?php

use App\Jobs\Product\ImportProductImagesFromDropboxJob;
use App\Livewire\Product\Import as ProductImportComponent;
use App\Models\Tenant;
use App\Services\ProductImageFolderMatcher;
use App\Services\TenantService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    Config::set('cache.default', 'array');

    collect(['categories', 'departments', 'units', 'tenants'])->each(fn (string $table) => Schema::dropIfExists($table));

    Schema::create('tenants', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('code')->unique();
        $table->string('subdomain')->unique();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('units', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id')->nullable();
        $table->string('name');
        $table->string('code')->nullable();
        $table->timestamps();
    });

    Schema::create('departments', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id')->nullable();
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('categories', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id')->nullable();
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->string('name');
        $table->timestamps();
    });
});

it('queues dropbox product image imports from the livewire component', function (): void {
    Queue::fake();

    $tenant = Tenant::query()->create([
        'name' => 'Tenant',
        'code' => 'TEN',
        'subdomain' => 'tenant',
    ]);

    session(['tenant_id' => $tenant->id]);

    Livewire::test(ProductImportComponent::class)
        ->set('dropboxFolderUrl', 'https://www.dropbox.com/scl/fo/test-folder/example?rlkey=abc123&st=token&dl=0')
        ->call('importDropboxFolderImages')
        ->assertSet('dropboxImportQueued', true)
        ->assertSet('dropboxImportSummary', null);

    Queue::assertPushed(ImportProductImagesFromDropboxJob::class, 1);
});

it('switches to a separate image upload tab on the import screen', function (): void {
    Livewire::test(ProductImportComponent::class)
        ->assertSet('stepOneTab', 'spreadsheet')
        ->assertSee('Spreadsheet Import')
        ->assertSee('Image Upload')
        ->assertDontSee('Dropbox Folder Match Check')
        ->call('setStepOneTab', 'images')
        ->assertSet('stepOneTab', 'images')
        ->assertSee('Dropbox Folder Match Check')
        ->assertSee('Upload Product Images')
        ->assertSee('ABC123-1.jpg')
        ->assertSee('ABC123_front.png');
});

it('runs dropbox product image imports inside the tenant context', function (): void {
    $tenant = Tenant::query()->create([
        'name' => 'Tenant',
        'code' => 'TEN',
        'subdomain' => 'tenant',
    ]);

    $state = (object) [
        'called' => false,
        'tenantIdDuringRun' => null,
        'sharedUrl' => null,
    ];

    app()->instance(ProductImageFolderMatcher::class, new class($state) extends ProductImageFolderMatcher
    {
        public function __construct(private object $state) {}

        public function importMatchedImagesFromDropboxFolder(string $sharedUrl): array
        {
            $this->state->called = true;
            $this->state->tenantIdDuringRun = app(TenantService::class)->getCurrentTenantId();
            $this->state->sharedUrl = $sharedUrl;

            return [];
        }
    });

    $job = new ImportProductImagesFromDropboxJob(
        'https://www.dropbox.com/scl/fo/test-folder/example?rlkey=abc123&st=token&dl=0',
        $tenant->id,
    );

    $job->handle(app(ProductImageFolderMatcher::class));

    expect($state->called)->toBeTrue();
    expect($state->tenantIdDuringRun)->toBe($tenant->id);
    expect($state->sharedUrl)->toBe('https://www.dropbox.com/scl/fo/test-folder/example?rlkey=abc123&st=token&dl=0');
    expect(app(TenantService::class)->getCurrentTenant())->toBeNull();
});
