<div>
    @push('styles')
        {{-- <link rel="stylesheet" href="{{ asset('assets/pos/pos.css?v=3') }}"> --}}
        <style>
            :root {
                --primary-color: #4f46e5;
                --primary-hover: #4338ca;
                --secondary-color: #64748b;
                --success-color: #22c55e;
                --danger-color: #ef4444;
                --warning-color: #f59e0b;
                --light-bg: #f8fafc;
                --border-color: #e2e8f0;
                --card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            }

            .main-wrapper {
                background: var(--light-bg);
                min-height: 100vh;
            }

            .pos-wrapper {
                gap: 1.5rem;
                padding: 1.5rem;
            }

            /* Category Sidebar Styles */
            .category-sidebar {
                background: white;
                border-radius: 0.75rem;
                box-shadow: var(--card-shadow);
                padding: 0.75rem;
                height: 100%;
                display: flex;
                flex-direction: column;
                position: relative;
            }

            .category-sidebar-header {
                padding: 0.5rem;
                margin-bottom: 0.5rem;
                border-bottom: 1px solid var(--border-color);
            }

            .category-sidebar-content {
                flex: 1;
                overflow-y: auto;
                overflow-x: hidden;
                padding-right: 0.25rem;
                margin-right: -0.25rem;
            }

            .category-button {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0.625rem 0.75rem;
                margin: 0.25rem 0;
                border-radius: 0.5rem;
                background: white;
                border: 1px solid var(--border-color);
                cursor: pointer;
                transition: all 0.2s ease;
                font-weight: 500;
                font-size: 0.875rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .category-button i {
                font-size: 0.875rem;
                margin-right: 0.5rem;
                flex-shrink: 0;
            }

            .category-button span {
                flex: 1;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .category-button .badge {
                background: #e2e8f0;
                color: #475569;
                padding: 0.125rem 0.5rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 600;
                margin-left: 0.5rem;
                flex-shrink: 0;
            }

            .category-button:hover {
                background: #f1f5f9;
                transform: translateX(4px);
                border-color: var(--primary-color);
            }

            .category-button.active {
                background: var(--primary-color);
                color: white;
                border-color: var(--primary-color);
            }

            .category-button.active-favorite {
                background: var(--warning-color);
                color: white;
                border-color: var(--warning-color);
            }

            .category-button.active .badge {
                background: rgba(255, 255, 255, 0.2);
                color: white;
            }

            /* Custom Scrollbar for Category Sidebar */
            .category-sidebar-content::-webkit-scrollbar {
                width: 4px;
            }

            .category-sidebar-content::-webkit-scrollbar-track {
                background: #f1f5f9;
                border-radius: 2px;
            }

            .category-sidebar-content::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 2px;
            }

            .category-sidebar-content::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }

            /* Responsive Category Sidebar */
            @media (max-width: 1199.98px) {
                .category-sidebar {
                    height: auto;
                    max-height: 300px;
                }
            }

            @media (max-width: 991.98px) {
                .category-sidebar {
                    max-height: 250px;
                }

                .category-button {
                    padding: 0.5rem 0.625rem;
                    font-size: 0.8125rem;
                }

                .category-button i {
                    font-size: 0.8125rem;
                    margin-right: 0.375rem;
                }

                .category-button .badge {
                    padding: 0.125rem 0.375rem;
                    font-size: 0.6875rem;
                }
            }

            @media (max-width: 767.98px) {
                .category-sidebar {
                    max-height: 200px;
                    margin-bottom: 0.75rem;
                }

                .category-button {
                    padding: 0.5rem;
                }

                .category-button:hover {
                    transform: translateX(2px);
                }
            }

            @media (max-width: 575.98px) {
                .category-sidebar {
                    max-height: 180px;
                }

                .category-button {
                    padding: 0.375rem 0.5rem;
                    font-size: 0.75rem;
                }

                .category-button i {
                    font-size: 0.75rem;
                    margin-right: 0.25rem;
                }
            }

            /* Product Grid Styles */
            .pos-products {
                background: white;
                border-radius: 1rem;
                box-shadow: var(--card-shadow);
                padding: 1.5rem;
                height: 100%;
            }

            .sales-header {
                background: #f1f5f9;
                border-radius: 0.5rem;
                padding: 0.75rem;
                margin-bottom: 0.75rem;
            }

            .sales-header .form-label {
                color: #1e293b;
                font-size: 0.75rem;
                margin-bottom: 0.25rem;
            }

            .sales-header .form-label i {
                font-size: 0.875rem;
            }

            .sales-header .select2-container--default .select2-selection--single {
                height: 2.25rem;
                border: 2px solid transparent;
                border-radius: 0.5rem;
                background: white;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                transition: all 0.2s ease;
            }

            .sales-header .select2-container--default .select2-selection--single:focus,
            .sales-header .select2-container--default.select2-container--open .select2-selection--single {
                border-color: #0ea5e9;
                background: #f0f9ff;
            }

            .sales-header .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 2.25rem;
                padding-left: 0.75rem;
                font-size: 0.875rem;
                color: #1e293b;
            }

            .sales-header .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 2.25rem;
                width: 2rem;
            }

            .sales-header .select2-container--default .select2-selection--single .select2-selection__placeholder {
                color: #94a3b8;
            }

            .sales-header .form-select {
                height: 2.25rem;
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
                border: 2px solid transparent;
                border-radius: 0.5rem;
                background: white;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                transition: all 0.2s ease;
            }

            .sales-header .form-select:focus {
                border-color: #0ea5e9;
                background: #f0f9ff;
                box-shadow: none;
            }

            .search-section {
                margin-bottom: 1.5rem;
            }

            .search-box .input-group {
                box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
                border-radius: 0.75rem;
                overflow: hidden;
            }

            .search-box .input-group-text {
                background: white;
                border: 1px solid var(--border-color);
                color: var(--secondary-color);
            }

            .search-box .form-control {
                border: 1px solid var(--border-color);
                padding: 0.75rem 1rem;
            }

            .search-box .form-control:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
            }

            /* Cart Styles */
            .product-order-list {
                background: white;
                border-radius: 1rem;
                box-shadow: var(--card-shadow);
                height: 100%;
                display: flex;
                flex-direction: column;
            }

            .cart-summary {
                padding: 0.75rem;
                border-bottom: 1px solid var(--border-color);
            }

            .cart-badge {
                background: var(--primary-color);
                color: white;
                width: 2rem;
                height: 2rem;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                font-size: 0.875rem;
            }

            .cart-summary h6 {
                font-size: 0.875rem;
                margin-bottom: 0;
                line-height: 1.25;
            }

            .cart-summary small {
                font-size: 0.75rem;
            }

            .cart-summary .action-group {
                gap: 0.5rem;
            }

            .cart-summary .action-btn {
                width: 2rem;
                height: 2rem;
                font-size: 0.875rem;
            }

            .cart-summary .action-group small {
                font-size: 0.625rem;
                margin-top: 0.125rem;
            }

            .action-btn {
                width: 2.5rem;
                height: 2.5rem;
                border-radius: 0.75rem;
                display: flex;
                align-items: center;
                justify-content: center;
                border: none;
                background: #f1f5f9;
                color: var(--secondary-color);
                transition: all 0.2s ease;
            }

            .action-btn:hover {
                background: var(--primary-color);
                color: white;
                transform: translateY(-2px);
            }

            .action-btn.delete-btn:hover {
                background: var(--danger-color);
            }

            .product-list {
                padding: 1rem 1.5rem;
                border-bottom: 1px solid var(--border-color);
                transition: all 0.2s ease;
            }

            .product-list:hover {
                background: #f8fafc;
            }

            .product-info h6 {
                font-weight: 600;
                margin-bottom: 0.25rem;
            }

            .qty-item {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .qty-item .form-control {
                width: 3rem;
                text-align: center;
                border-radius: 0.5rem;
                border: 1px solid var(--border-color);
                padding: 0.25rem;
            }

            .qty-item i {
                color: var(--secondary-color);
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .qty-item i:hover {
                color: var(--primary-color);
                transform: scale(1.1);
            }

            /* Payment Section Styles */
            .payment-method {
                padding: 1.5rem;
                border-top: 1px solid var(--border-color);
            }

            .payment-method h6 {
                font-weight: 600;
                margin-bottom: 1.5rem;
            }

            .methods .item {
                margin-bottom: 1rem;
            }

            .default-cover a {
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 1rem;
                border-radius: 1rem;
                border: 2px solid var(--border-color);
                transition: all 0.2s ease;
                text-decoration: none;
                color: var(--secondary-color);
            }

            .default-cover a:hover,
            .default-cover a.active {
                border-color: var(--primary-color);
                background: #f8fafc;
                transform: translateY(-2px);
            }

            .default-cover img {
                width: 2.5rem;
                height: 2.5rem;
                margin-bottom: 0.5rem;
            }

            .default-cover span {
                font-weight: 500;
            }

            /* Action Buttons */
            .action-buttons {
                padding: 1.5rem;
                background: white;
                border-top: 1px solid var(--border-color);
                margin-top: auto;
            }

            .btn {
                padding: 0.75rem 1.5rem;
                border-radius: 0.75rem;
                font-weight: 500;
                transition: all 0.2s ease;
            }

            .btn-primary {
                background: var(--primary-color);
                border-color: var(--primary-color);
            }

            .btn-primary:hover {
                background: var(--primary-hover);
                border-color: var(--primary-hover);
                transform: translateY(-2px);
            }

            .btn-secondary {
                background: var(--secondary-color);
                border-color: var(--secondary-color);
            }

            .btn-secondary:hover {
                background: #475569;
                border-color: #475569;
                transform: translateY(-2px);
            }

            /* Form Elements */
            .form-select,
            .form-control {
                border-radius: 0.75rem;
                border: 1px solid var(--border-color);
                padding: 0.75rem 1rem;
            }

            .form-select:focus,
            .form-control:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
            }

            /* Custom Scrollbar */
            ::-webkit-scrollbar {
                width: 6px;
            }

            ::-webkit-scrollbar-track {
                background: #f1f5f9;
            }

            ::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 3px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }

            /* Animations */
            .hover-scale {
                transition: transform 0.2s ease;
            }

            .hover-scale:hover {
                transform: scale(1.02);
            }

            /* Responsive Adjustments */
            @media (max-width: 991.98px) {
                .pos-wrapper {
                    padding: 1rem;
                }

                .category-sidebar,
                .pos-products,
                .product-order-list {
                    margin-bottom: 1rem;
                }
            }

            /* Product Wrap Styles */
            .product-wrap {
                max-height: 40vh !important;
                overflow-y: auto;
                padding: 0.25rem;
                background: #f8fafc;
                border-radius: 0.5rem;
                margin: 0.25rem;
            }

            .product-list {
                display: grid;
                grid-template-columns: 1fr auto auto;
                gap: 0.5rem;
                align-items: center;
                padding: 0.5rem;
                margin-bottom: 0.25rem;
                background: white;
                border-radius: 0.5rem;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
                transition: all 0.2s ease;
                border: 1px solid var(--border-color);
            }

            .product-list:hover {
                transform: translateY(-1px);
                box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
                border-color: var(--primary-color);
            }

            .product-info {
                min-width: 0;
                padding-right: 0.5rem;
            }

            .product-info h6 {
                font-weight: 600;
                margin-bottom: 0.125rem;
                color: #1e293b;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                font-size: 0.875rem;
                line-height: 1.25;
            }

            .product-info p {
                color: var(--primary-color);
                font-weight: 500;
                font-size: 0.875rem;
                margin: 0;
                line-height: 1.25;
            }

            .qty-item {
                display: flex;
                align-items: center;
                gap: 0.375rem;
                background: #f8fafc;
                padding: 0.25rem;
                border-radius: 0.375rem;
                border: 1px solid var(--border-color);
            }

            .qty-item .form-control {
                width: 2.5rem;
                text-align: center;
                border-radius: 0.25rem;
                border: 1px solid var(--border-color);
                padding: 0.25rem;
                font-weight: 500;
                background: white;
                font-size: 0.875rem;
                height: auto;
            }

            .qty-item i {
                color: var(--secondary-color);
                cursor: pointer;
                transition: all 0.2s ease;
                font-size: 1rem;
                width: 1.5rem;
                height: 1.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 0.25rem;
                background: white;
                border: 1px solid var(--border-color);
            }

            .qty-item i:hover {
                color: var(--primary-color);
                background: var(--primary-color);
                color: white;
                border-color: var(--primary-color);
                transform: scale(1.05);
            }

            .product-list .action-group {
                display: flex;
                gap: 0.25rem;
            }

            .product-list .action-btn {
                width: 1.75rem;
                height: 1.75rem;
                border-radius: 0.375rem;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 1px solid var(--border-color);
                background: white;
                color: var(--secondary-color);
                transition: all 0.2s ease;
                font-size: 0.75rem;
            }

            .product-list .action-btn:hover {
                background: var(--primary-color);
                color: white;
                border-color: var(--primary-color);
                transform: translateY(-1px);
            }

            .product-list .action-btn.delete-btn:hover {
                background: var(--danger-color);
                border-color: var(--danger-color);
            }

            /* Empty State */
            .product-wrap:empty::after {
                content: 'No items in cart';
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100%;
                color: var(--secondary-color);
                font-size: 0.875rem;
                font-weight: 500;
            }

            /* Scrollbar for product wrap */
            .product-wrap::-webkit-scrollbar {
                width: 6px;
            }

            .product-wrap::-webkit-scrollbar-track {
                background: #f1f5f9;
                border-radius: 3px;
            }

            .product-wrap::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 3px;
            }

            .product-wrap::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }

            /* Customer Info Section */
            .customer-info-section {
                background: white;
                border-radius: 0.75rem;
                padding: 1rem;
                margin: 0.5rem;
                box-shadow: var(--card-shadow);
                border: 1px solid var(--border-color);
            }

            .customer-info-section .form-label {
                color: #1e293b;
                font-size: 0.875rem;
                margin-bottom: 0.25rem;
                display: flex;
                align-items: center;
                gap: 0.375rem;
            }

            .customer-info-section .form-label i {
                color: var(--primary-color);
                font-size: 1rem;
            }

            .customer-info-section .input-group {
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
                border-radius: 0.5rem;
                overflow: hidden;
            }

            .customer-info-section .input-group-text {
                background: white;
                border: 1px solid var(--border-color);
                color: var(--secondary-color);
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
            }

            .customer-info-section .form-control {
                border: 1px solid var(--border-color);
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
                height: auto;
            }

            .customer-info-section .btn-light {
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
            }

            /* Order Total */
            .order-total {
                background: white;
                border-radius: 0.75rem;
                padding: 1rem;
                margin: 0.5rem;
                box-shadow: var(--card-shadow);
                border: 1px solid var(--border-color);
            }

            .order-total .table td {
                padding: 0.5rem 0;
                font-size: 0.875rem;
                color: #475569;
                vertical-align: middle;
            }

            .order-total .table tr:last-child td {
                padding-top: 0.75rem;
                font-size: 1rem;
                color: #1e293b;
            }

            .order-total .table tr.border-top {
                border-top: 1px solid var(--border-color) !important;
            }

            .order-total .table i {
                font-size: 0.875rem;
                width: 1.25rem;
                text-align: center;
            }

            /* Payment Method */
            .payment-method {
                background: white;
                border-radius: 0.75rem;
                padding: 1rem;
                margin: 0.5rem;
                box-shadow: var(--card-shadow);
                border: 1px solid var(--border-color);
            }

            .payment-method h6 {
                color: #1e293b;
                font-size: 1rem;
                font-weight: 600;
                margin-bottom: 1rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .payment-method .form-check {
                margin: 0;
                display: flex;
                align-items: center;
                gap: 0.375rem;
            }

            .payment-method .form-check-input {
                width: 1rem;
                height: 1rem;
                margin: 0;
                border: 1.5px solid var(--border-color);
            }

            .payment-method .form-check-label {
                color: #475569;
                font-size: 0.875rem;
            }

            .payment-method .methods {
                margin-top: 0.75rem;
            }

            .payment-method .default-cover {
                margin-bottom: 0.75rem;
            }

            .payment-method .default-cover a {
                padding: 0.75rem;
                border-radius: 0.75rem;
                border: 1px solid var(--border-color);
            }

            .payment-method .default-cover img {
                width: 2rem;
                height: 2rem;
                margin-bottom: 0.5rem;
            }

            .payment-method .default-cover span {
                font-size: 0.875rem;
            }

            /* Row Spacing */
            .row.g-3 {
                --bs-gutter-y: 0.75rem;
            }

            /* Compact Form Groups */
            .form-group {
                margin-bottom: 0.75rem;
            }

            .form-group:last-child {
                margin-bottom: 0;
            }

            /* Responsive Styles */
            @media (max-width: 1199.98px) {
                .pos-wrapper {
                    padding: 0.75rem;
                }

                .category-sidebar {
                    height: auto;
                    max-height: 300px;
                    margin-bottom: 1rem;
                }

                .pos-products {
                    height: auto;
                    margin-bottom: 1rem;
                }

                .product-order-list {
                    height: auto;
                }

                .tabs_container {
                    height: auto !important;
                    max-height: 400px;
                }
            }

            @media (max-width: 991.98px) {
                .main-wrapper {
                    padding: 0.5rem;
                }

                .pos-wrapper {
                    gap: 0.75rem;
                }

                .category-sidebar {
                    max-height: 250px;
                }

                .search-section .d-flex {
                    flex-wrap: wrap;
                }

                .search-section .search-box {
                    flex: 1 1 100%;
                    margin-bottom: 0.5rem;
                }

                .search-section .btn {
                    flex: 1 1 auto;
                }

                .product-wrap {
                    max-height: 400px;
                }

                .product-list {
                    grid-template-columns: 1fr auto;
                }

                .product-list .action-group {
                    position: absolute;
                    right: 0.5rem;
                    top: 0.5rem;
                }

                .product-info {
                    padding-right: 3rem;
                }

                .customer-info-section,
                .order-total,
                .payment-method {
                    margin: 0.5rem 0;
                }

                .payment-method .methods {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 0.5rem;
                }

                .payment-method .default-cover {
                    margin-bottom: 0;
                }

                .action-buttons .d-flex {
                    flex-wrap: wrap;
                }

                .action-buttons .btn {
                    flex: 1 1 100%;
                    margin-bottom: 0.5rem;
                }

                .action-buttons .btn:last-child {
                    margin-bottom: 0;
                }
            }

            @media (max-width: 767.98px) {
                .main-wrapper {
                    padding: 0.25rem;
                }

                .pos-wrapper {
                    padding: 0.5rem;
                }

                .category-sidebar {
                    max-height: 200px;
                }

                .sales-header .row {
                    flex-direction: column;
                }

                .sales-header .col-lg-8,
                .sales-header .col-lg-4 {
                    width: 100%;
                    margin-bottom: 0.5rem;
                }

                .product-list {
                    grid-template-columns: 1fr;
                    gap: 0.5rem;
                    padding: 0.75rem;
                }

                .product-info {
                    padding-right: 0;
                    margin-bottom: 0.5rem;
                }

                .qty-item {
                    justify-content: flex-start;
                }

                .product-list .action-group {
                    position: static;
                    justify-content: flex-end;
                    margin-top: 0.5rem;
                }

                .cart-summary {
                    padding: 0.5rem;
                }

                .cart-summary .action-group {
                    flex-wrap: wrap;
                    justify-content: flex-end;
                }

                .cart-summary .action-group>div {
                    margin-bottom: 0.25rem;
                }

                .customer-info-section .row {
                    flex-direction: column;
                }

                .customer-info-section .col-md-6 {
                    width: 100%;
                    margin-bottom: 0.5rem;
                }

                .payment-method .methods {
                    grid-template-columns: repeat(2, 1fr);
                }

                .payment-method h6 {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 0.5rem;
                }

                .payment-method .form-check {
                    width: 100%;
                }
            }

            @media (max-width: 575.98px) {
                .pos-wrapper {
                    padding: 0.25rem;
                }

                .category-sidebar {
                    max-height: 180px;
                }

                .search-section .btn {
                    width: 100%;
                }

                .product-wrap {
                    max-height: 350px;
                }

                .product-list {
                    padding: 0.5rem;
                }

                .qty-item {
                    width: 100%;
                    justify-content: space-between;
                }

                .qty-item .form-control {
                    width: 3rem;
                }

                .payment-method .methods {
                    grid-template-columns: 1fr;
                }

                .action-buttons .d-flex {
                    flex-direction: column;
                }

                .action-buttons .btn {
                    width: 100%;
                }

                .action-buttons .btn-outline-primary {
                    margin-bottom: 0.5rem;
                }
            }

            /* Additional Responsive Utilities */
            .d-flex-responsive {
                display: flex;
            }

            @media (max-width: 767.98px) {
                .d-flex-responsive {
                    flex-direction: column;
                }

                .w-100-mobile {
                    width: 100% !important;
                }

                .text-center-mobile {
                    text-align: center !important;
                }

                .mb-2-mobile {
                    margin-bottom: 0.5rem !important;
                }
            }

            /* Fix for Select2/TomSelect on Mobile */
            @media (max-width: 767.98px) {

                .select2-container,
                .ts-wrapper {
                    width: 100% !important;
                }

                .select2-container--default .select2-selection--single,
                .ts-control {
                    height: auto !important;
                    min-height: 38px;
                }
            }

            /* Touch-friendly adjustments */
            @media (hover: none) {

                .action-btn,
                .btn,
                .category-button,
                .default-cover a {
                    transition: none;
                }

                .action-btn:active,
                .btn:active,
                .category-button:active,
                .default-cover a:active {
                    transform: scale(0.98);
                }

                .qty-item i {
                    padding: 0.25rem;
                }
            }

            /* Category Navigation Styles */
            .category-nav {
                background: white;
                border-radius: 0.75rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                display: flex;
                flex-direction: column;
                height: 100%;
                border: 1px solid var(--border-color);
            }

            .category-nav-header {
                flex-shrink: 0;
                padding: 0.75rem 1rem;
                background: linear-gradient(135deg, var(--primary-color), #6366f1);
                color: white;
                border-radius: 0.75rem 0.75rem 0 0;
                display: flex;
                align-items: center;
                justify-content: space-between;
                position: relative;
                overflow: hidden;
            }

            .category-nav-header::after {
                content: '';
                position: absolute;
                top: 0;
                right: 0;
                width: 100px;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1));
            }

            .category-nav-header h6 {
                margin: 0;
                font-size: 0.875rem;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                position: relative;
                z-index: 1;
            }

            .category-nav-header i {
                font-size: 1rem;
                opacity: 0.9;
            }

            .category-nav-content {
                flex: 1;
                overflow-y: auto;
                overflow-x: hidden;
                padding: 0.5rem;
                background: #f8fafc;
            }

            .category-nav-item {
                display: flex;
                align-items: center;
                padding: 0.5rem 0.75rem;
                margin: 0.25rem 0;
                border-radius: 0.5rem;
                background: white;
                border: 1px solid var(--border-color);
                cursor: pointer;
                transition: all 0.2s ease;
                font-size: 0.8125rem;
                color: #475569;
                position: relative;
                overflow: hidden;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            }

            .category-nav-item::before {
                content: '';
                position: absolute;
                left: 0;
                top: 0;
                height: 100%;
                width: 3px;
                background: var(--primary-color);
                opacity: 0;
                transition: opacity 0.2s ease;
            }

            .category-nav-item:hover {
                background: white;
                border-color: var(--primary-color);
                transform: translateX(3px);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            .category-nav-item:hover::before {
                opacity: 1;
            }

            .category-nav-item.active {
                background: var(--primary-color);
                color: white;
                border-color: var(--primary-color);
                box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
            }

            .category-nav-item.active-favorite {
                background: var(--warning-color);
                color: white;
                border-color: var(--warning-color);
                box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);
            }

            .category-nav-item i {
                font-size: 0.875rem;
                width: 1.25rem;
                text-align: center;
                margin-right: 0.5rem;
                opacity: 0.9;
                transition: transform 0.2s ease;
            }

            .category-nav-item:hover i {
                transform: scale(1.1);
            }

            .category-nav-item span {
                flex: 1;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                font-weight: 500;
            }

            .category-nav-item .badge {
                background: rgba(255, 255, 255, 0.15);
                color: inherit;
                padding: 0.125rem 0.5rem;
                border-radius: 9999px;
                font-size: 0.6875rem;
                font-weight: 600;
                margin-left: 0.5rem;
                min-width: 1.5rem;
                text-align: center;
                backdrop-filter: blur(4px);
            }

            .category-nav-item:not(.active):not(.active-favorite) .badge {
                background: #e2e8f0;
                color: #475569;
            }

            /* Custom Scrollbar for Category Navigation */
            .category-nav-content::-webkit-scrollbar {
                width: 4px;
            }

            .category-nav-content::-webkit-scrollbar-track {
                background: transparent;
                margin: 0.25rem 0;
            }

            .category-nav-content::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 2px;
                border: 1px solid rgba(255, 255, 255, 0.5);
            }

            .category-nav-content::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }

            /* Responsive Styles */
            @media (max-width: 1199.98px) {
                .col-12.col-lg-2 {
                    height: auto;
                    min-height: 400px;
                }

                .category-nav-item {
                    padding: 0.4375rem 0.625rem;
                }
            }

            @media (max-width: 991.98px) {
                .col-12.col-lg-2 {
                    min-height: 350px;
                }

                .category-nav-header {
                    padding: 0.625rem 0.875rem;
                }

                .category-nav-content {
                    padding: 0.375rem;
                }
            }

            @media (max-width: 767.98px) {
                .col-12.col-lg-2 {
                    min-height: 300px;
                }

                .category-nav-item {
                    padding: 0.375rem 0.5rem;
                    margin: 0.1875rem 0;
                }
            }

            @media (max-width: 575.98px) {
                .col-12.col-lg-2 {
                    min-height: 250px;
                }

                .category-nav-header h6 {
                    font-size: 0.8125rem;
                }

                .category-nav-item {
                    font-size: 0.75rem;
                }
            }

            /* Add these styles to your existing styles section */
            .list-group-item {
                transition: all 0.2s ease;
                border-left: 3px solid transparent !important;
            }

            .list-group-item:hover:not(.active) {
                background-color: #f8fafc;
                border-left-color: var(--primary-color) !important;
                transform: translateX(2px);
            }

            .list-group-item.active {
                border-left-color: transparent !important;
            }

            .list-group-item .badge {
                font-size: 0.75rem;
                font-weight: 500;
                min-width: 1.5rem;
            }

            @media (max-width: 991.98px) {
                .col-12.col-lg-2 {
                    margin-bottom: 1rem;
                }

                .card {
                    height: auto !important;
                    max-height: 50vh;
                }

                .list-group {
                    max-height: 50vh !important;
                }
            }

            @media (max-width: 767.98px) {
                .card {
                    max-height: 50vh;
                }

                .list-group {
                    max-height: 50vh !important;
                }

                .list-group-item {
                    padding: 0.5rem 0.75rem !important;
                }
            }

            @media (max-width: 575.98px) {
                .card {
                    max-height: 50vh;
                }

                .list-group {
                    max-height: 50vh !important;
                }

                .list-group-item {
                    padding: 0.375rem 0.625rem !important;
                    font-size: 0.875rem;
                }

                .list-group-item .badge {
                    font-size: 0.6875rem;
                }
            }
        </style>
    @endpush
    <div class="main-wrapper">
        <div class="page-wrapper pos-pg-wrapper">
            <div class="content pos-design">
                <form wire:submit="submit">
                    <div class="row g-0 g-md-3">
                        <!-- Category  -->
                        <div class="col-12 col-lg-2">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-primary bg-gradient py-2 px-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h6 class="card-title text-white mb-0">
                                            <i class="fa fa-th-large me-2"></i>
                                            Categories
                                        </h6>
                                        <span class="badge bg-white bg-opacity-25 text-white">{{ count($categories) + 2 }}</span>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush" style="max-height: calc(100vh - 10rem); overflow-y: auto;">
                                        <button type="button"
                                            class="list-group-item list-group-item-action d-flex align-items-center py-2 px-3 border-0 {{ $category_id == 'favorite' ? 'active bg-warning text-white' : '' }}"
                                            wire:click="categorySelect('favorite')">
                                            <i class="fa fa-star me-2 {{ $category_id == 'favorite' ? 'text-white' : 'text-warning' }}"></i>
                                            <span class="flex-grow-1 text-truncate">Favorite</span>
                                        </button>

                                        <button type="button"
                                            class="list-group-item list-group-item-action d-flex align-items-center py-2 px-3 border-0 {{ $category_id == '' ? 'active bg-primary text-white' : '' }}"
                                            wire:click="categorySelect('')">
                                            <i class="fa fa-th-large me-2 {{ $category_id == '' ? 'text-white' : 'text-primary' }}"></i>
                                            <span class="flex-grow-1 text-truncate">All Products</span>
                                        </button>

                                        @foreach ($categories as $item)
                                            <button type="button"
                                                class="list-group-item list-group-item-action d-flex align-items-center py-2 px-3 border-0 {{ $item['id'] == $category_id ? 'active bg-primary text-white' : '' }}"
                                                wire:click="categorySelect({{ $item['id'] }})">
                                                <i class="fa fa-tag me-2 {{ $item['id'] == $category_id ? 'text-white' : 'text-primary' }}"></i>
                                                <span class="flex-grow-1 text-truncate">{{ $item['name'] }}</span>
                                                <span class="badge {{ $item['id'] == $category_id ? 'bg-white bg-opacity-25 text-white' : 'bg-light text-primary' }} rounded-pill ms-2">
                                                    {{ $item['products_count'] }}
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Product Grid -->
                        <div class="col-12 col-lg-6">
                            <div class="pos-products">
                                <div class="sales-header">
                                    <div class="row g-2">
                                        <div class="col-lg-8">
                                            <div class="form-group" wire:ignore>
                                                <label class="form-label d-flex align-items-center mb-1">
                                                    <i class="fa fa-user me-1 text-primary"></i>
                                                    <span class="fw-semibold small">Employee</span>
                                                </label>
                                                {{ html()->select('employee_id', $employees ?? [])->value($employee_id ?? '')->class('select-employee_id-list')->id('employee_id')->attribute('style', 'width:100%')->placeholder('Select employee...') }}
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <label class="form-label d-flex align-items-center mb-1">
                                                    <i class="fa fa-tags me-1 text-primary"></i>
                                                    <span class="fw-semibold small">Sale Type</span>
                                                </label>
                                                {{ html()->select('sale_type', priceTypes())->class('form-select')->id('sale_type')->attribute('wire:model.live', 'sales.sale_type')->required(true)->placeholder('Select type...') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @include('livewire.sale.search-section')

                                <div class="tabs_container" style="height: calc(100vh - 20rem); overflow: auto;">
                                    <div class="tab_content active" data-tab="all">
                                        <livewire:sale.product-list :sale_type="$sales['sale_type']" :category_id="$category_id" :product_key="$product_key" wire:loading.delay />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cart Section -->
                        <div class="col-12 col-lg-4">
                            <div class="product-order-list">
                                <div class="cart-summary">
                                    @php
                                        $total_quantity = collect($items)->sum('quantity');
                                    @endphp
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="cart-badge">{{ $total_quantity }}</span>
                                            <div>
                                                <h6 class="mb-0">Cart Items</h6>
                                                <small class="text-muted">{{ $total_quantity }} items in cart</small>
                                            </div>
                                        </div>
                                        @if ($total_quantity)
                                            <div class="action-group d-flex gap-2">
                                                @can('sale.combo offer')
                                                    <div class="d-flex flex-column align-items-center">
                                                        <button type='button' wire:click="manageComboOffer()" class="action-btn package-btn" title="Manage Combo Offer">
                                                            <i class="fa fa-cube"></i>
                                                        </button>
                                                        <small>Combo</small>
                                                    </div>
                                                @endcan
                                                <div class="d-flex flex-column align-items-center">
                                                    <button type='button' wire:click="viewItems()" class="action-btn view-btn" title="View Items">
                                                        <i class="fa fa-list"></i>
                                                    </button>
                                                    <small>View</small>
                                                </div>
                                                <div class="d-flex flex-column align-items-center">
                                                    <button type='button' wire:confirm="Are you sure to delete this?" wire:click="deleteAllItems()" class="action-btn delete-btn"
                                                        title="Delete All Items">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                    <small>Delete</small>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="product-wrap">
                                    @forelse ($items as $item)
                                        <div class="product-list">
                                            <div class="product-info">
                                                <h6>{{ $item['name'] }}</h6>
                                                <p>{{ currency($item['total']) }}</p>
                                            </div>
                                            <div class="qty-item">
                                                <i class="fa fa-minus-circle" wire:click="modifyQuantity('{{ $item['key'] }}','minus')" title="Decrease Quantity"></i>
                                                {{ html()->text('quantity')->value($item['quantity'])->attribute('min', 1)->class('form-control')->attribute('wire:model.live', 'items.' . $item['key'] . '.quantity') }}
                                                <i class="fa fa-plus-circle" wire:click="modifyQuantity('{{ $item['key'] }}','plus')" title="Increase Quantity"></i>
                                            </div>
                                            <div class="action-group">
                                                <button type="button" wire:click="editItem('{{ $item['key'] }}')" class="action-btn" title="Edit Item">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <button type="button" wire:confirm="Are you sure to delete this?" wire:click="removeItem('{{ $item['key'] }}')" class="action-btn delete-btn"
                                                    title="Delete Item">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center py-5">
                                            <i class="fa fa-shopping-cart fa-3x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">Your cart is empty</p>
                                        </div>
                                    @endforelse
                                </div>

                                <div class="customer-info-section">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-group" wire:ignore>
                                                <label class="form-label d-flex justify-content-between align-items-center">
                                                    <span class="d-flex align-items-center gap-2">
                                                        <i class="fa fa-user"></i>
                                                        <span>Customer</span>
                                                    </span>
                                                    <i id="viewCustomer" class="fa fa-eye pointer hover-opacity" title="View Customer Details"></i>
                                                </label>
                                                {{ html()->select('account_id', $accounts)->value($sales['account_id'])->class('select-customer_id')->id('account_id')->placeholder('Select Customer') }}
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <i class="fa fa-phone"></i>
                                                    <span>Customer Mobile</span>
                                                </label>
                                                <div class="input-group">
                                                    {{ html()->text('customer_mobile')->class('form-control')->attribute('wire:model', 'sales.customer_mobile')->id('customer_mobile')->placeholder('Mobile number') }}
                                                    <button type="button" id="addCustomer" class="btn btn-light" title="Add New Customer">
                                                        <i class="fa fa-user-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-group mb-0">
                                                <label class="form-label">
                                                    <i class="fa fa-tag"></i>
                                                    <span>Discount Amount</span>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="fa fa-calculator"></i>
                                                    </span>
                                                    {{ html()->text('other_discount')->class('form-control number')->attribute('wire:model.lazy', 'sales.other_discount')->placeholder('Enter discount amount') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="order-total">
                                    <table class="table table-borderless mb-0">
                                        <tr>
                                            <td class="fw-medium">
                                                <i class="fa fa-shopping-cart me-2"></i>
                                                Sub Total
                                            </td>
                                            <td class="text-end fw-medium">{{ currency($sales['total']) }}</td>
                                        </tr>
                                        @php
                                            $discount = $sales['total'] ? round(($sales['other_discount'] / $sales['total']) * 100, 2) : 0;
                                        @endphp
                                        <tr>
                                            <td class="text-danger">
                                                <i class="fa fa-tag me-2"></i>
                                                Discount ({{ $discount }}%)
                                            </td>
                                            <td class="text-end text-danger">{{ currency($sales['other_discount']) }}</td>
                                        </tr>
                                        <tr class="border-top">
                                            <td class="fw-bold">
                                                <i class="fa fa-money me-2"></i>
                                                Total
                                            </td>
                                            <td class="text-end fw-bold">{{ currency($sales['grand_total']) }}</td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="payment-method">
                                    <h6>
                                        <span class="d-flex align-items-center gap-2">
                                            <i class="fa fa-credit-card"></i>
                                            <span>Payment Method</span>
                                        </span>
                                        <div class="form-check">
                                            {{ html()->checkbox('send_to_whatsapp')->value('')->class('form-check-input')->attribute('wire:model.live', 'send_to_whatsapp')->id('send_to_whatsapp') }}
                                            <label for="send_to_whatsapp" class="form-check-label d-flex align-items-center gap-2">
                                                <i class="fa fa-whatsapp"></i>
                                                <span>Send Invoice To Whatsapp</span>
                                            </label>
                                        </div>
                                    </h6>

                                    <div class="row g-2 methods">
                                        <div class="col-md-4">
                                            <div class="default-cover">
                                                <a href="#" class="@if ($payment_method_name == 'cash') active @endif" wire:click="selectPaymentMethod('cash')">
                                                    <img src="{{ asset('assets/img/cash-pay.svg') }}" alt="Cash Payment">
                                                    <span>Cash</span>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="default-cover">
                                                <a href="#" class="@if ($payment_method_name == 'card') active @endif" wire:click="selectPaymentMethod('card')">
                                                    <img src="{{ asset('assets/img/card-pay.svg') }}" alt="Card Payment">
                                                    <span>Debit Card</span>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="default-cover">
                                                <a href="#" class="@if ($payment_method_name == 'custom') active @endif" wire:click="selectPaymentMethod('custom')">
                                                    <img src="{{ asset('assets/img/custom-pay.svg') }}" alt="Custom Payment">
                                                    <span>Custom Pay</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="action-buttons">
                                    <div class="d-flex gap-3">
                                        @can('sale.feedback')
                                            <button type="button" wire:click="openFeedback" class="btn btn-outline-primary d-flex align-items-center gap-2">
                                                <i class="fa fa-comment"></i>
                                                <span>Feedback</span>
                                            </button>
                                        @endcan
                                        <div class="d-flex flex-fill gap-3">
                                            <button type="button" wire:click='save("draft")' class="btn btn-secondary flex-fill hover-scale">
                                                <i class="fa fa-save me-2"></i>
                                                Draft
                                            </button>
                                            <button type="submit" wire:confirm="Are you sure to submit this?" class="btn btn-primary flex-fill hover-scale">
                                                <i class="fa fa-check-circle me-2"></i>
                                                Submit
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <x-sale.combo-offer-modal :id="$sales['id'] ?? ''" />
    @push('scripts')
        <script src="{{ asset('assets/pos/feather.min.js') }}"></script>
        <script src="{{ asset('assets/pos/script.js') }}"></script>
        <x-sale.show-confirmation />

        <script>
            $('#addCustomer').click(function() {
                Livewire.dispatch("Customer-Page-Create-Component", {
                    'mobile': $('#customer_mobile').val()
                });
            });
            $('#viewCustomer').click(function() {
                Livewire.dispatch("Customer-View-Component", {
                    'account_id': $('#account_id').val()
                });
            });
            window.addEventListener('AddToCustomerSelectBox', event => {
                var data = event.detail[0];
                var tomSelectInstance = document.querySelector('#account_id').tomselect;
                if (data['name']) {
                    preselectedData = {
                        id: data['id'],
                        name: data['name'],
                        mobile: data['mobile'],
                    };
                    tomSelectInstance.addOption(preselectedData);
                }
                tomSelectInstance.addItem(data['id']);
                @this.set('sales.account_id', data['id']);
            });
            $('#viewDraftedSales').click(function() {
                Livewire.dispatch("Sale-View-DraftTable-Component");
            });
        </script>
        <script>
            $(document).ready(function() {
                document.querySelector('#employee_id').tomselect.open();
                $('#employee_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('employee_id', value);
                });
                $('#account_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('sales.account_id', value);
                });
                window.addEventListener('OpenEmployeeDropBox', event => {
                    document.querySelector('#employee_id').tomselect.open();
                });
            });
        </script>
    @endpush
</div>
