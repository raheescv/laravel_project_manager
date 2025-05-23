<x-app-layout>

    @section('content')
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Barcode Settings</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('inventory::barcode::save-settings') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="width" class="form-label">Barcode Width (mm)</label>
                                <input type="number" class="form-control" id="width" name="width" value="{{ $settings['width'] ?? 40 }}" min="20" max="200" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="height" class="form-label">Barcode Height (mm)</label>
                                <input type="number" class="form-control" id="height" name="height" value="{{ $settings['height'] ?? 30 }}" min="10" max="200" required>
                            </div>
                        </div>
                    </div>

                    <!-- Product Name Settings -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Product Name Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Font Size (px)</label>
                                        <input type="number" class="form-control" name="product_name[font_size]" value="{{ $settings['product_name']['font_size'] ?? 12 }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Max Length</label>
                                        <input type="number" class="form-control" name="product_name[no_of_letters]" value="{{ $settings['product_name']['no_of_letters'] ?? 12 }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Alignment</label>
                                        <select class="form-select" name="product_name[align]">
                                            <option value="left" {{ ($settings['product_name']['align'] ?? '') == 'left' ? 'selected' : '' }}>Left</option>
                                            <option value="center" {{ ($settings['product_name']['align'] ?? '') == 'center' ? 'selected' : '' }}>Center</option>
                                            <option value="right" {{ ($settings['product_name']['align'] ?? '') == 'right' ? 'selected' : '' }}>Right</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Arabic Product Name Settings -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Arabic Product Name Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Font Size (px)</label>
                                        <input type="number" class="form-control" name="product_arabic_name[font_size]" value="{{ $settings['product_arabic_name']['font_size'] ?? 12 }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Max Length</label>
                                        <input type="number" class="form-control" name="product_arabic_name[no_of_letters]" value="{{ $settings['product_arabic_name']['no_of_letters'] ?? 12 }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Alignment</label>
                                        <select class="form-select" name="product_arabic_name[align]">
                                            <option value="left" {{ ($settings['product_arabic_name']['align'] ?? '') == 'left' ? 'selected' : '' }}>Left</option>
                                            <option value="center" {{ ($settings['product_arabic_name']['align'] ?? '') == 'center' ? 'selected' : '' }}>Center</option>
                                            <option value="right" {{ ($settings['product_arabic_name']['align'] ?? '') == 'right' ? 'selected' : '' }}>Right</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Barcode Image Settings -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Barcode Image Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Width</label>
                                        <input type="number" class="form-control" name="barcode_image[width]" value="{{ $settings['barcode_image']['width'] ?? 3 }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Height</label>
                                        <input type="number" class="form-control" name="barcode_image[height]" value="{{ $settings['barcode_image']['height'] ?? 40 }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Alignment</label>
                                        <select class="form-select" name="barcode_image[align]">
                                            <option value="left" {{ ($settings['barcode_image']['align'] ?? '') == 'left' ? 'selected' : '' }}>Left</option>
                                            <option value="center" {{ ($settings['barcode_image']['align'] ?? '') == 'center' ? 'selected' : '' }}>Center</option>
                                            <option value="right" {{ ($settings['barcode_image']['align'] ?? '') == 'right' ? 'selected' : '' }}>Right</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Price Settings -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Price Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Font Size (px)</label>
                                        <input type="number" class="form-control" name="mrp[font_size]" value="{{ $settings['mrp']['font_size'] ?? 12 }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Alignment</label>
                                        <select class="form-select" name="mrp[align]">
                                            <option value="left" {{ ($settings['mrp']['align'] ?? '') == 'left' ? 'selected' : '' }}>Left</option>
                                            <option value="center" {{ ($settings['mrp']['align'] ?? '') == 'center' ? 'selected' : '' }}>Center</option>
                                            <option value="right" {{ ($settings['mrp']['align'] ?? '') == 'right' ? 'selected' : '' }}>Right</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Arabic Price Settings -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Arabic Price Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Font Size (px)</label>
                                        <input type="number" class="form-control" name="mrp_arabic[font_size]" value="{{ $settings['mrp_arabic']['font_size'] ?? 12 }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Alignment</label>
                                        <select class="form-select" name="mrp_arabic[align]">
                                            <option value="left" {{ ($settings['mrp_arabic']['align'] ?? '') == 'left' ? 'selected' : '' }}>Left</option>
                                            <option value="center" {{ ($settings['mrp_arabic']['align'] ?? '') == 'center' ? 'selected' : '' }}>Center</option>
                                            <option value="right" {{ ($settings['mrp_arabic']['align'] ?? '') == 'right' ? 'selected' : '' }}>Right</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </x-app-layout>
