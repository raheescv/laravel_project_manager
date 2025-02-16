<div class="row">
    @foreach ($products as $item)
        <div wire:key="product-{{ $item['id'] }}" class="col-sm-2 col-md-6 col-lg-3 col-xl-3" wire:click="selectItem({{ $item['id'] }})">
            <div class="product-info default-cover card">
                <a href="#" class="img-bg">
                    <img src="{{ $item['thumbnail'] ?? cache('logo') }}" width="100%" height="100%" alt="Products">
                    <span><i class="ti-check btn_t"></i></span>
                </a>
                <h6 class="product-name"><a href="#">{{ $item['name'] }}</a></h6>
                <div class="d-flex align-items-center justify-content-between price">
                    @if ($item['type'] == 'product')
                        <span>{{ $item['quantity'] }}</span>
                    @endif
                    <span class="span_bt">{{ currency($item['mrp']) }}</span>
                </div>
            </div>
        </div>
    @endforeach
</div>
