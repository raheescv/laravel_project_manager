<div>
    <!-- Custom CSS for the sales page -->
    @push('styles')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
        <style>
            /* Mobile responsive enhancements for payment section */
            @media (max-width: 767.98px) {
                .payment-section {
                    padding: 15px !important;
                }

                .payment-section .row {
                    margin-bottom: 15px;
                }

                .payment-section .input-group {
                    margin-bottom: 10px;
                }

                .payment-section .btn-group {
                    flex-direction: column;
                    gap: 8px;
                }

                .payment-section .btn-outline-secondary,
                .payment-section .btn-outline-warning {
                    font-size: 0.75rem;
                    padding: 8px 12px;
                    justify-content: center;
                }

                .payment-section .table-responsive {
                    font-size: 0.8rem;
                }

                .payment-section .table-sm th,
                .payment-section .table-sm td {
                    padding: 0.4rem 0.3rem;
                    font-size: 0.75rem;
                }

                .payment-section .payment-remove-btn {
                    padding: 4px 6px;
                    font-size: 0.7rem;
                }

                .payment-empty-state {
                    padding: 30px 15px !important;
                }

                .payment-empty-state .fa-3x {
                    font-size: 2rem !important;
                }

                .payment-empty-state .badge {
                    font-size: 0.65rem;
                    padding: 4px 8px;
                }

                /* Stack payment input on mobile */
                .payment-section .row>.col-md-6 {
                    margin-bottom: 15px;
                }

                .payment-section .add-payment-btn {
                    width: 100%;
                    margin-top: 8px;
                }
            }

            /* Touch-friendly improvements */
            @media (max-width: 991.98px) {

                .payment-section .btn-outline-secondary,
                .payment-section .btn-outline-warning {
                    min-height: 44px;
                    padding: 10px 16px;
                }

                .payment-section .payment-remove-btn {
                    min-width: 36px;
                    min-height: 36px;
                }

                .payment-section .form-control,
                .payment-section .form-select {
                    min-height: 42px;
                    font-size: 16px;
                    /* Prevents zoom on iOS */
                }
            }

            .info-item {
                flex-direction: column;
                align-items: flex-start !important;
            }

            .bg-gradient-primary {
                background: linear-gradient(135deg, var(--bs-primary) 0%, #4361ee 100%);
            }

            .info-item i {
                margin-bottom: 10px;
            }

            @media (min-width: 768px) and (max-width: 991.98px) {
                .hero-header {
                    padding: 20px;
                }

                .payment-section .btn-group {
                    flex-direction: row;
                }

                .payment-section .btn-group .btn {
                    width: auto;
                    text-align: center;
                    justify-content: center;
                }
            }

            /* General Styling */
            .sales-container {
                min-height: calc(100vh - 100px);
            }

            .elegant-card {
                border-radius: 12px;
                border: none;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
                transition: all 0.3s ease;
                overflow: hidden;
            }

            .elegant-card:hover {
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
                transform: translateY(-2px);
            }

            .card-header-gradient {
                background: linear-gradient(135deg, var(--bs-primary) 0%, #4361ee 100%);
                color: white;
                border-bottom: 0;
                padding: 16px 20px;
            }

            /* Hero Header Styling */
            .hero-header {
                background: linear-gradient(135deg, var(--bs-primary) 0%, #4361ee 100%);
                border-radius: 15px;
                padding: 25px;
                margin-bottom: 25px;
                box-shadow: 0 10px 25px rgba(67, 97, 238, 0.15);
                position: relative;
                overflow: hidden;
            }

            .content-animation {
                animation: fadeInUp 0.6s ease-out;
            }

            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* Section Transitions */
            .main-content-section {
                opacity: 0;
                animation: fadeInUp 0.6s ease-out forwards;
                animation-delay: 0.2s;
            }

            .item-row {
                transition: all 0.2s ease;
            }

            .item-row:hover {
                background-color: rgba(0, 0, 0, 0.02);
            }

            /* Custom Input Groups */
            .elegant-input-group {
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
                transition: all 0.3s ease;
                border: 1px solid transparent;
            }

            .elegant-input-group:hover {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
                border-color: rgba(var(--bs-primary-rgb), 0.2);
            }

            .elegant-input-group:focus-within {
                box-shadow: 0 4px 15px rgba(var(--bs-primary-rgb), 0.15);
                border-color: rgba(var(--bs-primary-rgb), 0.5);
            }

            .elegant-input-group .input-group-text {
                border: none;
                background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.8) 0%, rgba(var(--bs-primary-rgb), 1) 100%);
                color: white;
                transition: all 0.3s ease;
                padding: 0.6rem 1rem;
            }

            .elegant-input-group:hover .input-group-text {
                background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.9) 0%, rgba(var(--bs-primary-rgb), 1) 100%);
            }

            .elegant-input-group .form-control {
                border: none;
                padding-left: 0.8rem;
                transition: all 0.3s ease;
            }

            .elegant-input-group .form-control:focus {
                box-shadow: none;
                background-color: rgba(var(--bs-primary-rgb), 0.03);
            }

            /* Enhanced Payment Section Styles */
            .payment-section {
                background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
                border-radius: 12px;
                padding: 20px;
                border: 1px solid #e9ecef;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                transition: all 0.3s ease;
            }

            .payment-section:hover {
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            }

            /* Enhanced input groups */
            .payment-section .input-group {
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
                transition: all 0.3s ease;
            }

            .payment-section .input-group:focus-within {
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(var(--bs-primary-rgb), 0.15);
            }

            .payment-section .input-group-text {
                border: none;
                font-weight: 600;
                transition: all 0.3s ease;
            }

            .payment-section .form-control,
            .payment-section .form-select {
                border: none;
                transition: all 0.3s ease;
            }

            .payment-section .form-control:focus,
            .payment-section .form-select:focus {
                box-shadow: none;
                background-color: rgba(var(--bs-primary-rgb), 0.05);
            }

            /* Enhanced quick action buttons */
            .payment-section .btn-outline-secondary {
                border-radius: 20px;
                padding: 6px 16px;
                font-size: 0.8rem;
                font-weight: 600;
                transition: all 0.3s ease;
                background: white;
                border: 2px solid #dee2e6;
            }

            .payment-section .btn-outline-secondary:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                background: var(--bs-primary);
                border-color: var(--bs-primary);
                color: white;
            }

            .payment-section .btn-outline-warning {
                border-radius: 20px;
                padding: 6px 16px;
                font-size: 0.8rem;
                font-weight: 600;
                transition: all 0.3s ease;
                background: #fff3cd;
                border: 2px solid #ffc107;
                color: #856404;
            }

            .payment-section .btn-outline-warning:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
                background: #ffc107;
                color: white;
            }

            /* Enhanced payment table */
            .payment-section .table {
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            }

            .payment-section .table thead th {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                font-weight: 600;
                border: none;
                color: #495057;
            }

            .payment-section .table tbody tr {
                transition: all 0.3s ease;
            }

            .payment-section .table tbody tr:hover {
                background-color: rgba(var(--bs-primary-rgb), 0.03);
                transform: translateX(5px);
            }

            .payment-section .table tbody td {
                border-color: #f1f3f4;
                vertical-align: middle;
            }

            /* Enhanced payment method badges */
            .payment-section .badge {
                padding: 6px 12px;
                border-radius: 20px;
                font-weight: 600;
                font-size: 0.75rem;
                background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
                border: 1px solid rgba(255, 255, 255, 0.2);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            /* Color-coded payment method icons */
            .payment-method-cash {
                color: #28a745 !important;
            }

            .payment-method-card {
                color: #007bff !important;
            }

            .payment-method-bank {
                color: #6f42c1 !important;
            }

            .payment-method-mobile {
                color: #fd7e14 !important;
            }

            .payment-method-crypto {
                color: #ffc107 !important;
            }

            /* Enhanced remove button */
            .payment-section .btn-outline-danger {
                border-radius: 50%;
                padding: 4px;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
                border: 2px solid #dc3545;
                background: white;
            }

            .payment-section .btn-outline-danger:hover {
                transform: scale(1.1) rotate(90deg);
                box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
                background: #dc3545;
                color: white;
            }

            /* Enhanced add payment button */
            .payment-section .btn-primary {
                border-radius: 8px;
                font-weight: 600;
                padding: 8px 16px;
                transition: all 0.3s ease;
                background: linear-gradient(135deg, var(--bs-primary) 0%, #0056b3 100%);
                border: none;
                box-shadow: 0 3px 10px rgba(var(--bs-primary-rgb), 0.3);
            }

            .payment-section .btn-primary:hover:not(:disabled) {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(var(--bs-primary-rgb), 0.4);
            }

            .payment-section .btn-primary:disabled {
                opacity: 0.6;
                transform: none;
                box-shadow: none;
            }

            /* Payment totals styling */
            .payment-section .table tfoot {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                font-weight: 600;
            }

            .payment-section .table tfoot td {
                border: none;
                padding: 12px;
            }

            /* Empty state styling */
            .payment-section .bg-light {
                background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%) !important;
                border: 2px dashed #dee2e6;
                border-radius: 8px;
                transition: all 0.3s ease;
            }

            .payment-section .bg-light:hover {
                border-color: var(--bs-primary);
                background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, #ffffff 100%) !important;
            }

            /* Payment feedback styles */
            .payment-feedback {
                font-size: 0.8rem;
                margin-top: 0.25rem;
                padding: 0.25rem 0.5rem;
                border-radius: 4px;
                display: none;
            }

            .payment-feedback.error {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }

            .payment-feedback.success {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }

            /* Enhanced payment feedback styles */
            .payment-amount-feedback .feedback-success {
                color: #28a745;
                font-weight: 500;
            }

            .payment-amount-feedback .feedback-warning {
                color: #ffc107;
                font-weight: 500;
            }

            .payment-amount-feedback .feedback-error {
                color: #dc3545;
                font-weight: 500;
            }



            /* Payment table enhancements */
            .payments-table-container {
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
                overflow: hidden;
                margin-top: 15px;
            }

            .payment-table-wrapper {
                border-radius: 8px;
                overflow: hidden;
            }

            .payment-rows tr {
                animation: slideInFromRight 0.3s ease-out;
            }

            @keyframes slideInFromRight {
                from {
                    opacity: 0;
                    transform: translateX(20px);
                }

                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            /* Empty state enhancements */
            .payment-empty-state {
                transition: all 0.3s ease;
                border: 2px dashed #dee2e6 !important;
            }

            .payment-empty-state:hover {
                border-color: var(--bs-primary) !important;
                background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, #ffffff 100%) !important;
            }

            .empty-state-icon {
                animation: float 3s ease-in-out infinite;
            }

            @keyframes float {

                0%,
                100% {
                    transform: translateY(0px);
                }

                50% {
                    transform: translateY(-10px);
                }
            }

            /* Payment totals styling */
            .payment-totals {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            }

            .payment-totals .border-top-2 {
                border-top: 3px solid var(--bs-primary) !important;
            }

            /* Enhanced input group focus effects */
            .payment-method-input:focus-within .input-group-text {
                background: var(--bs-primary) !important;
                transform: scale(1.05);
            }

            .amount-input:focus-within .input-group-text {
                background: #28a745 !important;
                transform: scale(1.05);
            }

            /* Loading state for add button */
            .add-payment-btn {
                position: relative;
                overflow: hidden;
            }

            .add-payment-btn .btn-loading .fa-spinner {
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                from {
                    transform: rotate(0deg);
                }

                to {
                    transform: rotate(360deg);
                }
            }

            /* Payment progress indicator */
            .payment-progress-indicator .badge {
                animation: pulse 2s infinite;
            }

            .payment-progress-bar .progress {
                border-radius: 2px;
                background-color: rgba(255, 255, 255, 0.2);
            }

            .payment-progress-bar .progress-bar {
                transition: width 0.6s ease;
                background-color: #ffffff !important;
                border-radius: 2px;
            }

            @keyframes pulse {

                0%,
                100% {
                    transform: scale(1);
                }

                50% {
                    transform: scale(1.05);
                }
            }

            /* Action Buttons */
            .action-btn {
                padding: 14px 24px;
                border-radius: 12px;
                font-weight: 600;
                transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                position: relative;
                overflow: hidden;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            }

            .action-btn::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(120deg, rgba(255, 255, 255, 0) 30%, rgba(255, 255, 255, 0.7), rgba(255, 255, 255, 0) 70%);
                transform: translateX(-100%);
                transition: transform 0.8s ease;
            }

            .action-btn:hover {
                transform: translateY(-3px) scale(1.02);
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            }

            .action-btn:hover::before {
                transform: translateX(100%);
            }

            .action-btn.btn-primary {
                background: linear-gradient(135deg, var(--bs-primary) 0%, #4361ee 100%);
                border: none;
                box-shadow: 0 5px 15px rgba(var(--bs-primary-rgb), 0.3);
            }

            .action-btn.btn-primary:hover {
                box-shadow: 0 8px 25px rgba(var(--bs-primary-rgb), 0.4);
            }

            .action-btn.btn-warning {
                background: linear-gradient(135deg, var(--bs-warning) 0%, #ffab2d 100%);
                border: none;
                box-shadow: 0 5px 15px rgba(var(--bs-warning-rgb), 0.3);
            }

            .action-btn.btn-warning:hover {
                box-shadow: 0 8px 25px rgba(var(--bs-warning-rgb), 0.4);
            }

            .action-btn.btn-outline-primary:hover {
                background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.9) 0%, rgba(67, 97, 238, 0.9) 100%);
            }

            .action-btn.btn-outline-info:hover {
                background: linear-gradient(135deg, rgba(var(--bs-info-rgb), 0.9) 0%, rgba(13, 202, 240, 0.9) 100%);
            }

            .action-btn i {
                transition: all 0.3s ease;
            }

            .action-btn:hover i {
                transform: scale(1.2);
            }

            /* Table Styling */
            .elegant-table th {
                font-weight: 600;
                background: #f8f9fa;
                position: relative;
                overflow: hidden;
            }

            .elegant-table th:after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                width: 100%;
                height: 2px;
                background: linear-gradient(90deg, transparent, var(--bs-primary), transparent);
                transform: scaleX(0);
                transition: transform 0.4s ease;
            }

            .elegant-table thead:hover th:after {
                transform: scaleX(1);
            }

            .elegant-table td,
            .elegant-table th {
                padding: 12px 15px;
            }

            /* Item Badge */
            .item-badge {
                width: 28px;
                height: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                color: white;
                font-size: 14px;
            }

            /* Employee group header */
            .employee-group-header {
                background: linear-gradient(135deg, #3498db, #2c3e50);
                color: white;
                border-radius: 8px 8px 0 0;
                padding: 10px 15px;
            }



            /* Status badges */
            .status-badge {
                padding: 5px 10px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 600;
            }

            /* Form controls */
            .form-control:focus,
            .form-select:focus {
                box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
                border-color: var(--bs-primary);
            }

            /* Enhanced mobile responsiveness */
            @media (max-width: 767px) {
                .hero-header {
                    padding: 15px;
                }

                .hero-title {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .hero-icon {
                    margin-bottom: 10px;
                }

                .hero-status,
                .hero-date {
                    margin-bottom: 10px;
                }

                .action-btn,
                .hero-action {
                    width: 100%;
                    margin-bottom: 10px;
                }

                .payment-methods .col-md-4 {
                    margin-bottom: 15px;
                }

                .table-responsive {
                    overflow-x: auto;
                    -webkit-overflow-scrolling: touch;
                }

                .elegant-card {
                    margin-bottom: 15px;
                }

                /* Force table to not be like tables anymore */
                .table-responsive-stack {
                    display: block;
                }

                .table-responsive-stack thead {
                    display: none;
                }

                .table-responsive-stack tbody tr {
                    display: block;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    margin-bottom: 15px;
                    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
                }

                .table-responsive-stack tbody tr td {
                    display: block;
                    text-align: right;
                    position: relative;
                    padding-left: 40%;
                    border-bottom: 1px solid #eee;
                }

                .table-responsive-stack tbody tr td:before {
                    content: attr(data-label);
                    position: absolute;
                    left: 10px;
                    top: 50%;
                    transform: translateY(-50%);
                    font-weight: 600;
                    text-align: left;
                }

                .table-responsive-stack tbody tr td:last-child {
                    border-bottom: 0;
                }
            }

            /* Card hover effects */
            .hover-card {
                transition: all 0.3s ease;
            }

            .hover-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            }

            /* Pulse animation for interactive elements */
            .pulse-on-hover:hover {
                animation: pulse 1s infinite;
            }





            /* Order summary enhancements */
            .order-summary-table tr {
                transition: background-color 0.3s ease;
            }

            .order-summary-table tr:hover {
                background-color: rgba(var(--bs-primary-rgb), 0.05);
            }

            .grand-total-row {
                background: linear-gradient(to right, rgba(var(--bs-primary-rgb), 0.1), transparent);
                font-weight: 700;
            }

            /* Animated icons */
            .animated-icon {
                transition: all 0.3s ease;
            }

            .animated-icon:hover {
                transform: scale(1.2) rotate(10deg);
                color: var(--bs-primary);
            }

            /* Order status pill */
            .order-status-pill {
                padding: 8px 15px;
                border-radius: 50px;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-weight: 600;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                transition: all 0.3s ease;
            }

            .order-status-pill:hover {
                transform: scale(1.05);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }







            /* ==================== */
            /* COMPLETE TRANSACTION SECTION STYLES */
            /* ==================== */

            /* Transaction Completion Container */
            .transaction-completion-section {
                background: #ffffff;
                border: 1px solid #e9ecef !important;
            }

            /* Completion Header Styles */
            .completion-header {
                border-bottom: 1px solid #e9ecef;
                padding-bottom: 15px;
            }

            .completion-title {
                font-weight: 600;
                color: var(--bs-dark);
                display: flex;
                align-items: center;
                margin: 0;
            }

            .title-icon {
                width: 32px;
                height: 32px;
                background: var(--bs-primary);
                color: white;
                border-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 10px;
            }

            .completion-subtitle {
                color: var(--bs-secondary);
                font-size: 0.9rem;
                margin: 0;
            }

            .completion-status .status-badge {
                padding: 6px 12px;
                border-radius: 15px;
                font-size: 0.8rem;
                font-weight: 600;
                text-transform: uppercase;
            }

            .status-badge.status-draft {
                background: #17a2b8;
                color: white;
            }

            .status-badge.status-edit {
                background: #ffc107;
                color: white;
            }



            /* Action Buttons Grid */
            .action-buttons-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                margin-bottom: 20px;
            }

            .action-item.col-span-2 {
                grid-column: span 2;
            }

            /* Simple Action Buttons */
            .enhanced-btn {
                min-height: 50px;
                border-radius: 6px;
                text-decoration: none;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 500;
            }

            /* Button Specific Styles */
            .draft-btn {
                background: #6c757d;
                border-color: #6c757d;
                color: white;
            }

            .draft-btn:hover {
                background: #495057;
                border-color: #495057;
                color: white;
            }

            .submit-btn {
                background: var(--bs-primary);
                border-color: var(--bs-primary);
                color: white;
            }

            .submit-btn:hover {
                background: #0056b3;
                border-color: #0056b3;
                color: white;
            }

            .feedback-btn {
                background: #17a2b8;
                border-color: #17a2b8;
                color: white;
            }

            .feedback-btn:hover {
                background: #138496;
                border-color: #138496;
                color: white;
            }













            /* Responsive Design for Complete Transaction */
            @media (max-width: 767.98px) {
                .action-buttons-grid {
                    grid-template-columns: 1fr;
                    gap: 12px;
                }

                .action-item.col-span-2 {
                    grid-column: span 1;
                }

                .enhanced-btn {
                    min-height: 50px;
                }
            }

            @media (max-width: 575.98px) {
                .transaction-completion-section {
                    padding: 20px !important;
                    margin: 15px 0 !important;
                }
            }

            /* Dark mode compatibility for Complete Transaction */
            @media (prefers-color-scheme: dark) {
                .transaction-completion-section {
                    background: #2d3436;
                    border-color: rgba(255, 255, 255, 0.1) !important;
                }

                .completion-title {
                    color: #ffffff;
                }

                .completion-subtitle {
                    color: #b2bec3;
                }
            }

            /* ==================== */
            /* TRANSACTION HISTORY TIMELINE STYLES */
            /* ==================== */

            .timeline-container {
                position: relative;
            }

            .timeline-item {
                position: relative;
                padding-left: 0;
            }

            .timeline-item::before {
                content: '';
                position: absolute;
                left: 19px;
                top: 50px;
                bottom: -15px;
                width: 2px;
                background: linear-gradient(to bottom, #e9ecef 0%, #dee2e6 100%);
                z-index: 1;
            }

            .timeline-item:last-of-type::before {
                display: none;
            }

            .timeline-icon {
                position: relative;
                z-index: 2;
                font-size: 14px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                border: 3px solid #ffffff;
            }

            .timeline-content {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 12px 16px;
                border-left: 3px solid #e9ecef;
                margin-left: 8px;
                transition: all 0.3s ease;
            }

            .timeline-content:hover {
                background: #ffffff;
                border-left-color: var(--bs-primary);
                box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
                transform: translateX(2px);
            }

            .timeline-status {
                border-radius: 8px;
                transition: all 0.3s ease;
            }

            .timeline-status:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            /* Custom scrollbar for payment table */
            .payments-table-container::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }

            .payments-table-container::-webkit-scrollbar-thumb {
                background-color: rgba(0, 0, 0, 0.1);
                border-radius: 4px;
            }

            .payments-table-container::-webkit-scrollbar-thumb:hover {
                background-color: rgba(0, 0, 0, 0.2);
            }

            .payments-table-container::-webkit-scrollbar-track {
                background-color: transparent;
            }


            /* Summary Cards */
            .summary-card {
                border-left: 4px solid var(--bs-primary);
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                transition: all 0.3s ease;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            }

            .summary-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
                background: linear-gradient(to right, rgba(var(--bs-primary-rgb), 0.05), #f8f9fa);
            }
        </style>
    @endpush

    <!-- Main Content Start -->
    <div class="container-fluid sales-container content-animation">
        <form wire:submit="submit">
            <!-- Main Content Area -->
            <div class="row main-content-section">
                <!-- Left Column: Customer Information & Item Selection -->
                <div class="col-lg-8">
                    <!-- Customer Information Card -->
                    <div class="elegant-card mb-4 animate__animated animate__fadeInUp">
                        <div class="card-header-gradient d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-white">
                                <i class="fa fa-user-circle me-2 animated-icon"></i> Customer Information
                            </h5>
                            <button type="button" id="viewCustomer" class="btn btn-sm btn-light text-primary pulse-on-hover" @if ($sales['account_id'] == 3) style="display: none;" @endif>
                                <i class="fa fa-eye me-1"></i> View Details
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <!-- Customer Selection -->
                                <div class="col-md-4">
                                    <div class="mb-1" wire:ignore>
                                        <label for="account_id" class="form-label fw-semibold">
                                            <i class="fa fa-user me-1 text-primary animated-icon"></i>Customer
                                        </label>
                                        <div>
                                            <div class="input-group">
                                                {{ html()->select('account_id', $accounts)->value($sales['account_id'])->class('select-customer_id')->id('account_id')->attribute('style', 'width:100%')->placeholder('Select Customer') }}
                                            </div>
                                        </div>
                                        <div class="mt-2 text-end">
                                            <span class="badge bg-primary">Balance: {{ currency($account_balance ?? 0) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Customer Details - For Walk-in Customers -->
                                <div class="col-md-8">
                                    @if ($sales['account_id'] == 3)
                                        <!-- Customer Form -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="reference_no" class="form-label fw-semibold">
                                                    <i class="fa fa-user me-1 text-primary animated-icon"></i>Customer Name
                                                </label>
                                                <div class="input-group elegant-input-group">
                                                    <span class="input-group-text bg-light"><i class="fa fa-user"></i></span>
                                                    {{ html()->input('customer_name')->value('')->class('form-control')->placeholder(' ')->id('customer_name')->attribute('wire:model', 'sales.customer_name') }}
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="reference_no" class="form-label fw-semibold">
                                                    <i class="fa fa-mobile me-1 text-primary animated-icon"></i>Phone Number
                                                </label>
                                                <div class="input-group elegant-input-group">
                                                    <span class="input-group-text bg-light"><i class="fa fa-mobile"></i></span>
                                                    {{ html()->input('customer_mobile')->value('')->class('form-control')->placeholder(' ')->attribute('wire:model', 'sales.customer_mobile')->id('customer_mobile') }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="row g-2">
                                <!-- Reference Number -->
                                <div class="col-md-4">
                                    <label for="reference_no" class="form-label fw-semibold">
                                        <i class="fa fa-tag me-1 text-primary animated-icon"></i>Reference
                                    </label>
                                    <div class="input-group elegant-input-group">
                                        <span class="input-group-text bg-light"><i class="fa fa-tag"></i></span>
                                        {{ html()->input('reference_no')->value('')->class('form-control')->placeholder('Reference No.')->attribute('wire:model', 'sales.reference_no') }}
                                    </div>
                                </div>

                                <!-- Sale Type -->
                                <div class="col-md-4">
                                    <label for="sale_type" class="form-label fw-semibold">
                                        <i class="fa fa-list me-1 text-primary animated-icon"></i>Sale Type
                                    </label>
                                    <div class="input-group elegant-input-group">
                                        <span class="input-group-text bg-light"><i class="fa fa-list-alt"></i></span>
                                        <div class="flex-grow-1">
                                            {{ html()->select('sale_type', priceTypes())->class('form-select')->id('sale_type')->attribute('wire:model.live', 'sales.sale_type')->required(true)->placeholder('Select Sale Type') }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Date Selection -->
                                <div class="col-md-4">
                                    <label for="date" class="form-label fw-semibold">
                                        <i class="fa fa-calendar me-1 text-primary animated-icon"></i>Sale Date
                                    </label>
                                    <div class="input-group elegant-input-group">
                                        <span class="input-group-text bg-light"><i class="fa fa-calendar"></i></span>
                                        {{ html()->date('date')->value('')->class('form-control')->attribute('wire:model', 'sales.date') }}
                                    </div>
                                </div>

                                <!-- Due Date -->
                                <div class="col-md-4">
                                    <label for="due_date" class="form-label fw-semibold">
                                        <i class="fa fa-clock-o me-1 text-primary animated-icon"></i>Due Date
                                    </label>
                                    <div class="input-group elegant-input-group">
                                        <span class="input-group-text bg-light"><i class="fa fa-clock-o"></i></span>
                                        {{ html()->date('due_date')->value('')->class('form-control')->attribute('wire:model', 'sales.due_date') }}
                                    </div>
                                </div>

                                <!-- Delivery Address -->
                                <div class="col-md-8">
                                    <label for="address" class="form-label fw-semibold">
                                        <i class="fa fa-map-marker me-1 text-primary"></i>Delivery Address
                                    </label>
                                    <div class="input-group elegant-input-group">
                                        <span class="input-group-text bg-light"><i class="fa fa-location-arrow"></i></span>
                                        {{ html()->textarea('address')->value('')->class('form-control')->rows(1)->attribute('wire:model.live', 'sales.address')->placeholder('Delivery address...') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Item Information Card -->
                    <div class="elegant-card mb-4">
                        <div class="card-header-gradient">
                            <h5 class="mb-0 text-white">
                                <i class="fa fa-cube me-2"></i> Item Information
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <!-- Product Selection Area -->
                            <div class="product-selection mb-4 bg-light p-3 rounded-3">
                                <div class="row g-3">
                                    <!-- Employee Selection -->
                                    <div class="col-md-4">
                                        <div wire:ignore>
                                            <label for="employee_id" class="form-label fw-semibold mb-2">
                                                <i class="fa fa-user-tie me-1 text-primary"></i>Select Employee
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-secondary text-white"><i class="fa fa-user"></i></span>
                                                <div class="flex-grow-1">
                                                    {{ html()->select('employee_id', $employees)->value($employee_id)->class('select-employee_id-list')->id('employee_id')->attribute('style', 'width:100%')->placeholder('Select Employee') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Product Selection -->
                                    <div class="col-md-8">
                                        <div wire:ignore>
                                            <label for="inventory_id" class="form-label fw-semibold mb-2">
                                                <i class="fa fa-cubes me-1 text-primary"></i>Select Product/Service
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-secondary text-white"><i class="fa fa-cube"></i></span>
                                                <div class="flex-grow-1">
                                                    {{ html()->select('inventory_id', [])->value('')->class('select-inventory-product_id-list')->id('inventory_id')->attribute('style', 'width:100%')->placeholder('Select Product') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Quick Search by Barcode -->
                                    <div class="col-md-12 mt-3">
                                        <div class="input-group elegant-input-group">
                                            <span class="input-group-text bg-dark text-white"><i class="fa fa-barcode"></i></span>
                                            {{ html()->text('barcode_key')->class('form-control')->placeholder('Scan barcode or enter product code')->attribute('wire:model.live.debounce.500ms', 'barcode_key') }}
                                            <button class="btn btn-primary" type="button" id="searchBarcode">
                                                <i class="fa fa-barcode"></i> Scan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Items Table -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle elegant-table border">
                                    <thead>
                                        <tr>
                                            <th><i class="fa fa-hashtag me-1"></i> SL</th>
                                            <th width="20%"><i class="fa fa-cube me-1"></i> Product</th>
                                            <th class="text-end"><i class="fa fa-tag me-1"></i> Unit Price</th>
                                            <th class="text-end"><i class="fa fa-cubes me-1"></i> Qty</th>
                                            <th class="text-end"><i class="fa fa-tag me-1"></i> Discount</th>
                                            <th class="text-end"><i class="fa fa-calculator me-1"></i> Tax %</th>
                                            <th class="text-end"><i class="fa fa-money me-1"></i> Total</th>
                                            @if ($sales['other_discount'] > 0)
                                                <th class="text-end"><i class="fa fa-calculator me-1"></i> Eff. Total</th>
                                            @endif
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $result = [];
                                            foreach ($items as $key => $value) {
                                                [$parent, $sub] = explode('-', $key);
                                                if (!isset($result[$parent])) {
                                                    $result[$parent] = [];
                                                }
                                                $result[$parent][$sub] = $value;
                                            }
                                            $data = $result;
                                        @endphp

                                        @if (count($data))
                                            @foreach ($data as $employee_id => $groupedItems)
                                                <!-- Employee Group Header -->
                                                <tr>
                                                    @php
                                                        $first = array_values($groupedItems)[0];
                                                    @endphp
                                                    <th colspan="{{ $sales['other_discount'] > 0 ? 9 : 8 }}" class="employee-group-header">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fa fa-user-circle me-2 fs-4"></i>
                                                            <div>
                                                                <span class="fw-bold">{{ $first['employee_name'] }}</span>
                                                                <span class="badge bg-light text-dark ms-2">{{ count($groupedItems) }} items</span>
                                                            </div>
                                                        </div>
                                                    </th>
                                                </tr>

                                                <!-- Items for this employee -->
                                                @foreach ($groupedItems as $item)
                                                    <tr class="item-row">
                                                        <td>
                                                            <div class="item-badge bg-secondary">
                                                                {{ $loop->iteration }}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="fw-semibold">{{ $item['name'] }}</div>
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-primary text-white px-1"><i class="fa fa-tag"></i></span>
                                                                {{ html()->number('unit_price')->value($item['unit_price'])->class('form-control text-end px-1')->attribute('wire:model.lazy', 'items.' . $item['key'] . '.unit_price') }}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-success text-white px-1"><i class="fa fa-cubes"></i></span>
                                                                {{ html()->number('quantity')->value($item['quantity'])->attribute('min', 1)->class('form-control text-end px-1')->attribute('step', 'any')->attribute('wire:model.lazy', 'items.' . $item['key'] . '.quantity') }}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-warning text-white px-1"><i class="fa fa-tag"></i></span>
                                                                {{ html()->number('discount')->value($item['discount'])->class('form-control text-end px-1')->attribute('wire:model.lazy', 'items.' . $item['key'] . '.discount') }}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-info text-white px-1"><i class="fa fa-calculator"></i></span>
                                                                {{ html()->number('tax')->value($item['tax'])->attribute('max', '50')->class('form-control text-end px-1')->attribute('wire:model.lazy', 'items.' . $item['key'] . '.tax') }}
                                                            </div>
                                                        </td>
                                                        <td class="text-end fw-bold">{{ currency($item['total']) }}</td>
                                                        @if ($sales['other_discount'] > 0)
                                                            <td class="text-end fw-bold">{{ currency($item['effective_total']) }}</td>
                                                        @endif
                                                        <td>
                                                            <button type="button" wire:click="removeItem('{{ $item['key'] }}')" wire:confirm="Are you sure you want to remove this item?"
                                                                class="btn btn-sm btn-danger rounded-circle">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="{{ $sales['other_discount'] > 0 ? 9 : 8 }}" class="text-center py-4">
                                                    <div class="py-5">
                                                        <i class="fa fa-shopping-basket fa-3x text-muted mb-3"></i>
                                                        <p class="lead text-muted">No items in this sale yet.</p>
                                                        <p class="text-muted">Select an employee and product to add items</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                    <tfoot class="table-group-divider">
                                        @php
                                            $items = collect($items);
                                        @endphp
                                        <tr class="fw-bold bg-light">
                                            <th colspan="3" class="text-end py-3"><i class="fa fa-calculator me-1"></i> Grand Total</th>
                                            <th class="text-end py-3">{{ currency($items->sum('quantity'), 3) }}</th>
                                            <th class="text-end py-3">{{ currency($items->sum('discount')) }}</th>
                                            <th class="text-end py-3">{{ currency($items->sum('tax_amount')) }}</th>
                                            <th class="text-end py-3 fs-5">{{ currency($items->sum('total')) }}</th>
                                            @if ($sales['other_discount'] > 0)
                                                <th class="text-end py-3 fs-5">{{ currency($items->sum('effective_total')) }}</th>
                                            @endif
                                            <th class="py-3"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction History -->
                    @isset($sales['created_user']['name'])
                        <div class="elegant-card mb-4">
                            <div class="card-header-gradient">
                                <h5 class="mb-0 text-white">
                                    <i class="fa fa-history me-2"></i> Transaction History
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="timeline-container">
                                    <!-- Created Event -->
                                    <div class="timeline-item d-flex align-items-start mb-3">
                                        <div class="timeline-icon bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 40px; height: 40px; min-width: 40px;">
                                            <i class="fa fa-plus"></i>
                                        </div>
                                        <div class="timeline-content flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="mb-0 fw-semibold text-success">Transaction Created</h6>
                                                <small class="text-muted">{{ isset($sales['created_at']) ? \Carbon\Carbon::parse($sales['created_at'])->format('M d, Y H:i') : 'N/A' }}</small>
                                            </div>
                                            <p class="mb-0 text-muted small">
                                                Created by <strong class="text-dark">{{ $sales['created_user']['name'] }}</strong>
                                            </p>
                                        </div>
                                    </div>

                                    @isset($sales['updated_user']['name'])
                                        <!-- Updated Event -->
                                        <div class="timeline-item d-flex align-items-start mb-3">
                                            <div class="timeline-icon bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                                style="width: 40px; height: 40px; min-width: 40px;">
                                                <i class="fa fa-edit"></i>
                                            </div>
                                            <div class="timeline-content flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <h6 class="mb-0 fw-semibold text-info">Transaction Updated</h6>
                                                    <small class="text-muted">{{ isset($sales['updated_at']) ? \Carbon\Carbon::parse($sales['updated_at'])->format('M d, Y H:i') : 'N/A' }}</small>
                                                </div>
                                                <p class="mb-0 text-muted small">
                                                    Updated by <strong class="text-dark">{{ $sales['updated_user']['name'] }}</strong>
                                                </p>
                                            </div>
                                        </div>
                                    @endisset

                                    @isset($sales['cancelled_user']['name'])
                                        <!-- Cancelled Event -->
                                        <div class="timeline-item d-flex align-items-start mb-3">
                                            <div class="timeline-icon bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                                style="width: 40px; height: 40px; min-width: 40px;">
                                                <i class="fa fa-times"></i>
                                            </div>
                                            <div class="timeline-content flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <h6 class="mb-0 fw-semibold text-danger">Transaction Cancelled</h6>
                                                    <small class="text-muted">{{ isset($sales['cancelled_at']) ? \Carbon\Carbon::parse($sales['cancelled_at'])->format('M d, Y H:i') : 'N/A' }}</small>
                                                </div>
                                                <p class="mb-0 text-muted small">
                                                    Cancelled by <strong class="text-dark">{{ $sales['cancelled_user']['name'] }}</strong>
                                                </p>
                                            </div>
                                        </div>
                                    @endisset

                                    <!-- Status Badge -->
                                    <div class="timeline-status mt-4 p-3 rounded"
                                        style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-left: 4px solid {{ $sales['status'] === 'draft' ? '#17a2b8' : ($sales['status'] === 'cancelled' ? '#dc3545' : '#28a745') }};">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="mb-1 fw-semibold">Current Status</h6>
                                                <span class="badge bg-{{ $sales['status'] === 'draft' ? 'info' : ($sales['status'] === 'cancelled' ? 'danger' : 'success') }} text-white">
                                                    {{ ucfirst($sales['status'] ?? 'Draft') }}
                                                </span>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted d-block">Transaction ID</small>
                                                <strong class="text-dark">#{{ $sales['id'] ?? 'New' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endisset
                </div>

                <!-- Right Column: Order Summary & Payment -->
                <div class="col-lg-4">
                    <!-- Order Summary -->
                    <div class="elegant-card mb-4 sticky-top hover-card animate__animated animate__fadeInRight" style="top: 10px; z-index: 10;">
                        <div class="card-header-gradient d-flex align-items-center justify-content-between">
                            <h5 class="mb-0 text-white">
                                <i class="fa fa-calculator me-2 animated-icon"></i> Order Summary
                            </h5>
                            <span class="badge bg-light text-primary order-status-pill">
                                <i class="fa fa-shopping-basket"></i> {{ count($items) }} Items
                            </span>
                        </div>
                        <div class="card-body p-4">
                            <div class="summary-card mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="fw-semibold text-dark">
                                        <i class="fa fa-money me-1 text-success"></i> Gross Total
                                    </div>
                                    <div class="fs-4 fw-bold text-primary">{{ currency($sales['gross_amount']) }}</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="fw-semibold text-dark">
                                        <i class="fa fa-shopping-cart me-1 text-info"></i> Sale Total
                                    </div>
                                    <div class="fs-4 fw-bold text-primary">{{ currency($sales['total']) }}</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="fw-semibold text-dark">
                                        <i class="fa fa-tag me-1 text-danger"></i> Item Discount
                                    </div>
                                    <div class="fw-bold text-danger">{{ currency($sales['item_discount']) }}</div>
                                </div>
                            </div>

                            <!-- Additional Costs Table -->
                            <div class="p-3 border rounded-3 mb-3">
                                <div class="mb-3">
                                    <label for="other_discount" class="form-label fw-semibold">
                                        <i class="fa fa-tag me-1 text-warning"></i> Other Discount
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-warning text-white"><i class="fa fa-tag"></i></span>
                                        <input class="form-control text-end" type="number" name="other_discount" id="other_discount" value="" wire:model.lazy="sales.other_discount">
                                    </div>
                                    <small class="text-muted">You can add a percentage (e.g. 10%)</small>
                                </div>

                                <div>
                                    <label for="freight" class="form-label fw-semibold">
                                        <i class="fa fa-truck me-1 text-info"></i> Freight/Shipping
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-info text-white"><i class="fa fa-truck"></i></span>
                                        <input class="form-control text-end" type="number" name="freight" id="freight" value="" wire:model.lazy="sales.freight">
                                    </div>
                                </div>
                            </div>

                            <!-- Grand Total Summary - Enhanced -->
                            <div class="grand-total-summary-card">
                                <div class="card border-0 shadow-lg rounded-4 mt-3 overflow-hidden position-relative">
                                    <!-- Gradient Background Overlay -->
                                    <div class="grand-total-gradient-overlay"></div>

                                    <!-- Header with animated icon -->
                                    <div class="card-header bg-primary text-white py-2">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h6 class="mb-0 fw-bold d-flex align-items-center text-white">
                                                <div class="icon-container me-2">
                                                    <i class="fa fa-bar-chart animated-bounce"></i>
                                                </div>
                                                Transaction Summary
                                            </h6>
                                            <div class="summary-badge">
                                                <span class="badge bg-white text-primary fw-bold px-3 py-2 rounded-pill">
                                                    <i class="fa fa-receipt me-1"></i>
                                                    Final
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-body p-0 position-relative">
                                        <!-- Enhanced Grand Total Row -->
                                        <div class="grand-total-highlight p-4 bg-gradient-light border-bottom">
                                            <div class="row align-items-center">
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <div class="total-icon-wrapper me-3">
                                                            <i class="fa fa-calculator text-primary fs-3"></i>
                                                        </div>
                                                        <div>
                                                            <h5 class="mb-1 fw-bold text-dark">Grand Total</h5>
                                                            <small class="text-muted">Final amount to be paid</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6 text-end">
                                                    <div class="grand-total-amount">
                                                        <div class="total-badge bg-primary text-white px-4 py-3 rounded-3 shadow-sm">
                                                            <div class="fs-4 fw-bold mb-0 animate__animated animate__pulse animate__infinite animate__slow">
                                                                {{ currency($sales['grand_total']) }}
                                                            </div>
                                                            <small class="opacity-75">Total Due</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Payment Status Grid -->
                                        <div class="payment-status-grid p-3">
                                            <div class="row g-3">
                                                <!-- Paid Amount -->
                                                <div class="col-md-6">
                                                    <div class="status-card bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 p-3 h-100">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <div class="status-info">
                                                                <h6 class="mb-1 fw-bold text-success">Amount Paid</h6>
                                                                <small class="text-muted">Received payments</small>
                                                            </div>
                                                            <div class="status-amount text-end">
                                                                <div class="fs-5 fw-bold text-success">
                                                                    {{ currency($sales['paid']) }}
                                                                </div>
                                                                @if ($sales['grand_total'] > 0)
                                                                    <small class="text-muted">
                                                                        {{ number_format(($sales['paid'] / $sales['grand_total']) * 100, 1) }}%
                                                                    </small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Balance Amount -->
                                                <div class="col-md-6">
                                                    <div
                                                        class="status-card {{ $sales['balance'] > 0 ? 'bg-danger bg-opacity-10 border border-danger border-opacity-25' : ($sales['balance'] < 0 ? 'bg-warning bg-opacity-10 border border-warning border-opacity-25' : 'bg-success bg-opacity-10 border border-success border-opacity-25') }} rounded-3 p-3 h-100">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <div class="status-info">
                                                                <h6 class="mb-1 fw-bold {{ $sales['balance'] > 0 ? 'text-danger' : ($sales['balance'] < 0 ? 'text-warning' : 'text-success') }}">
                                                                    @if ($sales['balance'] > 0)
                                                                        Outstanding
                                                                    @elseif($sales['balance'] < 0)
                                                                        Change Due
                                                                    @else
                                                                        Fully Paid
                                                                    @endif
                                                                </h6>
                                                                <small class="text-muted">
                                                                    @if ($sales['balance'] > 0)
                                                                        Remaining balance
                                                                    @elseif($sales['balance'] < 0)
                                                                        Excess payment
                                                                    @else
                                                                        Transaction complete
                                                                    @endif
                                                                </small>
                                                            </div>
                                                            <div class="status-amount text-end">
                                                                <div class="fs-5 fw-bold {{ $sales['balance'] > 0 ? 'text-danger' : ($sales['balance'] < 0 ? 'text-warning' : 'text-success') }}">
                                                                    @if ($sales['balance'] != 0)
                                                                        {{ currency(abs($sales['balance'])) }}
                                                                    @else
                                                                        <i class="fa fa-check text-success"></i>
                                                                    @endif
                                                                </div>
                                                                @if ($sales['balance'] != 0 && $sales['grand_total'] > 0)
                                                                    <small class="text-muted">
                                                                        {{ number_format((abs($sales['balance']) / $sales['grand_total']) * 100, 1) }}%
                                                                    </small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- WhatsApp Integration Section -->
                                        <div class="whatsapp-section border-top bg-light bg-opacity-50 p-3">
                                            <div class="form-check form-switch d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center">
                                                    <div class="whatsapp-icon-container me-3">
                                                        <div class="rounded-circle bg-success bg-opacity-10 p-2 d-flex align-items-center justify-content-center">
                                                            <i class="fa fa-whatsapp text-success fs-5"></i>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="form-check-label fw-semibold mb-1 d-block" for="send_to_whatsapp">
                                                            Send Invoice via WhatsApp
                                                        </label>
                                                        <small class="text-muted">Instantly share invoice with customer</small>
                                                    </div>
                                                </div>
                                                <div class="form-switch-container">
                                                    {{ html()->checkbox('send_to_whatsapp')->class('form-check-input form-switch-input')->id('send_to_whatsapp')->attribute('wire:model.live', 'send_to_whatsapp') }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Payment Progress Bar -->
                                        @if ($sales['grand_total'] > 0)
                                            <div class="payment-progress-section p-3 border-top">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <small class="text-muted fw-semibold">Payment Progress</small>
                                                    <small class="badge bg-primary bg-opacity-10 text-primary">
                                                        {{ number_format(min(100, ($sales['paid'] / $sales['grand_total']) * 100), 1) }}% Complete
                                                    </small>
                                                </div>
                                                <div class="progress rounded-pill" style="height: 8px;">
                                                    <div class="progress-bar bg-gradient-success rounded-pill progress-bar-animated" role="progressbar"
                                                        style="width: {{ min(100, ($sales['paid'] / $sales['grand_total']) * 100) }}%" aria-valuenow="{{ $sales['paid'] }}" aria-valuemin="0"
                                                        aria-valuemax="{{ $sales['grand_total'] }}">
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Simple Payment Section -->
                            <div class="card border-0 shadow-sm mt-4">
                                <div class="card-header bg-primary text-white py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-white d-flex align-items-center">
                                            <i class="fa fa-credit-card me-2"></i>Payment Details
                                        </h6>
                                        @if ($sales['grand_total'] > 0)
                                            <div class="payment-progress-indicator">
                                                @php
                                                    $progressPercentage = min(100, ($sales['paid'] / $sales['grand_total']) * 100);
                                                @endphp
                                                <span class="badge bg-light text-primary fw-bold">
                                                    {{ number_format($progressPercentage, 0) }}% Paid
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    @if ($sales['grand_total'] > 0)
                                        <div class="payment-progress-bar mt-2">
                                            <div class="progress" style="height: 4px;">
                                                <div class="progress-bar bg-light" role="progressbar" style="width: {{ min(100, ($sales['paid'] / $sales['grand_total']) * 100) }}%"
                                                    aria-valuenow="{{ $sales['paid'] }}" aria-valuemin="0" aria-valuemax="{{ $sales['grand_total'] }}">
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="card-body p-3 payment-section">
                                    <!-- Payment Input Section -->
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-2 d-flex align-items-center">
                                                <i class="fa fa-credit-card me-1 text-primary"></i> Payment Method
                                            </label>
                                            <div wire:ignore>
                                                <div class="input-group-sm payment-method-input">
                                                    {{ html()->select('payment_method_id', $paymentMethods)->value($default_payment_method_id)->class('select-payment_method_id-list')->id('payment_method_id')->placeholder('Select Payment Method')->attribute('style', 'width:100%') }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-2 d-flex align-items-center">
                                                <i class="fa fa-calculator me-1 text-success"></i> Amount
                                            </label>
                                            <div class="input-group input-group-sm amount-input">
                                                <span class="input-group-text bg-success text-white">
                                                    <i class="fa fa-dollar"></i>
                                                </span>
                                                {{ html()->number('amount')->value('')->class('form-control form-control-sm text-end')->attribute('step', '0.01')->attribute('min', '0')->placeholder('0.00')->id('payment_amount')->attribute('wire:model.live', 'payment.amount') }}
                                                <button type="button" wire:click="addPayment" class="btn btn-primary btn-sm add-payment-btn">
                                                    <span class="btn-content">
                                                        <i class="fa fa-plus me-1"></i> Add
                                                    </span>
                                                    <span class="btn-loading d-none">
                                                        <i class="fa fa-spinner fa-spin me-1"></i> Adding...
                                                    </span>
                                                </button>
                                            </div>
                                            <div class="payment-amount-feedback mt-1"></div>
                                        </div>
                                    </div>

                                    <!-- Quick Amount Buttons -->


                                    <!-- Payments Table -->
                                    @if (count($payments) > 0)
                                        <div class="payments-table-container">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="text-muted mb-0 d-flex align-items-center">
                                                    <i class="fa fa-list me-1"></i> Payment Summary ({{ count($payments) }} {{ Str::plural('payment', count($payments)) }})
                                                </h6>
                                                <span class="badge bg-info">{{ count($payments) }}</span>
                                            </div>
                                            <div class="table-responsive payment-table-wrapper">
                                                <table class="table table-sm table-hover mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th class="py-2 border-0">
                                                                <i class="fa fa-credit-card me-1 text-primary"></i>Method
                                                            </th>
                                                            <th class="py-2 text-end border-0">
                                                                <i class="fa fa-dollar-sign me-1 text-success"></i>Amount
                                                            </th>
                                                            <th class="py-2 text-center border-0" width="80">
                                                                <i class="fa fa-cog me-1 text-muted"></i>Action
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="payment-rows">
                                                        @foreach ($payments as $key => $item)
                                                            <tr class="payment-row">
                                                                <td class="py-2">
                                                                    <span class="badge bg-light text-dark d-flex align-items-center gap-1" style="width: fit-content;">
                                                                        <span>{{ $item['name'] }}</span>
                                                                    </span>
                                                                </td>
                                                                <td class="py-2 text-end fw-semibold text-success">
                                                                    {{ currency($item['amount']) }}
                                                                </td>
                                                                <td class="py-2 text-center pull-right">
                                                                    <button type="button" wire:click="removePayment('{{ $key }}')" wire:confirm="Remove this payment?"
                                                                        class="btn btn-outline-danger btn-sm payment-remove-btn" title="Remove payment">
                                                                        <i class="fa fa-times"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot class="payment-totals">
                                                        <tr class="fw-bold border-top-2">
                                                            <td class="py-3 bg-light">
                                                                <i class="fa fa-calculator me-1 text-primary"></i>Total Paid:
                                                            </td>
                                                            <td class="py-3 text-end bg-light text-success">
                                                                {{ currency($sales['paid']) }}
                                                            </td>
                                                            <td class="py-3 bg-light"></td>
                                                        </tr>
                                                        @if ($sales['balance'] > 0)
                                                            <tr class="text-danger fw-bold">
                                                                <td class="py-2">
                                                                    <i class="fa fa-exclamation-triangle me-1"></i>Remaining:
                                                                </td>
                                                                <td class="py-2 text-end">{{ currency($sales['balance']) }}</td>
                                                                <td class="py-2"></td>
                                                            </tr>
                                                        @elseif($sales['balance'] < 0)
                                                            <tr class="text-warning fw-bold">
                                                                <td class="py-2">
                                                                    <i class="fa fa-coins me-1"></i>Change Due:
                                                                </td>
                                                                <td class="py-2 text-end">{{ currency(abs($sales['balance'])) }}</td>
                                                                <td class="py-2"></td>
                                                            </tr>
                                                        @else
                                                            <tr class="text-success fw-bold">
                                                                <td class="py-2">
                                                                    <i class="fa fa-check-circle me-1"></i>Status:
                                                                </td>
                                                                <td class="py-2 text-end">Fully Paid</td>
                                                                <td class="py-2"></td>
                                                            </tr>
                                                        @endif
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    @else
                                        <div class="payment-empty-state text-center py-5 bg-light rounded">
                                            <div class="empty-state-content">
                                                <h6 class="text-muted mb-2">No Payments Added</h6>
                                                <p class="text-muted small mb-3">
                                                    Add a payment method and amount to get started with processing this transaction.
                                                </p>
                                                <div class="d-flex justify-content-center gap-2">
                                                    <span class="badge bg-primary bg-opacity-10 text-primary text-white">
                                                        <i class="fa fa-arrow-up me-1"></i> Select method above
                                                    </span>
                                                    <span class="badge bg-success bg-opacity-10 text-success text-white">
                                                        <i class="fa fa-plus me-1"></i> Enter amount
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Complete Transaction Section - Enhanced -->
                            <div class="transaction-completion-section card shadow-sm rounded p-4 mt-4 border">
                                <div class="action-buttons-grid">
                                    @if (!isset($sales['id']))
                                        <!-- Draft Save Button -->
                                        <div class="action-item">
                                            <button type="button" wire:click='save("draft")' class="btn enhanced-btn draft-btn w-100">
                                                <i class="fa fa-save me-2"></i>
                                                Save as Draft
                                            </button>
                                        </div>

                                        <!-- Submit Button -->
                                        <div class="action-item">
                                            <button type="submit" wire:confirm="Are you sure to submit this sale?" id="submitSaleBtn" class="btn enhanced-btn submit-btn w-100">
                                                <i class="fa fa-check-circle me-2"></i>
                                                Submit & Print Invoice
                                            </button>
                                        </div>
                                    @else
                                        <!-- Draft Save Button -->
                                        @if ($sales['status'] == 'draft')
                                            <div class="action-item">
                                                <button type="button" wire:click='save("draft")' class="btn enhanced-btn draft-btn w-100">
                                                    <i class="fa fa-save me-2"></i>
                                                    Save as Draft
                                                </button>
                                            </div>
                                        @endif

                                        <!-- Submit Button -->
                                        <div class="action-item">
                                            <button type="submit" wire:confirm="Are you sure to submit this sale?" id="submitSaleBtn" class="btn enhanced-btn submit-btn w-100">
                                                <i class="fa fa-check-circle me-2"></i>
                                                Update & Print Invoice
                                            </button>
                                        </div>
                                    @endif

                                    @can('sale.feedback')
                                        <!-- Feedback Button -->
                                        <div class="action-item col-span-2">
                                            <button type="button" wire:click="openFeedback" class="btn enhanced-btn feedback-btn w-100">
                                                <i class="fa fa-comment me-2"></i>
                                                Add Customer Feedback
                                            </button>
                                        </div>
                                    @endcan
                                </div>
                            </div>

                            <!-- Validation Errors - Enhanced -->
                            @if ($this->getErrorBag()->count())
                                <div class="card border-danger shadow-sm mt-4 overflow-hidden animate__animated animate__headShake">
                                    <div class="card-header bg-danger text-white d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-white text-danger p-1 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                            <i class="fa fa-exclamation-triangle"></i>
                                        </div>
                                        <h6 class="mb-0">Please fix the following errors</h6>
                                    </div>
                                    <div class="card-body p-3 bg-danger bg-opacity-10">
                                        <ul class="mb-0 ps-3">
                                            @foreach ($this->getErrorBag()->toArray() as $error)
                                                <li class="mb-1 d-flex align-items-center gap-2 animate__animated animate__fadeInDown" style="animation-delay: {{ $loop->iteration * 0.1 }}s">
                                                    <i class="fa fa-times-circle text-danger"></i>
                                                    <span>{{ $error[0] }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <div class="text-center mt-3">
                                            <button type="button" class="btn btn-sm btn-danger"
                                                onclick="this.closest('.card').classList.add('animate__animated', 'animate__fadeOutRight'); setTimeout(() => this.closest('.card').style.display = 'none', 500);">
                                                <i class="fa fa-check me-1"></i> Got it
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        <x-sale.show-confirmation />

        <script>
            $(document).ready(function() {
                let employee_id = "{{ $employee_id }}";
                // to open the dropdown by on load
                if (employee_id) {
                    document.querySelector('#inventory_id').tomselect.open();
                } else {
                    document.querySelector('#employee_id').tomselect.open();
                }
                $('#account_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('sales.account_id', value);
                    if (value == 3) {
                        $('#customer_name').select();
                    } else {
                        if (employee_id) {
                            document.querySelector('#inventory_id').tomselect.open();
                        } else {
                            document.querySelector('#employee_id').tomselect.open();
                        }
                    }
                });
                $('#inventory_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('inventory_id', value);
                });
                $('#employee_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('employee_id', value);
                    document.querySelector('#inventory_id').tomselect.open();
                });
                $('#payment_method_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('payment.payment_method_id', value);
                    $('#payment').select();
                });
                window.addEventListener('OpenProductBox', event => {
                    @this.set('inventory_id', null);
                    document.querySelector('#inventory_id').tomselect.open();
                });
                window.addEventListener('ResetSelectBox', event => {
                    var tomSelectInstance = document.querySelector('#account_id').tomselect;
                    tomSelectInstance.addItem("{{ $sales['account_id'] }}");

                    var tomSelectInstance = document.querySelector('#payment_method_id').tomselect;
                    tomSelectInstance.addItem("{{ $payment['payment_method_id'] }}");

                    var tomSelectInstance = document.querySelector('#inventory_id').tomselect;
                    tomSelectInstance.clear();
                    document.querySelector('#inventory_id').tomselect.open();
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
                });
                $('#viewCustomer').click(function() {
                    Livewire.dispatch("Customer-View-Component", {
                        'account_id': $('#account_id').val()
                    });
                });
            });
        </script>
    @endpush
</div>
