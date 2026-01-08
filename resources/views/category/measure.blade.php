<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tailoring Measurement Module</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="container mx-auto p-6 space-y-8">

  <header class="text-2xl font-bold mb-4">Tailoring Measurement Module</header>

  @if(session('success'))
    <div class="bg-green-100 text-green-800 p-3 rounded">{{ session('success') }}</div>
  @endif

  <section class="bg-white rounded shadow p-5 space-y-4">

    <!-- Customer Selection -->
    <div>
      <label class="block font-medium">Customer</label>
      <select id="customer-select" class="mt-1 w-full border rounded px-3 py-2">
        <option value="">-- Select Customer --</option>
        @foreach($customers as $customer)
          <option value="{{ $customer->id }}">{{ $customer->name }}</option>
        @endforeach
      </select>
    </div>

    <!-- Category Selection -->
    <div>
      <label class="block font-medium">Category</label>
      <select id="category-select" class="mt-1 w-full border rounded px-3 py-2">
        <option value="">-- Select Category --</option>
        @foreach($categories as $category)
          <option value="{{ $category->id }}">{{ $category->name }}</option>
        @endforeach
      </select>
    </div>

    <!-- Measurement Form -->
    <form id="measurement-form" action="{{ route('settings::category::measurements.save') }}" method="POST" class="hidden mt-4 bg-gray-50 p-4 rounded grid grid-cols-1 md:grid-cols-2 gap-3">
      @csrf
      <input type="hidden" name="customer_id" id="form-customer">
      <input type="hidden" name="category_id" id="form-category">
      <input type="hidden" name="template_ids" id="form-templates">
      <div id="fields-container"></div>
      <div class="col-span-2 mt-2">
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Save Measurements</button>
      </div>
    </form>

  </section>

  <!-- Saved Measurements -->
  <section class="bg-white rounded shadow p-5 mt-6">
    <h3 class="text-lg font-semibold mb-2">Saved Measurements</h3>
    <div id="saved-measurements" class="text-sm space-y-2">
      @foreach($savedMeasurements as $measurement)
      <div class="border p-2 rounded bg-gray-50 flex justify-between items-center">
          <span>
              <strong>{{ $measurement->customer?->name ?? 'Unknown Customer' }}</strong> - 
              <strong>{{ $measurement->template->category->name ?? 'Unknown Category' }}</strong> - 
              <strong>{{ $measurement->template?->name ?? 'Unknown Template' }}:</strong>
              @if(!empty($measurement->values) && is_array($measurement->values))
                  @foreach($measurement->values as $field => $value)
                      {{ $field }}: {{ $value }}@if(!$loop->last), @endif
                  @endforeach
              @else
                  No measurements
              @endif
          </span>
      </div>
      @endforeach
    </div>
  </section>

</div>

<script>
const templates = @json($templates);

const categorySelect = document.getElementById('category-select');
const measurementForm = document.getElementById('measurement-form');
const fieldsContainer = document.getElementById('fields-container');
const customerSelect = document.getElementById('customer-select');
const formCustomer = document.getElementById('form-customer');
const formCategory = document.getElementById('form-category');
const templateIdsInput = document.getElementById('form-templates');

categorySelect.addEventListener('change', function() {
    const categoryId = this.value;
    if(!categoryId || !customerSelect.value){
        measurementForm.classList.add('hidden');
        fieldsContainer.innerHTML = '';
        return;
    }

    // Filter templates by selected category
    const categoryTemplates = templates.filter(t => t.category_id == categoryId);

    fieldsContainer.innerHTML = '';
    categoryTemplates.forEach(t => {
        const wrapper = document.createElement('div');
        wrapper.className = 'flex flex-col';

        const label = document.createElement('label');
        label.className = 'font-medium';
        label.innerText = t.name + ' *';

        const input = document.createElement('input');
        input.type = 'text';
        input.name = `values[${t.id}]`; 
        input.placeholder = 'Enter ' + t.name;
        input.className = 'border rounded px-3 py-2 mt-1';

        wrapper.appendChild(label);
        wrapper.appendChild(input);
        fieldsContainer.appendChild(wrapper);
    });

    // Save all template IDs
    templateIdsInput.value = categoryTemplates.map(t => t.id).join(',');

    measurementForm.classList.remove('hidden');
    formCustomer.value = customerSelect.value;
    formCategory.value = categoryId;
});

// Re-render if customer changes
customerSelect.addEventListener('change', () => categorySelect.dispatchEvent(new Event('change')));
</script>

</body>
</html>
