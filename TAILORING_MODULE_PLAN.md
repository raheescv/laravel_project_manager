# Tailoring Module - Implementation Plan

## Overview
This document outlines the complete plan for implementing a **completely independent** Tailoring Order Management System based on the provided HTML designs. The system consists of two main modules:
1. **Tailoring Order Management** - Create, manage, and complete tailoring orders (independent from sales)
   - Order creation with measurements
   - Job completion tracking (part of order workflow)
2. **Measurement Management** - Capture detailed measurements for tailoring items

**IMPORTANT: This is a standalone system with NO connections to sales or sale_items tables. All data is stored in dedicated tailoring tables. Job Completion is part of the order workflow, not a separate module.**

---

## 1. Module Structure

### 1.1 Tailoring Order Module
- Create and manage tailoring orders
- Store orders in `tailoring_orders` table (independent)
- Store items in `tailoring_order_items` table (independent)
- Handle measurements and styling options
- **Job Completion is part of this module** - updates order and items during completion process
- Track completion status, tailor assignments, stock usage, and commissions within the order

---

## 2. Database Architecture

### 2.1 Migration: Create Tailoring Orders Table
**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_tailoring_orders_table.php`

**Fields:**
- `id` (primary key)
- `tenant_id` (foreign key to tenants)
- `order_no` (string) - Custom order number (e.g., "RA3HAWO2404")
- `unique(['tenant_id', 'order_no'])` - Unique order number per tenant
- `branch_id` (unsignedBigInteger, nullable) - Foreign key to branches
- `account_id` (unsignedBigInteger, nullable) - Foreign key to accounts (customer)
- `customer_name` (string, nullable) - Customer name
- `customer_mobile` (string, 15, nullable) - Customer contact/mobile
- `salesman_id` (unsignedBigInteger, nullable) - Foreign key to users table (salesman)
- `order_date` (date) - Order date
- `delivery_date` (date, nullable) - Expected delivery date
- `gross_amount` (decimal(16,2), default: 0) - Total gross amount
- `item_discount` (decimal(16,2), default: 0) - Total item discount
- `tax_amount` (decimal(16,2), default: 0) - Total tax amount
- `total` (decimal(16,2), default: 0) - Total amount
- `other_discount` (decimal(16,2), default: 0) - Additional discount
- `freight` (decimal(16,2), default: 0) - Freight charges
- `round_off` (decimal(10,2), default: 0) - Round off amount
- `grand_total` (decimal(16,2), default: 0) - Grand total
- `paid` (decimal(16,2), default: 0) - Amount paid
- `balance` (decimal(16,2), default: 0) - Balance amount
- `payment_method_ids` (string, nullable) - Payment method IDs (comma separated)
- `payment_method_name` (string, nullable) - Payment method names
- `status` (enum: ['draft', 'pending', 'confirmed', 'in_progress', 'completed', 'delivered', 'cancelled'], default: 'draft')
- `notes` (text, nullable) - General notes for the order

**Job Completion Fields (Part of Order):**
- `rack_id` (unsignedBigInteger, nullable) - Foreign key to racks table
- `cutter_id` (unsignedBigInteger, nullable) - Foreign key to users table (cutter employee)
- `completion_date` (date, nullable) - Date of completion
- `completion_status` (enum: ['pending', 'in_progress', 'completed', 'delivered'], nullable) - Completion status
- `created_by` (foreign key to users)
- `updated_by` (foreign key to users, nullable)
- `deleted_by` (foreign key to users, nullable)
- `softDeletes()` - Soft delete support
- `timestamps()`
- Indexes: `['tenant_id', 'order_no']`, `['tenant_id', 'order_date']`, `['tenant_id', 'status']`, `['tenant_id', 'customer_name']`

### 2.2 Migration: Create Tailoring Order Items Table
**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_tailoring_order_items_table.php`

**Fields:**
- `id` (primary key)
- `tenant_id` (foreign key to tenants)
- `tailoring_order_id` (foreign key to tailoring_orders)
- `item_no` (integer) - Item sequence number

**Category & Model:**
- `tailoring_category_id` (unsignedBigInteger, nullable) - Foreign key to tailoring_categories
- `tailoring_category_model_id` (unsignedBigInteger, nullable) - Foreign key to tailoring_category_models

**Product Information:**
- `product_id` (unsignedBigInteger, nullable) - Foreign key to products (optional)
- `product_name` (string) - Product name/description
- `product_color` (string, nullable) - Product color
- `unit_id` (foreign key to units, default: 1)
- `quantity` (decimal(8,3)) - Item quantity
- `unit_price` (decimal(16,2)) - Unit price/item rate
- `stitch_rate` (decimal(16,2), default: 0) - Stitching rate per item
- `gross_amount` (decimal(16,2)) - Gross amount (unit_price * quantity)
- `discount` (decimal(16,2), default: 0) - Item discount
- `net_amount` (decimal(16,2)) - Net amount (gross_amount - discount)
- `tax` (decimal(16,2), default: 0) - Tax percentage
- `tax_amount` (decimal(16,2)) - Tax amount (calculated)
- `total` (decimal(16,2)) - Total amount (net_amount + tax_amount + stitch_rate)

**Basic Measurements (Left Column):**
- `length` (decimal(8,2), nullable) - Length measurement
- `shoulder` (decimal(8,2), nullable) - Shoulder measurement
- `sleeve` (decimal(8,2), nullable) - Sleeve measurement
- `chest` (decimal(8,2), nullable) - Chest measurement
- `stomach` (string, nullable) - Stomach measurement
- `sl_chest` (decimal(8,2), nullable) - S.L Chest measurement
- `sl_so` (decimal(8,2), nullable) - S.L So measurement
- `neck` (decimal(8,2), nullable) - Neck measurement
- `bottom` (string, nullable) - Bottom measurement
- `mar_size` (string, nullable) - Mar Size
- `mar_model` (string, nullable) - Mar Model

**Cuff Details:**
- `cuff` (string, nullable) - Cuff style
- `cuff_size` (string, nullable) - Cuff size
- `cuff_cloth` (string, nullable) - Cuff Cloth type
- `cuff_model` (string, nullable) - Cuff Model

**Additional Measurements:**
- `neck_d_button` (string, nullable) - Neck D Button
- `side_pt_size` (string, nullable) - Side PT Size

**Collar Details (Right Column):**
- `collar` (string, nullable) - Collar style
- `collar_size` (string, nullable) - Collar size
- `collar_cloth` (string, nullable) - Collar Cloth type
- `collar_model` (string, nullable) - Collar Model

**Additional Styling:**
- `regal_size` (string, nullable) - Regal Size
- `knee_loose` (string, nullable) - Knee Loose measurement
- `fp_down` (string, nullable) - FP Down measurement
- `fp_model` (string, nullable) - FP Model
- `fp_size` (string, nullable) - FP Size
- `pen` (string, nullable) - Pen type
- `side_pt_model` (string, nullable) - Side PT Model
- `stitching` (string, nullable) - Stitching type
- `button` (string, nullable) - Button type
- `button_no` (string, nullable) - Button No
- `mobile_pocket` (enum: ['Yes', 'No'], default: 'No') - Mobile pocket option

**Job Completion Fields (Part of Order Item):**
- `tailor_id` (unsignedBigInteger, nullable) - Foreign key to users table (tailor employee)
- `tailor_commission` (decimal(10,2), default: 0) - Commission per unit
- `tailor_total_commission` (decimal(10,2), default: 0) - Total commission (calculated: tailor_commission * quantity)
- `stock_quantity` (decimal(8,3), default: 0) - Current stock quantity (live data from inventory, shown in frontend only, not stored)
- `used_quantity` (decimal(8,3), default: 0) - Quantity of material used
- `wastage` (decimal(8,3), default: 0) - Wastage amount
- `total_quantity_used` (decimal(8,3), default: 0) - Total qty used (calculated: used_quantity + wastage)
- `stock_balance` (decimal(8,3), default: 0) - Remaining stock after usage (calculated: stock_quantity - total_quantity_used) (live data, shown in frontend only, not stored)
- `item_completion_date` (date, nullable) - Date this item was completed
- `is_selected_for_completion` (boolean, default: false) - Selection checkbox for completion

**Additional:**
- `tailoring_notes` (text, nullable) - Item-specific tailoring notes
- `created_by` (foreign key to users)
- `updated_by` (foreign key to users, nullable)
- `deleted_by` (foreign key to users, nullable)
- `softDeletes()` - Soft delete support
- `timestamps()`
- Indexes: `['tenant_id', 'tailoring_order_id']`, `['tenant_id', 'tailoring_category_id']`

### 2.3 Migration: Create Tailoring Categories Table
**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_tailoring_categories_table.php`

**Fields:**
- `id` (primary key)
- `tenant_id` (foreign key to tenants)
- `name` (string) - Category name: 'Thob', 'Sirwal', 'Vest'
- `description` (text, nullable)
- `is_active` (boolean, default: true)
- `order` (integer, default: 0) - Display order
- `created_at`, `updated_at`
- Unique constraint: `['tenant_id', 'name']`

### 2.4 Migration: Create Tailoring Category Models Table
**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_tailoring_category_models_table.php`

**Fields:**
- `id` (primary key)
- `tenant_id` (foreign key to tenants)
- `tailoring_category_id` (foreign key to tailoring_categories)
- `name` (string) - Model name (e.g., 'KUWAITY' for Thob category)
- `description` (text, nullable)
- `is_active` (boolean, default: true)
- `created_at`, `updated_at`
- Unique constraint: `['tenant_id', 'tailoring_category_id', 'name']`

### 2.5 Migration: Create Tailoring Measurement Options Table
**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_tailoring_measurement_options_table.php`

**Fields:**
- `id` (primary key)
- `tenant_id` (foreign key to tenants)
- `option_type` (enum: 'mar_model', 'cuff', 'cuff_cloth', 'cuff_model', 'collar', 'collar_cloth', 'collar_model', 'fp_model', 'pen', 'side_pt_model', 'stitching', 'button')
- `value` (string)
- `created_at`, `updated_at`
- Index: `['tenant_id', 'option_type']`

### 2.6 Migration: Create Tailoring Payments Table
**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_tailoring_payments_table.php`

**Fields:**
- `id` (primary key)
- `tenant_id` (foreign key to tenants)
- `tailoring_order_id` (foreign key to tailoring_orders)
- `payment_method_id` (unsignedBigInteger) - Foreign key to accounts (payment method)
- `date` (date) - Payment date
- `amount` (decimal(16,2)) - Payment amount
- `created_by` (foreign key to users)
- `updated_by` (foreign key to users, nullable)
- `deleted_by` (foreign key to users, nullable)
- `softDeletes()` - Soft delete support
- `timestamps()`
- Indexes: `['tenant_id', 'tailoring_order_id']`, `['tenant_id', 'date']`, `['tenant_id', 'payment_method_id']`

### 2.7 Migration: Create Racks Table
**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_racks_table.php`

**Fields:**
- `id` (primary key)
- `tenant_id` (foreign key to tenants)
- `name` (string) - Rack name/number
- `description` (text, nullable)
- `is_active` (boolean, default: true)
- `created_at`, `updated_at`
- Unique constraint: `['tenant_id', 'name']`

---

## 3. Backend Architecture

### 3.1 Tailoring Order Action Classes

#### 3.1.1 Create Tailoring Order Action
**File:** `app/Actions/Tailoring/Order/CreateTailoringOrderAction.php`
- Creates a new tailoring order in `tailoring_orders` table
- Handles tailoring-specific data validation
- Generates order number automatically
- Processes tailoring measurements and styling options
- Creates tailoring order items with all tailoring fields
- Creates tailoring payments if provided
- Calculates totals (gross, discount, tax, grand total)
- Calculates paid amount and balance from payments
- Updates order payment_method_ids and payment_method_name
- **NO connection to sales/sale_items tables**

#### 3.1.2 Update Tailoring Order Action
**File:** `app/Actions/Tailoring/Order/UpdateTailoringOrderAction.php`
- Updates existing tailoring order in `tailoring_orders` table
- Validates measurements and styling data
- Updates tailoring order and order items
- Recalculates totals
- **NO connection to sales/sale_items tables**

#### 3.1.3 Get Tailoring Order Action
**File:** `app/Actions/Tailoring/Order/GetTailoringOrderAction.php`
- Retrieves tailoring order from `tailoring_orders` table
- Loads related items, measurements, styling, and product details
- Returns formatted data for Vue components
- **NO connection to sales/sale_items tables**

#### 3.1.4 Add Tailoring Item Action
**File:** `app/Actions/Tailoring/Order/Item/AddTailoringItemAction.php`
- Adds a new item to tailoring order in `tailoring_order_items` table
- Validates tailoring-specific fields (measurements, styling)
- Calculates amounts including stitch rate
- Creates tailoring order item with all data
- **NO connection to sale_items table**

#### 3.1.5 Update Tailoring Item Action
**File:** `app/Actions/Tailoring/Order/Item/UpdateTailoringItemAction.php`
- Updates existing tailoring order item
- Validates and updates measurements/styling
- Recalculates amounts
- **NO connection to sale_items table**

#### 3.1.6 Delete Tailoring Item Action
**File:** `app/Actions/Tailoring/Order/Item/DeleteTailoringItemAction.php`
- Removes item from tailoring order (soft delete)
- Updates order totals
- **NO connection to sale_items table**

### 3.1.7 Tailoring Payment Action Classes

#### 3.1.7.1 Create Tailoring Payment Action
**File:** `app/Actions/Tailoring/Payment/CreateAction.php`
- Creates a new payment record in `tailoring_payments` table
- Validates payment data
- Updates order paid amount and balance
- Updates order payment_method_ids and payment_method_name
- **NO connection to sale_payments table**

#### 3.1.7.2 Update Tailoring Payment Action
**File:** `app/Actions/Tailoring/Payment/UpdateAction.php`
- Updates existing payment record
- Recalculates order paid amount and balance
- Updates order payment_method_ids and payment_method_name
- **NO connection to sale_payments table**

#### 3.1.7.3 Delete Tailoring Payment Action
**File:** `app/Actions/Tailoring/Payment/DeleteAction.php`
- Deletes payment record (soft delete)
- Recalculates order paid amount and balance
- Updates order payment_method_ids and payment_method_name
- **NO connection to sale_payments table**

### 3.2 Tailoring Order Controller

#### 3.2.1 Tailoring Order Controller
**File:** `app/Http/Controllers/Tailoring/OrderController.php`

**Methods:**
- `index()` - List all tailoring orders (delegates to action)
- `create()` - Show create page (returns Inertia view)
- `store()` - Create new tailoring order (delegates to `CreateTailoringOrderAction`)
- `edit($id)` - Show edit page (delegates to action)
- `update($id)` - Update tailoring order (delegates to `UpdateTailoringOrderAction`)
- `show($id)` - View tailoring order details (delegates to `GetTailoringOrderAction`)
- `destroy($id)` - Delete tailoring order (delegates to action)

**API Methods:**
- `getCategories()` - Get all tailoring categories with their models
- `getCategoryModels($categoryId)` - Get models for a specific category
- `addCategoryModel($categoryId, $modelName)` - Add new model to a category
- `getProducts()` - Search products for tailoring (optional)
- `getProductColors()` - Get available colors for a product (optional)
- `getMeasurementOptions()` - Get available options for measurement dropdowns
- `addMeasurementOption()` - Add new option to measurement dropdowns dynamically
- `addItem()` - Add item to order (delegates to `AddTailoringItemAction`)
- `updateItem()` - Update item (delegates to `UpdateTailoringItemAction`)
- `removeItem()` - Remove item (delegates to `DeleteTailoringItemAction`)
- `calculateAmount()` - Calculate item amount (qty * rate + stitch_rate)

**Payment API Methods:**
- `addPayment()` - Add payment to order (delegates to `CreateTailoringPaymentAction`)
- `updatePayment($paymentId)` - Update payment (delegates to `UpdateTailoringPaymentAction`)
- `deletePayment($paymentId)` - Delete payment (delegates to `DeleteTailoringPaymentAction`)
- `getPayments($orderId)` - Get all payments for an order

**Job Completion API Methods (Separate Page):**
- `getOrderByOrderNumber($orderNo)` - Fetch order by order number (delegates to `GetOrderByOrderNumberAction`)
- `searchOrders()` - Search orders by order number, customer, dates, etc.
- `updateCompletion($orderId)` - Update order completion details (delegates to `UpdateOrderCompletionAction`)
- `submitCompletion($orderId)` - Submit order completion (delegates to `SubmitOrderCompletionAction`)
- `getRacks()` - Get available racks
- `getTailors()` - Get available tailors (users with tailor role)
- `getCutters()` - Get available cutters (users with cutter role)
- `getItemStock($itemId)` - Get live stock quantity for an item from inventory
- `calculateStockBalance()` - Calculate stock balance after usage (live calculation)
- `calculateTailorCommission()` - Calculate total commission for tailor
- `updateItemCompletion($itemId)` - Update item completion data

### 3.3 Job Completion Action Classes

#### 3.1.8 Get Order by Order Number Action
**File:** `app/Actions/Tailoring/Order/GetOrderByOrderNumberAction.php`
- Fetches tailoring order by order number
- Loads order with all items and completion data
- Returns formatted data for job completion page
- **Used by separate job completion page**

#### 3.1.9 Update Order Completion Action
**File:** `app/Actions/Tailoring/Order/UpdateOrderCompletionAction.php`
- Updates order completion details (rack, cutter, completion_date, completion_status)
- Updates order items with completion data (tailor, commissions, stock usage)
- Calculates totals and stock balances
- Updates order status based on completion
- **Used by separate job completion page**

#### 3.1.10 Submit Order Completion Action
**File:** `app/Actions/Tailoring/Order/SubmitOrderCompletionAction.php`
- Submits selected items for completion
- Updates stock inventory (if integrated)
- Calculates and records tailor commissions
- Updates order completion status
- Marks items as completed
- **Used by separate job completion page**

### 3.5 Model Updates

#### 3.5.1 Tailoring Order Model
**File:** `app/Models/TailoringOrder.php`
- BelongsTo Tenant, Branch, Account (customer)
- BelongsTo User (salesman, cutter, created_by, updated_by)
- BelongsTo Rack
- HasMany TailoringOrderItem
- HasMany TailoringPayment
- **NO relationship with Sale model**
- Scopes: `draft()`, `pending()`, `confirmed()`, `inProgress()`, `completed()`, `delivered()`, `byDate()`, `byCustomer()`, `byCompletionStatus()`
- Methods: `calculateTotals()`, `updateTotals()`, `generateOrderNo()`, `updateCompletion()`, `submitCompletion()`
- Fillable: All fields from tailoring_orders table including completion fields

#### 3.5.2 Tailoring Order Item Model
**File:** `app/Models/TailoringOrderItem.php`
- BelongsTo Tenant, TailoringOrder
- BelongsTo TailoringCategory, TailoringCategoryModel
- BelongsTo Product (optional), Unit
- BelongsTo User (employee, assistant, tailor)
- **NO relationship with SaleItem model**
- Accessors for formatted measurements display
- Scopes for filtering by category, model, completion status
- Methods: `calculateAmount()`, `calculateTotal()`, `calculateStockBalance()`, `calculateTailorCommission()`, `updateCompletion()`
- Fillable: All fields from tailoring_order_items table including completion fields

#### 3.5.3 Tailoring Category Model
**File:** `app/Models/TailoringCategory.php`
- Manages tailoring categories (Thob, Sirwal, Vest)
- BelongsTo Tenant
- HasMany TailoringCategoryModel
- Scopes for active categories
- Methods to get categories with models

#### 3.5.4 Tailoring Category Model Model
**File:** `app/Models/TailoringCategoryModel.php`
- Manages models for each tailoring category (e.g., KUWAITY for Thob)
- BelongsTo Tenant
- BelongsTo TailoringCategory
- Scopes for active models
- Methods to get models by category

#### 3.5.5 Tailoring Measurement Option Model
**File:** `app/Models/TailoringMeasurementOption.php`
- Manages dynamic measurement options (collar, cuff, etc.)
- BelongsTo Tenant
- Scopes for filtering by option_type
- Methods to get options by type for dropdowns


#### 3.5.8 Tailoring Payment Model
**File:** `app/Models/TailoringPayment.php`
- BelongsTo Tenant, TailoringOrder
- BelongsTo Account (payment_method)
- BelongsTo User (created_by, updated_by)
- **NO relationship with SalePayment model**
- Scopes: `byDate()`, `byPaymentMethod()`, `today()`, `last7Days()`
- Methods: `getNameAttribute()` - returns payment method name
- Fillable: tailoring_order_id, payment_method_id, date, amount, created_by, updated_by

#### 3.5.9 Rack Model
**File:** `app/Models/Rack.php`
- BelongsTo Tenant
- HasMany TailoringOrder (through completion)
- Scopes for active racks

---

## 4. Frontend Architecture (Vue Components)

### 4.1 Tailoring Order Components

#### 4.1.1 Main Page Component
**File:** `resources/js/Pages/Tailoring/Order.vue`
- Main container component
- Manages overall state and form submission
- Coordinates child components

### 4.2 Tailoring Order Component Structure

#### 4.2.1 Category Header Component
**File:** `resources/js/components/Tailoring/CategoryHeader.vue`
**Props:**
- `categories` (Array) - Available tailoring categories
- `selectedCategories` (Array) - Selected category IDs (for filtering)

**Features:**
- Category checkboxes (Thob, Sirwal, Vest) - for filtering/display
- Horizontal layout with divider

#### 4.2.2 Order Header Component
**File:** `resources/js/components/Tailoring/OrderHeader.vue`
**Props:**
- `orderNo` (String)
- `customer` (Object)
- `contact` (String)
- `salesman` (Object)
- `orderDate` (Date)

**Features:**
- Order number display (auto-generated)
- Customer search/select with "Add Customer" link
- Contact number input
- Salesman dropdown
- Order date picker

#### 4.2.3 Category & Model Selection Component
**File:** `resources/js/components/Tailoring/CategoryModelSelector.vue`
**Props:**
- `categories` (Array) - Available tailoring categories
- `selectedCategory` (Object, nullable)
- `selectedModel` (Object, nullable)

**Features:**
- Category selection (Thob, Sirwal, Vest) - checkboxes or dropdown
- Model selection dropdown (populated based on selected category)
- Add new model button for each category
- Displays selected category and model

#### 4.2.4 Measurement Component
**File:** `resources/js/components/Tailoring/MeasurementForm.vue`
**Props:**
- `measurements` (Object)
- `category` (Object) - Selected tailoring category
- `model` (Object) - Selected category model

**Features:**
- Two-column layout matching measurement.html design
- Fields may vary based on selected category (future enhancement)

**Left Column Fields:**
- Thob Model (select with add button)
- Length, Shoulder, Sleeve, Chest, Stomach (inputs)
- S.L Chest, S.L So (inputs)
- Neck, Bottom (inputs)
- Mar Size (input), Mar Model (select with add button)
- Cuff (select with add button), Cuff Size (input)
- Cuff Cloth (select with add button), Cuff Model (select with add button)
- Neck D Button (input), Side PT Size (input)

**Right Column Fields:**
- Collar (select with add button), Collar Size (input)
- Collar Cloth (select with add button), Collar Model (select with add button)
- Regal Size (input), Knee Loose (input)
- FP Down (input), FP Model (select with add button), FP Size (input)
- Pen (select with add button)
- Side PT Model (select with add button), Stitching (select with add button)
- Button (select with add button), Button No (input)
- Mobile Pocket (select: Yes/No), Notes (input)

#### 4.2.5 Product Selection Component
**File:** `resources/js/components/Tailoring/ProductSelection.vue`
**Props:**
- `products` (Array)
- `colors` (Array)

**Features:**
- Product search/select (optional - can work without products table)
- Product color search/select with "ADD COLOR" link
- Quantity input
- Item Rate input
- Stitch Rate input
- Tax input
- Amount (calculated, readonly)
- Add button

#### 4.2.6 Summary Table Component
**File:** `resources/js/components/Tailoring/SummaryTable.vue`
**Props:**
- `items` (Array)

**Features:**
- Displays: Type, Qty, Rate, Amount
- Shows Sub Total
- Shows Total (highlighted)

#### 4.2.7 Work Orders Preview Component
**File:** `resources/js/components/Tailoring/WorkOrdersPreview.vue`
**Props:**
- `workOrders` (Array)

**Features:**
- Table showing: No Type, Item, Qty, Colour, Rate, Stitch Rate, Amount
- Displays all added items with full details

#### 4.2.8 Action Buttons Component
**File:** `resources/js/components/Tailoring/ActionButtons.vue`
**Props:**
- `isLoading` (Boolean)
- `canSubmit` (Boolean)

**Features:**
- Clear button (resets form)
- Create Order button (submits form)
- Payment button (opens payment modal)

#### 4.2.9 Payment Modal Component
**File:** `resources/js/components/Tailoring/PaymentModal.vue`
**Props:**
- `order` (Object) - The tailoring order
- `payments` (Array) - Existing payments
- `paymentMethods` (Array) - Available payment methods

**Features:**
- Display order total and balance
- Add payment form (payment method, amount, date)
- List existing payments
- Edit/delete payments
- Calculate total paid and remaining balance
- Updates order paid amount and balance

### 4.3 Job Completion Components (Separate Page)

#### 4.3.1 Main Job Completion Page
**File:** `resources/js/Pages/Tailoring/JobCompletion.vue`
- Separate page for job completion workflow (not part of order page)
- Fetches order by order number
- Manages completion data for the fetched order
- Coordinates child components

#### 4.3.2 Order Search Component
**File:** `resources/js/components/Tailoring/JobCompletion/OrderSearch.vue`
**Props:**
- `orders` (Array) - Available orders for completion

**Features:**
- Order No search/input field
- Search orders by order number
- Customer search/input (optional filter)
- Contact input (optional filter)
- Order Date and Delivery Date filters (optional)
- Rack filter (optional)
- Clear and Search buttons
- Fetches order by order number from API

#### 4.3.3 Completion Header Component
**File:** `resources/js/components/Tailoring/JobCompletion/CompletionHeader.vue`
**Props:**
- `order` (Object) - The fetched tailoring order
- `racks` (Array)
- `cutters` (Array)

**Features:**
- Displays Order No and Customer name (from fetched order)
- Rack selection dropdown (updates order.rack_id)
- Cutter selection dropdown (updates order.cutter_id)
- Completion date input
- Completion status display

#### 4.3.4 Completion Items Table Component
**File:** `resources/js/components/Tailoring/JobCompletion/CompletionItemsTable.vue`
**Props:**
- `items` (Array) - Order items with completion data
- `tailors` (Array)

**Features:**
- Displays all order items with completion fields
- Readonly fields: No Type, Item, Item Qty, Model, Length, Amount, Stock (live data from inventory), Stock Balance (calculated live), Tot Qty Used
- Editable fields: Tailor Com., Tailor, Used Qty, Wastage, Completion Date
- Calculated fields: Tailor Total Com., Tot Qty Used, Stock Balance (calculated: stock_quantity - total_quantity_used)
- Stock quantity is fetched live from inventory system (not stored in order item)
- Stock balance is calculated live in frontend (not stored)
- Checkbox for selection (is_selected_for_completion)
- Select all checkbox in header
- Real-time calculations on input change
- Updates order items directly (not separate table)

#### 4.3.5 Status Bar Component
**File:** `resources/js/components/Tailoring/JobCompletion/CompletionStatusBar.vue`
**Props:**
- `recordCount` (Number)
- `completionStatus` (String)

**Features:**
- Displays number of records
- Shows completion status
- Shows "No records" when empty

### 4.4 Composables

#### 4.4.1 useTailoringOrder Composable
**File:** `resources/js/composables/useTailoringOrder.js`
- Manages tailoring order state
- Handles form validation
- Calculates totals
- Manages items array
- Handles API calls
- Gets current branch from session/user settings (not from props)
- Fetches live stock data from inventory for items
- **No dependency on sales data**

#### 4.4.2 useTailoringMeasurements Composable
**File:** `resources/js/composables/useTailoringMeasurements.js`
- Manages measurement inputs
- Validates measurement ranges
- Formats measurements for display

#### 4.4.3 useJobCompletion Composable
**File:** `resources/js/composables/useJobCompletion.js`
- Manages job completion state (for separate job completion page)
- Handles order fetching by order number
- Handles completion data updates
- Manages item completion data
- Calculates totals and balances
- Handles API calls for completion
- **Separate page workflow** - fetches order, then updates order and items

#### 4.4.4 useOrderSearch Composable
**File:** `resources/js/composables/useOrderSearch.js`
- Manages order search functionality
- Searches orders by order number, customer, dates, etc.
- Fetches order by order number
- Handles search results

#### 4.4.4 useStockCalculation Composable
**File:** `resources/js/composables/useStockCalculation.js`
- Calculates stock balance after usage
- Handles wastage calculations
- Updates total quantity used

#### 4.4.5 useTailorCommission Composable
**File:** `resources/js/composables/useTailorCommission.js`
- Calculates tailor commission per item
- Calculates total commission
- Handles commission rate updates

---

## 5. Routes

### 5.1 Tailoring Order Web Routes
**File:** `routes/tailoring.php` (new file)

```php
Route::name('tailoring::order::')->prefix('tailoring/order')->controller(OrderController::class)->group(function (): void {
    Route::get('', 'index')->name('index')->can('tailoring.order.view');
    Route::get('create', 'create')->name('create')->can('tailoring.order.create');
    Route::post('', 'store')->name('store')->can('tailoring.order.create');
    Route::get('edit/{id}', 'edit')->name('edit')->can('tailoring.order.edit');
    Route::put('{id}', 'update')->name('update')->can('tailoring.order.edit');
    Route::get('{id}', 'show')->name('show')->can('tailoring.order.view');
    Route::delete('{id}', 'destroy')->name('destroy')->can('tailoring.order.delete');
});
```

### 5.2 Tailoring Order API Routes
**File:** `routes/tailoring.php`

```php
Route::prefix('tailoring/order')->name('api.tailoring.order.')->group(function (): void {
    Route::get('categories', [OrderController::class, 'getCategories'])->name('categories');
    Route::get('category-models/{categoryId}', [OrderController::class, 'getCategoryModels'])->name('category-models');
    Route::post('category-models', [OrderController::class, 'addCategoryModel'])->name('add-category-model');
    Route::get('products', [OrderController::class, 'getProducts'])->name('products'); // Optional
    Route::get('product-colors', [OrderController::class, 'getProductColors'])->name('product-colors'); // Optional
    Route::get('measurement-options', [OrderController::class, 'getMeasurementOptions'])->name('measurement-options');
    Route::post('measurement-options', [OrderController::class, 'addMeasurementOption'])->name('add-measurement-option');
    Route::post('add-item', [OrderController::class, 'addItem'])->name('add-item');
    Route::put('update-item/{id}', [OrderController::class, 'updateItem'])->name('update-item');
    Route::delete('remove-item/{id}', [OrderController::class, 'removeItem'])->name('remove-item');
    Route::post('calculate-amount', [OrderController::class, 'calculateAmount'])->name('calculate-amount');
    Route::post('add-payment', [OrderController::class, 'addPayment'])->name('add-payment');
    Route::put('update-payment/{id}', [OrderController::class, 'updatePayment'])->name('update-payment');
    Route::delete('remove-payment/{id}', [OrderController::class, 'deletePayment'])->name('remove-payment');
    Route::get('{id}/payments', [OrderController::class, 'getPayments'])->name('payments');
});
```

### 5.3 Job Completion Web Routes (Separate Page)
**File:** `routes/tailoring.php`

```php
Route::name('tailoring::job-completion::')->prefix('tailoring/job-completion')->controller(OrderController::class)->group(function (): void {
    Route::get('', 'jobCompletionPage')->name('index')->can('tailoring.job_completion.view');
    Route::get('create', 'jobCompletionPage')->name('create')->can('tailoring.job_completion.create');
});
```

### 5.4 Job Completion API Routes (Separate Page)
**File:** `routes/tailoring.php`

```php
Route::prefix('tailoring/job-completion')->name('api.tailoring.job-completion.')->group(function (): void {
    Route::get('order-by-number/{orderNo}', [OrderController::class, 'getOrderByOrderNumber'])->name('order-by-number');
    Route::post('search-orders', [OrderController::class, 'searchOrders'])->name('search-orders');
    Route::put('{id}/completion', [OrderController::class, 'updateCompletion'])->name('update-completion');
    Route::post('{id}/completion/submit', [OrderController::class, 'submitCompletion'])->name('submit-completion');
    Route::put('item/{itemId}/completion', [OrderController::class, 'updateItemCompletion'])->name('update-item-completion');
    Route::get('racks', [OrderController::class, 'getRacks'])->name('racks');
    Route::get('tailors', [OrderController::class, 'getTailors'])->name('tailors');
    Route::get('cutters', [OrderController::class, 'getCutters'])->name('cutters');
    Route::post('calculate-stock-balance', [OrderController::class, 'calculateStockBalance'])->name('calculate-stock-balance');
    Route::post('calculate-tailor-commission', [OrderController::class, 'calculateTailorCommission'])->name('calculate-tailor-commission');
});
```

---

## 6. Implementation Steps

### Phase 1: Database Setup - Tailoring Orders
1. ✅ Create migration for tailoring_categories table
2. ✅ Create migration for tailoring_category_models table
3. ✅ Create migration for tailoring_measurement_options table
4. ✅ Create migration for tailoring_orders table
5. ✅ Create migration for tailoring_order_items table
6. ✅ Create migration for tailoring_payments table
7. ✅ Run migrations
8. ✅ Create TailoringOrder model
9. ✅ Create TailoringOrderItem model
10. ✅ Create TailoringPayment model
11. ✅ Create TailoringCategory model
12. ✅ Create TailoringCategoryModel model
13. ✅ Create TailoringMeasurementOption model
14. ✅ Seed default categories (Thob, Sirwal, Vest)

### Phase 1B: Database Setup - Completion Fields
1. ✅ Create migration for racks table
2. ✅ Add completion fields to tailoring_orders table (rack_id, cutter_id, completion_date, completion_status)
3. ✅ Add completion fields to tailoring_order_items table (tailor_id, commissions, stock fields, completion_date, is_selected_for_completion)
4. ✅ Run migrations
5. ✅ Create Rack model
6. ✅ Update TailoringOrder model with completion relationships and methods
7. ✅ Update TailoringOrderItem model with completion relationships and methods
8. ✅ Seed default racks (if needed)

### Phase 2: Backend - Tailoring Order Actions
1. ✅ Create `CreateTailoringOrderAction` (works with TailoringOrder model)
2. ✅ Create `UpdateTailoringOrderAction` (works with TailoringOrder model)
3. ✅ Create `GetTailoringOrderAction` (works with TailoringOrder model)
4. ✅ Create `AddTailoringItemAction` (works with TailoringOrderItem model)
5. ✅ Create `UpdateTailoringItemAction` (works with TailoringOrderItem model)
6. ✅ Create `DeleteTailoringItemAction` (works with TailoringOrderItem model)

### Phase 2A: Backend - Tailoring Payment Actions
1. ✅ Create `CreateTailoringPaymentAction` (works with TailoringPayment model)
2. ✅ Create `UpdateTailoringPaymentAction` (works with TailoringPayment model)
3. ✅ Create `DeleteTailoringPaymentAction` (works with TailoringPayment model)

### Phase 2B: Backend - Order Completion Actions
1. ✅ Create `GetOrderByOrderNumberAction` (fetches order by order number for job completion page)
2. ✅ Create `UpdateOrderCompletionAction` (updates order completion data)
3. ✅ Create `SubmitOrderCompletionAction` (submits completion)

### Phase 3: Backend - Tailoring Order Controller
1. ✅ Create `OrderController` (independent, no Sale dependency)
2. ✅ Implement all controller methods (work with TailoringOrder model)
3. ✅ Add API methods for product search, colors, etc. (optional - can work without products)
4. ✅ Add payment API methods (addPayment, updatePayment, deletePayment, getPayments)
5. ✅ Add job completion page method (`jobCompletionPage`) - returns Inertia view for separate page
6. ✅ Add completion API methods (getOrderByOrderNumber, searchOrders, updateCompletion, submitCompletion, updateItemCompletion, getRacks, getTailors, getCutters)

### Phase 4: Frontend - Tailoring Order Components
1. ✅ Create main `Order.vue` page (gets current branch from session/settings)
2. ✅ Create `CategoryHeader.vue` component (shows current branch name, no selection dropdown)
3. ✅ Create `OrderHeader.vue` component (shows current branch, can change via page settings)
4. ✅ Create `CategoryModelSelector.vue` component
5. ✅ Create `MeasurementForm.vue` component (comprehensive two-column layout)
6. ✅ Create `ProductSelection.vue` component
7. ✅ Create `SummaryTable.vue` component
8. ✅ Create `WorkOrdersPreview.vue` component
9. ✅ Create `ActionButtons.vue` component

### Phase 4B: Frontend - Job Completion Page (Separate Page)
1. ✅ Create main `JobCompletion.vue` page (separate page, not in order page)
2. ✅ Create `OrderSearch.vue` component (search/fetch order by order number)
3. ✅ Create `CompletionHeader.vue` component
4. ✅ Create `CompletionItemsTable.vue` component
5. ✅ Create `CompletionStatusBar.vue` component
6. ✅ Implement order fetching by order number

### Phase 5: Frontend - Tailoring Order Composables & Logic
1. ✅ Create `useTailoringOrder.js` composable
2. ✅ Create `useTailoringMeasurements.js` composable
3. ✅ Implement form validation
4. ✅ Implement calculations (amounts, totals)
5. ✅ Implement API integration

### Phase 5B: Frontend - Job Completion Composables & Logic
1. ✅ Create `useJobCompletion.js` composable (for separate job completion page)
2. ✅ Create `useOrderSearch.js` composable (search and fetch orders by order number)
3. ✅ Create `useStockCalculation.js` composable
4. ✅ Create `useTailorCommission.js` composable
5. ✅ Implement calculations (stock balance, commissions)
6. ✅ Implement API integration for order fetching and completion updates

### Phase 6: Routes & Integration
1. ✅ Add web routes
2. ✅ Add API routes
3. ✅ Update navigation menu (if needed)
4. ✅ Add permissions (if needed)

### Phase 7: Styling & UX
1. ✅ Apply Tailwind CSS styling (matching HTML design)
2. ✅ Add responsive design
3. ✅ Add loading states
4. ✅ Add error handling
5. ✅ Add success notifications

### Phase 8: Testing & Refinement
1. ✅ Test order creation
2. ✅ Test item addition/removal
3. ✅ Test calculations
4. ✅ Test form validation
5. ✅ Test responsive design
6. ✅ Fix any bugs

---

## 7. Data Flow

### 7.1 Creating a Tailoring Order
```
User fills form → Vue components collect data → 
useTailoringOrder composable validates → 
API call to OrderController::store() → 
CreateTailoringOrderAction::execute() → 
Creates TailoringOrder in tailoring_orders table → 
Creates TailoringOrderItems in tailoring_order_items table → 
Calculates totals → 
Returns success response → 
Vue shows success message → 
Redirect to order view or list
```

### 7.2 Adding an Item
```
User fills product selection form → 
ProductSelection component emits event → 
useTailoringOrder handles item addition → 
API call to OrderController::addItem() → 
AddTailoringItemAction::execute() → 
Creates TailoringOrderItem in tailoring_order_items table → 
Returns item data → 
Vue updates SummaryTable and WorkOrdersPreview
```

### 7.3 Job Completion Flow (Separate Page)
```
User navigates to Job Completion page → 
Enter order number in search field → 
API call to OrderController::getOrderByOrderNumber() → 
GetOrderByOrderNumberAction::execute() → 
Fetches tailoring order by order number → 
Returns order with items → 
Display order details and items → 
User assigns rack and cutter to order → 
User assigns tailors to items and enters stock usage → 
Calculate commissions and stock balances → 
Select items to complete → 
API call to OrderController::updateCompletion() or submitCompletion() → 
UpdateOrderCompletionAction or SubmitOrderCompletionAction::execute() → 
Updates order completion fields (rack_id, cutter_id, completion_status) → 
Updates order items with completion data (tailor, commissions, stock) → 
Updates stock inventory (if integrated) → 
Returns success response → 
Vue shows success message → 
Order status updated
```

**Note:** Job Completion is a separate page that fetches orders by order number. It updates the tailoring_order and tailoring_order_items tables directly, not separate tables.

---

## 8. Key Features

### 8.1 Order Management
- Auto-generate order numbers (format: RA3HAWO2404)
- Link to existing customer or create new
- Assign salesman
- Set delivery date
- Add general notes

### 8.2 Category & Model System
**Tailoring Categories:**
- **Categories**: Thob, Sirwal, Vest (stored in tailoring_categories table)
- **Models per Category**: Each category has multiple models (e.g., Thob -> KUWAITY, etc.)
- **Model Management**: Add new models dynamically for each category
- **Selection Flow**: Select category first, then select model from that category

### 8.3 Measurements
**Comprehensive Measurement System:**
- **Basic Measurements**: Length, Shoulder, Sleeve, Chest, Stomach, S.L Chest, S.L So, Neck, Bottom
- **Model Selections**: Mar Model, FP Model, Side PT Model (with add new option)
- **Cuff Details**: Cuff, Cuff Size, Cuff Cloth, Cuff Model
- **Collar Details**: Collar, Collar Size, Collar Cloth, Collar Model
- **Additional Measurements**: Mar Size, Neck D Button, Side PT Size, Regal Size, Knee Loose, FP Down, FP Size
- **Styling Options**: Pen, Stitching, Button, Button No
- **Category Selection**: Thob, Sirwal, Vest (checkboxes for filtering)
- **Display measurements** in work order preview

### 8.4 Styling Options
**Comprehensive Styling System:**
- **Collar**: Collar type, Collar Size, Collar Cloth, Collar Model (all with add new option)
- **Cuff**: Cuff type, Cuff Size, Cuff Cloth, Cuff Model (all with add new option)
- **Stitching**: Stitching type selection (with add new option)
- **Button**: Button type and Button No (with add new option)
- **Mobile Pocket**: Yes/No option
- **Additional**: Pen type, Side PT Model
- **Item-specific notes** for special instructions
- **Dynamic options**: All select fields support adding new values on-the-fly

### 8.5 Product Selection
- Search and select products (optional - can work without products table)
- Select product color (with option to add new)
- Enter quantity
- Set item rate
- Set stitch rate (tailoring-specific)
- Calculate amount automatically: (qty * item_rate) + stitch_rate
- Apply tax

### 8.6 Calculations
- Item Amount = (Quantity × Item Rate) + Stitch Rate
- Sub Total = Sum of all item amounts
- Tax Amount = Sum of all item taxes
- Total = Sub Total + Tax Amount
- Grand Total (handled by TailoringOrder model)
- Paid = Sum of all payment amounts
- Balance = Grand Total - Paid

### 8.6A Payment Management
- **Payment Methods**: Uses accounts table for payment methods
- **Multiple Payments**: Order can have multiple payments
- **Payment Tracking**: Each payment stored in tailoring_payments table
- **Auto-calculation**: Paid amount and balance auto-calculated from payments
- **Payment Summary**: Order stores payment_method_ids and payment_method_name for quick reference

### 8.7 Job Completion Features (Separate Page)
**Completion Management (Separate Page Workflow):**
- Navigate to separate Job Completion page
- Search and fetch order by order number
- View completion data for fetched order
- Assign rack and cutter to order (updates order.rack_id, order.cutter_id)
- Set completion date and status on order
- **Separate page, but all data stored in order and order_items tables**

**Item Completion Management:**
- View all order items with completion fields
- Assign tailor to each item (updates item.tailor_id)
- Set tailor commission per item (auto-calculates total)
- Enter stock quantity, used quantity, and wastage for each item
- Auto-calculate total quantity used and stock balance
- Set completion date per item
- Select items for completion (is_selected_for_completion flag)
- All data stored in tailoring_order_items table

**Stock Management:**
- View current stock for each item (fetched live from inventory system, not stored)
- Track used quantity (stored in order item)
- Track wastage (stored in order item)
- Calculate remaining stock balance live (stock_quantity - total_quantity_used) - shown in frontend only
- Stock quantity and stock balance are live data, calculated/displayed in frontend, not stored in database
- Update inventory on submission (if inventory system integrated)

**Commission Management:**
- Set commission rate per item
- Auto-calculate total commission per item (tailor_commission * quantity)
- Track commissions by tailor

---

## 9. Integration Points

### 9.1 Existing Systems Integration
- **Customer Management**: Uses accounts table for customer selection (optional)
- **Product Management**: Uses products table for product search (optional - can work without)
- **User Management**: Uses users table for salesman, employee, tailor, cutter
- **Branch Management**: Uses branches table (optional)
- **Unit Management**: Uses units table for item units
- **Payment System**: Uses tailoring_payments table for payment tracking
- **Inventory**: Can integrate with inventory system for stock tracking (optional)

### 9.2 Permissions
- Reuse existing permission structure or create new:
  - `tailoring.order.view` - View tailoring orders
  - `tailoring.order.create` - Create tailoring orders
  - `tailoring.order.edit` - Edit tailoring orders
  - `tailoring.order.delete` - Delete tailoring orders
  - `tailoring.job_completion.view` - View job completions
  - `tailoring.job_completion.create` - Create job completions
  - `tailoring.job_completion.edit` - Edit job completions
  - `tailoring.job_completion.submit` - Submit job completions

---

## 10. File Structure Summary

```
Backend:
├── app/
│   ├── Actions/
│   │   └── Tailoring/
│   │       ├── Order/
│   │       │   ├── CreateTailoringOrderAction.php
│   │       │   ├── UpdateTailoringOrderAction.php
│   │       │   ├── GetTailoringOrderAction.php
│   │       │   └── Item/
│   │       │       ├── AddTailoringItemAction.php
│   │       │       ├── UpdateTailoringItemAction.php
│   │       │       └── DeleteTailoringItemAction.php
│   │       ├── Order/
│   │       │   ├── GetOrderByOrderNumberAction.php
│   │       │   ├── UpdateOrderCompletionAction.php
│   │       │   └── SubmitOrderCompletionAction.php
│   │       └── Payment/
│   │           ├── CreateAction.php
│   │           ├── UpdateAction.php
│   │           └── DeleteAction.php
│   ├── Http/
│   │   └── Controllers/
│   │       └── Tailoring/
│   │           └── OrderController.php (includes completion methods)
│   └── Models/
│       ├── TailoringOrder.php (new - independent)
│       ├── TailoringOrderItem.php (new - independent)
│       ├── TailoringPayment.php (new - independent)
│       ├── TailoringCategory.php (new)
│       ├── TailoringCategoryModel.php (new)
│       ├── TailoringMeasurementOption.php (new)
│       └── Rack.php (new)
│
├── database/
│   ├── migrations/
│   │   ├── YYYY_MM_DD_HHMMSS_create_tailoring_categories_table.php
│   │   ├── YYYY_MM_DD_HHMMSS_create_tailoring_category_models_table.php
│   │   ├── YYYY_MM_DD_HHMMSS_create_tailoring_measurement_options_table.php
│   │   ├── YYYY_MM_DD_HHMMSS_create_racks_table.php
│   │   ├── YYYY_MM_DD_HHMMSS_create_tailoring_orders_table.php (new - independent)
│   │   ├── YYYY_MM_DD_HHMMSS_create_tailoring_order_items_table.php (new - independent)
│   │   ├── YYYY_MM_DD_HHMMSS_create_tailoring_payments_table.php (new - independent)
│   │   ├── YYYY_MM_DD_HHMMSS_add_completion_fields_to_tailoring_orders_table.php
│   │   └── YYYY_MM_DD_HHMMSS_add_completion_fields_to_tailoring_order_items_table.php
│   └── seeders/
│       ├── TailoringCategorySeeder.php (new - seeds Thob, Sirwal, Vest)
│       └── RackSeeder.php (new - seeds default racks if needed)

Frontend:
├── resources/
│   ├── js/
│   │   ├── Pages/
│   │   │   └── Tailoring/
│   │   │       ├── Order.vue
│   │   │       └── JobCompletion.vue (separate page)
│   │   │       └── components/
│   │   │           ├── CategoryHeader.vue
│   │   │           ├── OrderHeader.vue
│   │   │           ├── CategoryModelSelector.vue
│   │   │           ├── MeasurementForm.vue
│   │   │           ├── ProductSelection.vue
│   │   │           ├── SummaryTable.vue
│   │   │           ├── WorkOrdersPreview.vue
│   │   │           ├── ActionButtons.vue
│   │   │           ├── PaymentModal.vue
│   │   │           └── JobCompletion/
│   │   │               ├── OrderSearch.vue
│   │   │               ├── CompletionHeader.vue
│   │   │               ├── CompletionItemsTable.vue
│   │   │               └── CompletionStatusBar.vue
customer│   │   └── composables/
│   │       ├── useTailoringOrder.js
│   │       ├── useTailoringMeasurements.js
│   │       ├── useJobCompletion.js (for separate job completion page)
│   │       ├── useOrderSearch.js (search and fetch orders by order number)
│   │       ├── useStockCalculation.js
│   │       └── useTailorCommission.js

└── routes/
    └── tailoring.php (new - for all tailoring routes)
```

---

## 11. Notes

### 11.1 Complete Independence
- **NO connection to sales/sale_items**: This is a completely standalone system
- **All data self-contained**: All tailoring data stored in dedicated tables
- **Job Completion is part of order**: Completion data stored in tailoring_orders and tailoring_order_items tables, not separate tables
- **Optional integrations**: Can optionally use products, accounts, branches, but not required
- **Flexible architecture**: Can work with or without other modules

### 11.2 Category & Model Structure
- **Categories** (Thob, Sirwal, Vest) are stored in `tailoring_categories` table
- **Models** (e.g., KUWAITY for Thob) are stored in `tailoring_category_models` table
- Each order item links to both `tailoring_category_id` and `tailoring_category_model_id`
- Users select category first, then model from that category
- Models can be added dynamically for each category

### 11.3 Development Guidelines
- Follow existing code patterns and conventions
- Maintain consistency with existing Vue component structure
- Use Inertia.js for page navigation
- Follow the Action class pattern for business logic
- Category and model selection should be intuitive and user-friendly
- **IMPORTANT**: This is a completely standalone system - no dependencies on sales/sale_items
- All data is self-contained in tailoring-specific tables
- **Job Completion is a separate page** - fetches order by order number, updates order and items
- **Branch Management**: Branch is selected by default from session/user settings, shown in order (readonly), can be changed via order page settings
- **Stock Data**: Stock quantity and stock balance are live data (fetched from inventory, calculated in frontend), not stored in order items
- Can optionally integrate with other systems (products, inventory, payments) but not required

---

**End of Planning Document**
