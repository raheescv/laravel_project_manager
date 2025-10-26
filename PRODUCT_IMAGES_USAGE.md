## Product Image Components Usage

### Separate Normal Images and 360° Images

#### 1. In Blade Templates:

```blade
{{-- Display normal product images --}}
<x-product-images :product="$product" />

{{-- Display 360-degree product viewer --}}
<x-product-360-viewer :product="$product" />
```

#### 2. In API Resource (Already Updated):

The `ProductResource` now returns images in two separate arrays:

```json
{
  "id": 1,
  "name": "Product Name",
  "images": [
    {
      "id": 1,
      "path": "https://example.com/image1.jpg",
      "url": "https://example.com/image1.jpg",
      "name": "image1.jpg",
      "size": "123456",
      "type": "jpg",
      "method": "normal"
    }
  ],
  "images360": [
    {
      "id": 5,
      "path": "https://example.com/360/image1.jpg",
      "url": "https://example.com/360/image1.jpg",
      "name": "image1.jpg",
      "size": "123456",
      "type": "jpg",
      "method": "degree",
      "degree": 0,
      "sort_order": 0
    }
  ]
}
```

#### 3. Usage Examples:

**In Product Detail Page:**
```blade
<!-- Product Details -->
<div class="product-details">
    <h1>{{ $product->name }}</h1>
    
    <!-- Regular Product Images -->
    <x-product-images :product="$product" />
    
    <!-- 360° Interactive Viewer -->
    <x-product-360-viewer :product="$product" />
</div>
```

**In Product List (API):**
```php
// Controller
public function show(Product $product)
{
    $product->load([
        'images',
        'unit',
        'brand',
        'mainCategory',
        'subCategory',
        'inventories.branch',
    ]);
    
    return new ProductResource($product);
}
```

**With Custom Image Lists:**
```blade
{{-- Show only first 3 images --}}
@php
    $limitedImages = $product->normalImages()->limit(3)->get();
@endphp
<x-product-images :images="$limitedImages" />

{{-- Show 360 images without viewer --}}
@php
    $angleImages = $product->angleImages()->orderedByAngle()->get();
@endphp
<div class="row">
    @foreach($angleImages as $image)
        <div class="col-md-3">
            <img src="{{ $image->path }}" alt="{{ $image->degree }}°">
        </div>
    @endforeach
</div>
```

#### 4. Relationships Available:

```php
// In Product Model
$product->normalImages()    // Get normal images (method = 'normal')
$product->angleImages()     // Get 360° images (method = 'degree')
$product->images            // Get all images
```

#### 5. Component Props:

**`<x-product-images>`:**
- `product` - Product model (optional if `images` provided)
- `images` - Custom collection of images (optional)

**`<x-product-360-viewer>`:**
- `product` - Product model (optional if `images` provided)
- `images` - Custom collection of 360° images (optional)

### Features:

✅ **Separated Display**: Normal images and 360° viewer are separate components  
✅ **Flexible Usage**: Can use with product model or custom image lists  
✅ **Responsive Design**: Works on all screen sizes  
✅ **Interactive**: 360° viewer with drag/auto-rotate controls  
✅ **Modal Support**: Click to view images in full size  
✅ **API Ready**: Returns data in separate JSON fields

### Database Structure:

```sql
-- product_images table
id              BIGINT
product_id      BIGINT
method          ENUM('normal', 'degree')
degree          INTEGER (0-359)
sort_order      INTEGER
path            VARCHAR
name            VARCHAR
size            VARCHAR
type            VARCHAR (file extension)
```

### API Response Structure:

```json
{
  "images": [],      // Normal product images
  "images360": []    // 360-degree images with angles
}
```
