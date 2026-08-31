<?php

use App\Models\Product;
use App\Models\Tenant;
use App\Services\ProductImageFolderMatcher;
use App\Services\TenantService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

uses(Tests\TestCase::class);

it('forces dropbox shared links to download mode', function (): void {
    $matcher = new ProductImageFolderMatcher();

    $downloadUrl = $matcher->buildDropboxDownloadUrl('https://www.dropbox.com/scl/fo/test-folder/example?rlkey=abc123&st=token&dl=0');

    expect($downloadUrl)->toBe('https://www.dropbox.com/scl/fo/test-folder/example?rlkey=abc123&st=token&dl=1');
});

it('collects normalized codes from image filenames in a zip archive', function (): void {
    $zipPath = tempnam(sys_get_temp_dir(), 'matcher-test-');
    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('ABC123.jpg', 'image');
    $zip->addFromString('nested/xyz-99.PNG', 'image');
    $zip->addFromString('notes/readme.txt', 'ignore');
    $zip->close();

    $matcher = new ProductImageFolderMatcher();

    $codes = $matcher->collectCodesFromZip($zipPath);

    @unlink($zipPath);

    expect($codes)->toBe(['abc123', 'xyz-99']);
});

it('imports matched images and sets the first thumbnail from a zip archive', function (): void {
    $diskRoot = sys_get_temp_dir().'/matcher-public-'.uniqid();
    mkdir($diskRoot, 0777, true);
    Config::set('filesystems.disks.public.root', $diskRoot);

    collect(['product_images', 'products', 'jobs', 'tenants'])->each(fn (string $table) => Schema::dropIfExists($table));

    Schema::create('tenants', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('code')->unique();
        $table->string('subdomain')->unique();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('jobs', function (Blueprint $table): void {
        $table->id();
        $table->string('queue')->index();
        $table->longText('payload');
        $table->unsignedTinyInteger('attempts');
        $table->unsignedInteger('reserved_at')->nullable();
        $table->unsignedInteger('available_at');
        $table->unsignedInteger('created_at');
    });

    Schema::create('products', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id');
        $table->string('type')->default('product');
        $table->string('code');
        $table->string('name');
        $table->string('thumbnail')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('product_images', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('product_id');
        $table->string('method')->default('normal');
        $table->unsignedInteger('degree')->nullable();
        $table->unsignedInteger('sort_order')->nullable();
        $table->string('path');
        $table->unsignedBigInteger('size')->nullable();
        $table->string('type')->nullable();
        $table->string('name');
        $table->timestamps();
    });

    $tenant = Tenant::query()->create([
        'name' => 'Tenant',
        'code' => 'TEN',
        'subdomain' => 'tenant',
    ]);

    app(TenantService::class)->setCurrentTenant($tenant);

    $product = Product::query()->create([
        'tenant_id' => $tenant->id,
        'type' => 'product',
        'code' => 'ABC123',
        'name' => 'Product A',
    ]);

    $zipPath = tempnam(sys_get_temp_dir(), 'matcher-import-');
    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('ABC123.jpg', 'image-one');
    $zip->addFromString('ABC123.png', 'image-two');
    $zip->addFromString('NO_MATCH.jpg', 'image-three');
    $zip->close();

    $matcher = new ProductImageFolderMatcher();
    $summary = $matcher->importMatchedImagesFromZip($zipPath);

    @unlink($zipPath);

    expect($summary['imported_images'])->toBe(2);
    expect($summary['matched_product_codes'])->toBe(1);
    expect($summary['missing_product_codes'])->toBe(1);
    expect($summary['matched_products'])->toBe([
        [
            'id' => $product->id,
            'code' => 'ABC123',
            'name' => 'Product A',
            'matched_by' => 'code',
        ],
    ]);
    expect($product->images()->count())->toBe(2);
    expect($product->fresh()->thumbnail)->not->toBeNull();
});

it('matches multiple image filenames for a single product code using suffixes', function (): void {
    $diskRoot = sys_get_temp_dir().'/matcher-public-'.uniqid();
    mkdir($diskRoot, 0777, true);
    Config::set('filesystems.disks.public.root', $diskRoot);

    collect(['product_images', 'products', 'jobs', 'tenants'])->each(fn (string $table) => Schema::dropIfExists($table));

    Schema::create('tenants', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('code')->unique();
        $table->string('subdomain')->unique();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('jobs', function (Blueprint $table): void {
        $table->id();
        $table->string('queue')->index();
        $table->longText('payload');
        $table->unsignedTinyInteger('attempts');
        $table->unsignedInteger('reserved_at')->nullable();
        $table->unsignedInteger('available_at');
        $table->unsignedInteger('created_at');
    });

    Schema::create('products', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id');
        $table->string('type')->default('product');
        $table->string('code');
        $table->string('name');
        $table->string('thumbnail')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('product_images', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('product_id');
        $table->string('method')->default('normal');
        $table->unsignedInteger('degree')->nullable();
        $table->unsignedInteger('sort_order')->nullable();
        $table->string('path');
        $table->unsignedBigInteger('size')->nullable();
        $table->string('type')->nullable();
        $table->string('name');
        $table->timestamps();
    });

    $tenant = Tenant::query()->create([
        'name' => 'Tenant',
        'code' => 'TEN',
        'subdomain' => 'tenant',
    ]);

    app(TenantService::class)->setCurrentTenant($tenant);

    $product = Product::query()->create([
        'tenant_id' => $tenant->id,
        'type' => 'product',
        'code' => 'ABC123',
        'name' => 'Product A',
    ]);

    Product::query()->create([
        'tenant_id' => $tenant->id,
        'type' => 'product',
        'code' => 'XYZ-99',
        'name' => 'Product B',
    ]);

    $zipPath = tempnam(sys_get_temp_dir(), 'matcher-import-');
    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('ABC123-1.jpg', 'image-one');
    $zip->addFromString('ABC123_front.png', 'image-two');
    $zip->addFromString('XYZ-99(2).jpeg', 'image-three');
    $zip->addFromString('ABC123ALT.jpg', 'image-four');
    $zip->close();

    $matcher = new ProductImageFolderMatcher();
    $summary = $matcher->importMatchedImagesFromZip($zipPath);

    @unlink($zipPath);

    expect($summary['imported_images'])->toBe(3);
    expect($summary['total_image_files'])->toBe(4);
    expect($summary['matched_image_files'])->toBe(3);
    expect($summary['missing_image_files'])->toBe(1);
    expect($summary['matched_product_codes'])->toBe(2);
    expect($summary['missing_product_codes'])->toBe(1);
    expect($summary['total_file_codes'])->toBe(3);
    expect($product->images()->count())->toBe(2);
    expect($summary['missing_codes'])->toContain('abc123alt');
});

it('matches image filenames against product names when no code matches', function (): void {
    $diskRoot = sys_get_temp_dir().'/matcher-public-'.uniqid();
    mkdir($diskRoot, 0777, true);
    Config::set('filesystems.disks.public.root', $diskRoot);

    collect(['product_images', 'products', 'jobs', 'tenants'])->each(fn (string $table) => Schema::dropIfExists($table));

    Schema::create('tenants', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('code')->unique();
        $table->string('subdomain')->unique();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('jobs', function (Blueprint $table): void {
        $table->id();
        $table->string('queue')->index();
        $table->longText('payload');
        $table->unsignedTinyInteger('attempts');
        $table->unsignedInteger('reserved_at')->nullable();
        $table->unsignedInteger('available_at');
        $table->unsignedInteger('created_at');
    });

    Schema::create('products', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id');
        $table->string('type')->default('product');
        $table->string('code');
        $table->string('name');
        $table->string('thumbnail')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('product_images', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('product_id');
        $table->string('method')->default('normal');
        $table->unsignedInteger('degree')->nullable();
        $table->unsignedInteger('sort_order')->nullable();
        $table->string('path');
        $table->unsignedBigInteger('size')->nullable();
        $table->string('type')->nullable();
        $table->string('name');
        $table->timestamps();
    });

    $tenant = Tenant::query()->create([
        'name' => 'Tenant',
        'code' => 'TEN',
        'subdomain' => 'tenant',
    ]);

    app(TenantService::class)->setCurrentTenant($tenant);

    $byName = Product::query()->create([
        'tenant_id' => $tenant->id,
        'type' => 'product',
        'code' => 'SKU-1',
        'name' => 'Blue Shirt',
    ]);

    $byCode = Product::query()->create([
        'tenant_id' => $tenant->id,
        'type' => 'product',
        'code' => 'ABC123',
        'name' => 'Product A',
    ]);

    $zipPath = tempnam(sys_get_temp_dir(), 'matcher-import-');
    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('Blue Shirt.jpg', 'image-one');
    $zip->addFromString('blue-shirt-2.png', 'image-two');
    $zip->addFromString('blue_shirt(3).jpeg', 'image-three');
    $zip->addFromString('ABC123.jpg', 'image-four');
    $zip->addFromString('Green Trousers.jpg', 'image-five');
    $zip->close();

    $matcher = new ProductImageFolderMatcher();
    $summary = $matcher->importMatchedImagesFromZip($zipPath);

    @unlink($zipPath);

    expect($byName->images()->count())->toBe(3);
    expect($byCode->images()->count())->toBe(1);
    expect($summary['imported_images'])->toBe(4);
    expect($summary['matched_image_files'])->toBe(4);
    expect($summary['missing_image_files'])->toBe(1);
    expect($summary['missing_codes'])->toContain('green trousers');
    expect(collect($summary['matched_products'])->firstWhere('id', $byName->id)['matched_by'])->toBe('name');
    expect(collect($summary['matched_products'])->firstWhere('id', $byCode->id)['matched_by'])->toBe('code');
});

it('accumulates every source a product was matched through', function (): void {
    $matcher = new ProductImageFolderMatcher();
    $matchedProducts = collect();

    $product = new Product();
    $product->forceFill(['id' => 7, 'code' => 'RED-CAP', 'name' => 'Crimson Cap']);

    $remember = (new ReflectionClass($matcher))->getMethod('rememberMatchedProduct');
    $remember->setAccessible(true);
    $remember->invoke($matcher, $matchedProducts, $product, 'name');
    $remember->invoke($matcher, $matchedProducts, $product, 'code');
    $remember->invoke($matcher, $matchedProducts, $product, 'code');

    expect($matchedProducts)->toHaveCount(1);
    expect($matchedProducts->get(7)['matched_by'])->toBe('code, name');
});

it('prefers a product code match over a product name match', function (): void {
    $diskRoot = sys_get_temp_dir().'/matcher-public-'.uniqid();
    mkdir($diskRoot, 0777, true);
    Config::set('filesystems.disks.public.root', $diskRoot);

    collect(['product_images', 'products', 'jobs', 'tenants'])->each(fn (string $table) => Schema::dropIfExists($table));

    Schema::create('tenants', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('code')->unique();
        $table->string('subdomain')->unique();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('jobs', function (Blueprint $table): void {
        $table->id();
        $table->string('queue')->index();
        $table->longText('payload');
        $table->unsignedTinyInteger('attempts');
        $table->unsignedInteger('reserved_at')->nullable();
        $table->unsignedInteger('available_at');
        $table->unsignedInteger('created_at');
    });

    Schema::create('products', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id');
        $table->string('type')->default('product');
        $table->string('code');
        $table->string('name');
        $table->string('thumbnail')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('product_images', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('product_id');
        $table->string('method')->default('normal');
        $table->unsignedInteger('degree')->nullable();
        $table->unsignedInteger('sort_order')->nullable();
        $table->string('path');
        $table->unsignedBigInteger('size')->nullable();
        $table->string('type')->nullable();
        $table->string('name');
        $table->timestamps();
    });

    $tenant = Tenant::query()->create([
        'name' => 'Tenant',
        'code' => 'TEN',
        'subdomain' => 'tenant',
    ]);

    app(TenantService::class)->setCurrentTenant($tenant);

    $codeOwner = Product::query()->create([
        'tenant_id' => $tenant->id,
        'type' => 'product',
        'code' => 'RED-CAP',
        'name' => 'Crimson Cap',
    ]);

    $nameOwner = Product::query()->create([
        'tenant_id' => $tenant->id,
        'type' => 'product',
        'code' => 'SKU-2',
        'name' => 'Red Cap',
    ]);

    $zipPath = tempnam(sys_get_temp_dir(), 'matcher-import-');
    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('RED-CAP.jpg', 'image-one');
    $zip->close();

    $matcher = new ProductImageFolderMatcher();
    $matcher->importMatchedImagesFromZip($zipPath);

    @unlink($zipPath);

    expect($codeOwner->images()->count())->toBe(1);
    expect($nameOwner->images()->count())->toBe(0);
});
