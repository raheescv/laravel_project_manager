<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('account::index') }}">Accounts</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Cheque Print</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Cheque Print</h1>
            <p class="lead">
                Print cheque with or without template
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Cheque Details</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('account::cheque::print') }}" method="GET" target="_blank">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="text" class="form-control" id="date" name="date" value="{{ date('d-M-Y') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payee" class="form-label">Payee Name</label>
                                    <input type="text" class="form-control" id="payee" name="payee" placeholder="Enter payee name" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount</label>
                                    <input type="number" step="0.01" class="form-control" id="amount" name="amount" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cheque_number" class="form-label">Cheque Number</label>
                                    <input type="text" class="form-control" id="cheque_number" name="cheque_number" placeholder="CHQ-000001">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="bank_name" class="form-label">Bank Name</label>
                                    <input type="text" class="form-control" id="bank_name" name="bank_name" placeholder="Bank Name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="account_number" class="form-label">Account Number</label>
                                    <input type="text" class="form-control" id="account_number" name="account_number" placeholder="5002-626450536-14516568">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="signature" class="form-label">Signature</label>
                                    <input type="text" class="form-control" id="signature" name="signature" placeholder="Signature">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="use_template" class="form-label">Template</label>
                                    <select class="form-select" id="use_template" name="use_template">
                                        <option value="1">With Template</option>
                                        <option value="0">Without Template</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-print me-2"></i>Print Cheque
                            </button>
                            <a href="{{ route('account::cheque::configuration') }}" class="btn btn-outline-secondary">
                                <i class="fa fa-cog me-2"></i>Configure Cheque
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>





