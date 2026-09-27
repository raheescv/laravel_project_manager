<?php

use App\Imports\ProductImport;
use App\Jobs\Product\ImportProductImagesFromUrlsJob;
use App\Models\Product;
use App\Services\ProductImageUrlImporter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    $this->world = PosWorld::create(stock: 5);
});

function writeProductImportCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'product-import-').'.csv';
    $handle = fopen($path, 'w');
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);

    return $path;
}

it('splits an image cell into valid http links', function (): void {
    $urls = app(ProductImageUrlImporter::class)->parseUrls("https://a.test/1.jpg, https://a.test/2.png | not-a-url\nftp://a.test/3.jpg; https://a.test/1.jpg");

    expect($urls)->toBe(['https://a.test/1.jpg', 'https://a.test/2.png']);
    expect(app(ProductImageUrlImporter::class)->parseUrls(null))->toBe([]);
});

it('rewrites dropbox and google drive share links to direct downloads', function (): void {
    $importer = app(ProductImageUrlImporter::class);

    expect($importer->directDownloadUrl('https://www.dropbox.com/scl/fi/abc/shirt.jpg?rlkey=x&dl=0'))
        ->toBe('https://www.dropbox.com/scl/fi/abc/shirt.jpg?rlkey=x&raw=1');
    expect($importer->directDownloadUrl('https://drive.google.com/file/d/FILE123/view?usp=sharing'))
        ->toBe('https://drive.google.com/uc?export=download&id=FILE123');
    expect($importer->directDownloadUrl('https://cdn.test/shirt.jpg'))->toBe('https://cdn.test/shirt.jpg');
});

it('downloads image links onto the product, sets the thumbnail and skips non-images and repeats', function (): void {
    Storage::fake('public');
    Http::fake([
        'cdn.test/front.jpg' => Http::response('jpeg-bytes', 200, ['Content-Type' => 'image/jpeg']),
        'cdn.test/back.png' => Http::response('png-bytes', 200, ['Content-Type' => 'image/png']),
        'cdn.test/page.html' => Http::response('<html></html>', 200, ['Content-Type' => 'text/html']),
    ]);

    $product = $this->world->product;
    $product->update(['thumbnail' => null]);
    $importer = app(ProductImageUrlImporter::class);

    $summary = $importer->import($product, ['https://cdn.test/front.jpg', 'https://cdn.test/back.png', 'https://cdn.test/page.html']);

    expect($summary['imported'])->toBe(2);
    expect($summary['failed'])->toBe(['https://cdn.test/page.html']);
    expect($product->images()->pluck('name')->all())->toBe(['front.jpg', 'back.png']);
    expect($product->fresh()->thumbnail)->toContain('storage/products/'.$product->id.'/front-');
    expect(Storage::disk('public')->allFiles('products/'.$product->id))->toHaveCount(2);

    $again = $importer->import($product, ['https://cdn.test/front.jpg']);

    expect($again['skipped'])->toBe(1);
    expect($product->images()->count())->toBe(2);
});

it('queues image downloads for products imported with an image column', function (): void {
    Queue::fake();

    $existing = $this->world->product;
    $file = writeProductImportCsv([
        ['id', 'name', 'barcode', 'image'],
        [$existing->id, $existing->name, '99887766', 'https://cdn.test/front.jpg, https://cdn.test/back.jpg'],
        ['', 'Brand New Image Product', '', 'https://cdn.test/new.jpg'],
        ['', 'No Image Product', '', ''],
    ]);

    Excel::import(new ProductImport(
        $this->world->user->id,
        3,
        $this->world->branch->id,
        ['id' => 'id', 'name' => 'name', 'barcode' => 'barcode', 'image' => 'image'],
        'product',
        'Product',
        $this->world->tenant->id,
        'update'
    ), $file);

    @unlink($file);

    $created = Product::query()->where('name', 'Brand New Image Product')->first();
    expect($created)->not->toBeNull();

    Queue::assertPushed(ImportProductImagesFromUrlsJob::class, 2);
    Queue::assertPushed(ImportProductImagesFromUrlsJob::class, function (ImportProductImagesFromUrlsJob $job) use ($existing): bool {
        $payload = (fn () => [$this->productId, $this->urls])->call($job);

        return $payload === [$existing->id, ['https://cdn.test/front.jpg', 'https://cdn.test/back.jpg']];
    });
    Queue::assertPushed(ImportProductImagesFromUrlsJob::class, function (ImportProductImagesFromUrlsJob $job) use ($created): bool {
        return (fn () => $this->productId)->call($job) === $created->id;
    });
});
