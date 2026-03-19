import React, { useEffect, useState, useRef } from 'react';
import axios from 'axios';
import AsyncSelect from 'react-select/async';
import { BarcodeScanner } from '@thewirv/react-barcode-scanner';
import { Head } from '@inertiajs/react';

export default function ProductSearch() {
    // Filters
    const [productName, setProductName] = useState('');
    const [productCode, setProductCode] = useState('');
    const [productBarcode, setProductBarcode] = useState('');
    const [branchIds, setBranchIds] = useState([]);
    const [showNonZeroOnly, setShowNonZeroOnly] = useState(true);
    const [showBarcodeCodes, setShowBarcodeCodes] = useState(false);

    // Data & pagination / sort
    const [products, setProducts] = useState([]);
    const [totalQuantity, setTotalQuantity] = useState(0);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
    const [limit, setLimit] = useState(10);
    const [page, setPage] = useState(1);
    const [sortField, setSortField] = useState('products.code');
    const [sortDirection, setSortDirection] = useState('desc');
    const [loading, setLoading] = useState(false);

    // Barcode scanner
    const [scannerOpen, setScannerOpen] = useState(false);
    const [scannedCode, setScannedCode] = useState('');

    // Highlighting
    const [highlightedSKU, setHighlightedSKU] = useState('');
    const rowRefs = useRef({});
    const debounceRef = useRef(null);

    useEffect(() => {
        fetchProducts(page);
    }, [page, sortField, sortDirection]);

    useEffect(() => {
        clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            setPage(1);
            fetchProducts(1);
        }, 250);
        return () => clearTimeout(debounceRef.current);
    }, [productName, productCode, productBarcode, branchIds, showNonZeroOnly, showBarcodeCodes, limit]);

    useEffect(() => {
        if (highlightedSKU && rowRefs.current[highlightedSKU]) {
            rowRefs.current[highlightedSKU].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }, [highlightedSKU, products]);

    async function fetchProducts(customPage = null) {
        setLoading(true);
        const p = customPage || page;
        try {
            const params = {
                productName,
                productCode,
                ...(productBarcode ? { productBarcode } : {}),
                branch_id: branchIds.map(b => b.value).join(','),
                show_non_zero: showNonZeroOnly ? 1 : 0,
                show_barcode_sku: showBarcodeCodes ? 1 : 0,
                limit,
                page: p,
                sortField,
                sortDirection,
            };
            const res = await axios.get('/inventory/product/getProduct', { params });
            const { data, total_quantity, links, per_page, total } = res.data;
            setProducts(data || []);
            setTotalQuantity(total_quantity || 0);
            setMeta({
                current_page: links?.current_page || p,
                last_page: links?.last_page || 1,
                per_page: per_page || limit,
                total: total || 0,
            });
        } catch (err) {
            console.error('Fetch products error', err);
        } finally {
            setLoading(false);
        }
    }

    function clearFilters() {
        setProductName('');
        setProductCode('');
        setProductBarcode('');
        setBranchIds([]);
        setShowNonZeroOnly(false);
        setShowBarcodeCodes(false);
        setLimit(10);
        setPage(1);
        setSortField('products.code');
        setSortDirection('desc');
        fetchProducts(1);
    }

    function changeSort(field) {
        if (sortField === field) {
            setSortDirection(prev => (prev === 'asc' ? 'desc' : 'asc'));
        } else {
            setSortField(field);
            setSortDirection('desc');
        }
        setPage(1);
    }

    function goToPage(p) {
        if (p < 1 || p > meta.last_page) return;
        setPage(p);
    }

    async function loadBranchOptions(inputValue) {
        try {
            const res = await axios.get('/settings/branch/list', { params: { query: inputValue } });
            const items = res.data.items || res.data || [];
            return items.map(i => ({ value: i.id, label: i.name || i.text }));
        } catch (err) {
            console.error('Load branches error', err);
            return [];
        }
    }

    function beep() {
        const audio = new Audio('/audio/beep_short.ogg');
        audio.play();
    }

    function showNotification(message, type = 'info') {
        const el = document.createElement('div');
        el.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        el.style.cssText = 'top:20px; right:20px; z-index:9999; min-width:260px; max-width:90vw;';
        el.innerHTML = `${message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(el);
        setTimeout(() => el.parentNode && el.remove(), 3500);
    }

    async function applyScannedCode(code) {
        beep();
        if (!code) return;
        setScannedCode(code);
        setHighlightedSKU(code);
        setPage(1);

        const baseParams = {
            productBarcode: code,
            branch_id: branchIds.map(b => b.value).join(','),
            show_non_zero: showNonZeroOnly ? 1 : 0,
            show_barcode_sku: showBarcodeCodes ? 1 : 0,
            sortField,
            sortDirection,
        };

        try {
            const checkRes = await axios.get('/inventory/product/getProduct', { params: { ...baseParams, limit: 1, page: 1 } });
            const found = Array.isArray(checkRes.data.data) && checkRes.data.data.length > 0;

            if (!found) {
                showNotification(`Barcode not found: ${code}`, 'danger');
                setScannedCode('');
                setHighlightedSKU('');
                return;
            }

            const resFull = await axios.get('/inventory/product/getProduct', { params: { ...baseParams, limit, page: 1 } });
            const { data, total_quantity, links, per_page, total } = resFull.data;

            setProducts(data || []);
            setTotalQuantity(total_quantity || 0);
            setMeta({
                current_page: links?.current_page || 1,
                last_page: links?.last_page || 1,
                per_page: per_page || limit,
                total: total || 0,
            });
            setProductBarcode(code);
            setTimeout(() => setHighlightedSKU(''), 1500);
            showNotification(`Barcode found: ${code}`, 'success');
        } catch (err) {
            console.error('Error checking barcode:', err);
            showNotification(`Error checking barcode: ${code}`, 'danger');
            setScannedCode('');
            setHighlightedSKU('');
        }
    }

    function onScan(result) {
        if (!result?.rawValue) return;
        const clean = result.rawValue.replace(/[^a-zA-Z0-9]/g, '');
        if (clean.length >= 4 && clean.length <= 30) {
            applyScannedCode(clean);
            setScannerOpen(false);
        }
    }

    function renderSortIcon(field) {
        if (sortField !== field) return <i className="fa fa-sort ms-1" style={{ opacity: 0.2 }} />;
        return sortDirection === 'asc'
            ? <i className="fa fa-sort-asc ms-1 text-primary" />
            : <i className="fa fa-sort-desc ms-1 text-primary" />;
    }

    function renderPagination() {
        const { current_page: current, last_page: last } = meta;
        if (last <= 1) return null;

        const pages = [];
        const maxButtons = 5;
        let start = Math.max(1, current - Math.floor(maxButtons / 2));
        let end = Math.min(last, start + maxButtons - 1);
        if (end - start < maxButtons - 1) start = Math.max(1, end - maxButtons + 1);

        pages.push(
            <li key="first" className={`page-item ${current === 1 ? 'disabled' : ''}`}>
                <button className="page-link" onClick={() => goToPage(1)}><i className="fa fa-angle-double-left" /></button>
            </li>
        );
        pages.push(
            <li key="prev" className={`page-item ${current === 1 ? 'disabled' : ''}`}>
                <button className="page-link" onClick={() => goToPage(current - 1)}><i className="fa fa-angle-left" /></button>
            </li>
        );

        for (let p = start; p <= end; p++) {
            pages.push(
                <li key={p} className={`page-item ${p === current ? 'active' : ''}`}>
                    <button className="page-link" onClick={() => goToPage(p)}>{p}</button>
                </li>
            );
        }

        pages.push(
            <li key="next" className={`page-item ${current === last ? 'disabled' : ''}`}>
                <button className="page-link" onClick={() => goToPage(current + 1)}><i className="fa fa-angle-right" /></button>
            </li>
        );
        pages.push(
            <li key="last" className={`page-item ${current === last ? 'disabled' : ''}`}>
                <button className="page-link" onClick={() => goToPage(last)}><i className="fa fa-angle-double-right" /></button>
            </li>
        );

        return <nav><ul className="pagination pagination-sm mb-0">{pages}</ul></nav>;
    }

    const sortableHeader = (field, label, icon, align = '') => (
        <th
            className={`${align} user-select-none`}
            style={{ cursor: 'pointer', whiteSpace: 'nowrap', fontSize: '0.75rem', textTransform: 'uppercase', letterSpacing: '0.5px', fontWeight: 600, color: '#6c757d', borderBottom: '2px solid #dee2e6' }}
            onClick={() => changeSort(field)}
        >
            <i className={`fa fa-${icon} me-1`} style={{ opacity: 0.5 }} />
            <span className="d-none d-md-inline">{label}</span>
            {renderSortIcon(field)}
        </th>
    );

    function renderMobileCards() {
        return (
            <div className="d-md-none">
                {products.map((item, idx) => (
                    <div
                        key={item.inventory_id}
                        className={`p-3 ${idx !== products.length - 1 ? 'border-bottom' : ''} ${item.barcode === highlightedSKU ? 'bg-success bg-opacity-10' : ''}`}
                        ref={el => { if (el) rowRefs.current[item.barcode] = el; }}
                    >
                        <div className="d-flex justify-content-between align-items-start">
                            <div style={{ minWidth: 0, flex: 1 }}>
                                <div className="fw-semibold text-dark" style={{ fontSize: '0.9rem' }}>{item.name}</div>
                                <div className="mt-1 d-flex flex-wrap gap-2">
                                    <span className="badge bg-light text-dark border" style={{ fontSize: '0.7rem' }}>
                                        <i className="fa fa-barcode me-1" />{item.code}
                                    </span>
                                    {item.size && (
                                        <span className="badge bg-light text-dark border" style={{ fontSize: '0.7rem' }}>
                                            <i className="fa fa-arrows-h me-1" />{item.size}
                                        </span>
                                    )}
                                    {item.barcode && (
                                        <span className="badge bg-light text-dark border" style={{ fontSize: '0.7rem' }}>
                                            <i className="fa fa-qrcode me-1" />{item.barcode}
                                        </span>
                                    )}
                                </div>
                            </div>
                            <span className={`badge rounded-pill ms-2 ${item.quantity > 0 ? 'bg-success' : 'bg-danger'}`} style={{ fontSize: '0.85rem', minWidth: '40px' }}>
                                {item.quantity}
                            </span>
                        </div>
                        <div className="d-flex justify-content-between align-items-center mt-2">
                            <small className="text-muted">
                                <i className="fa fa-building me-1" />{item.branch_name}
                            </small>
                            <small className="fw-semibold text-dark">
                                <i className="fa fa-money me-1 text-muted" />{item.mrp}
                            </small>
                        </div>
                    </div>
                ))}
            </div>
        );
    }

    const hasActiveFilters = productName || productCode || productBarcode || branchIds.length > 0 || showBarcodeCodes;

    return (
        <>
            <Head title="Product Inventory" />

            {/* Page Header — Nifty style */}
            <div className="content__header content__boxed overlapping">
                <div className="content__wrap">
                    <nav aria-label="breadcrumb">
                        <ol className="breadcrumb mb-0">
                            <li className="breadcrumb-item"><a href="/">Home</a></li>
                            <li className="breadcrumb-item"><a href="/inventory">Inventory</a></li>
                            <li className="breadcrumb-item active" aria-current="page">Product Search</li>
                        </ol>
                    </nav>
                    <div className="d-flex flex-wrap justify-content-between align-items-end mt-2 gap-2">
                        <div>
                            <h1 className="page-title mb-0" style={{ fontSize: '1.5rem' }}>
                                <i className="fa fa-search me-2 text-primary" />Product Inventory
                            </h1>
                            <p className="text-muted mb-0 mt-1 d-none d-sm-block" style={{ fontSize: '0.85rem' }}>
                                Search, filter, and manage product stock across branches
                            </p>
                        </div>
                        <div className="d-flex gap-2">
                            <button
                                className="btn btn-sm btn-primary"
                                onClick={() => { setScannerOpen(true); setScannedCode(''); }}
                            >
                                <i className="fa fa-camera me-1" /> Scan Barcode
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Main Content */}
            <div className="content__boxed">
                <div className="content__wrap">

                    {/* Summary Cards */}
                    <div className="row g-2 g-md-3 mb-3">
                        <div className="col-6 col-md-3">
                            <div className="card border-0 shadow-sm h-100">
                                <div className="card-body p-3 d-flex align-items-center">
                                    <div className="rounded-circle d-flex align-items-center justify-content-center me-3" style={{ width: 42, height: 42, backgroundColor: '#e8f4fd' }}>
                                        <i className="fa fa-cubes text-primary" />
                                    </div>
                                    <div>
                                        <div className="text-muted" style={{ fontSize: '0.7rem', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Total Quantity</div>
                                        <div className="fw-bold text-dark" style={{ fontSize: '1.2rem' }}>{totalQuantity.toLocaleString()}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-6 col-md-3">
                            <div className="card border-0 shadow-sm h-100">
                                <div className="card-body p-3 d-flex align-items-center">
                                    <div className="rounded-circle d-flex align-items-center justify-content-center me-3" style={{ width: 42, height: 42, backgroundColor: '#e8fdf0' }}>
                                        <i className="fa fa-list text-success" />
                                    </div>
                                    <div>
                                        <div className="text-muted" style={{ fontSize: '0.7rem', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Total Records</div>
                                        <div className="fw-bold text-dark" style={{ fontSize: '1.2rem' }}>{meta.total.toLocaleString()}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-6 col-md-3">
                            <div className="card border-0 shadow-sm h-100">
                                <div className="card-body p-3 d-flex align-items-center">
                                    <div className="rounded-circle d-flex align-items-center justify-content-center me-3" style={{ width: 42, height: 42, backgroundColor: '#fef4e8' }}>
                                        <i className="fa fa-building text-warning" />
                                    </div>
                                    <div>
                                        <div className="text-muted" style={{ fontSize: '0.7rem', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Branches</div>
                                        <div className="fw-bold text-dark" style={{ fontSize: '1.2rem' }}>{branchIds.length > 0 ? branchIds.length : 'All'}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-6 col-md-3">
                            <div className="card border-0 shadow-sm h-100">
                                <div className="card-body p-3 d-flex align-items-center">
                                    <div className="rounded-circle d-flex align-items-center justify-content-center me-3" style={{ width: 42, height: 42, backgroundColor: '#f4e8fd' }}>
                                        <i className="fa fa-filter" style={{ color: '#8b5cf6' }} />
                                    </div>
                                    <div>
                                        <div className="text-muted" style={{ fontSize: '0.7rem', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Page</div>
                                        <div className="fw-bold text-dark" style={{ fontSize: '1.2rem' }}>{meta.current_page} / {meta.last_page}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Filters Card */}
                    <div className="card border-0 shadow-sm mb-3">
                        <div className="card-header bg-white border-bottom py-3">
                            <div className="d-flex justify-content-between align-items-center">
                                <h5 className="mb-0" style={{ fontSize: '0.95rem' }}>
                                    <i className="fa fa-filter me-2 text-muted" />Filters
                                    {hasActiveFilters && (
                                        <span className="badge bg-primary rounded-pill ms-2" style={{ fontSize: '0.65rem' }}>Active</span>
                                    )}
                                </h5>
                                <div className="d-flex align-items-center gap-2">
                                    {loading && (
                                        <span className="text-primary" style={{ fontSize: '0.8rem' }}>
                                            <i className="fa fa-spinner fa-spin me-1" />Loading...
                                        </span>
                                    )}
                                    {hasActiveFilters && (
                                        <button className="btn btn-outline-secondary btn-sm" onClick={clearFilters} style={{ fontSize: '0.75rem' }}>
                                            <i className="fa fa-times me-1" /> Clear All
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>
                        <div className="card-body py-3">
                            <div className="row g-2 g-md-3 align-items-end">
                                <div className="col-12 col-sm-6 col-lg-3">
                                    <label className="form-label mb-1" style={{ fontSize: '0.75rem', fontWeight: 600, color: '#6c757d', textTransform: 'uppercase', letterSpacing: '0.3px' }}>
                                        <i className="fa fa-tag me-1" />Product Name
                                    </label>
                                    <div className="input-group input-group-sm">
                                        <span className="input-group-text bg-white"><i className="fa fa-search text-muted" /></span>
                                        <input
                                            className="form-control"
                                            value={productName}
                                            onChange={(e) => { setProductName(e.target.value); setProductBarcode(''); }}
                                            placeholder="Search by name..."
                                        />
                                    </div>
                                </div>

                                <div className="col-6 col-sm-6 col-lg-2">
                                    <label className="form-label mb-1" style={{ fontSize: '0.75rem', fontWeight: 600, color: '#6c757d', textTransform: 'uppercase', letterSpacing: '0.3px' }}>
                                        <i className="fa fa-code me-1" />Product Code
                                    </label>
                                    <div className="input-group input-group-sm">
                                        <span className="input-group-text bg-white"><i className="fa fa-code text-muted" /></span>
                                        <input
                                            className="form-control"
                                            value={productCode}
                                            onChange={(e) => { setProductCode(e.target.value); setProductBarcode(''); }}
                                            placeholder="Code..."
                                        />
                                    </div>
                                </div>

                                <div className="col-6 col-sm-6 col-lg-2">
                                    <label className="form-label mb-1" style={{ fontSize: '0.75rem', fontWeight: 600, color: '#6c757d', textTransform: 'uppercase', letterSpacing: '0.3px' }}>
                                        <i className="fa fa-barcode me-1" />Barcode
                                    </label>
                                    <div className="input-group input-group-sm">
                                        <span className="input-group-text bg-white"><i className="fa fa-barcode text-muted" /></span>
                                        <input
                                            id="productBarcodeInput"
                                            className="form-control barcode-input"
                                            value={productBarcode}
                                            onChange={(e) => setProductBarcode(e.target.value)}
                                            placeholder="Barcode..."
                                        />
                                    </div>
                                </div>

                                <div className="col-12 col-sm-6 col-lg-3">
                                    <label className="form-label mb-1" style={{ fontSize: '0.75rem', fontWeight: 600, color: '#6c757d', textTransform: 'uppercase', letterSpacing: '0.3px' }}>
                                        <i className="fa fa-building me-1" />Branch
                                    </label>
                                    <AsyncSelect
                                        isMulti
                                        cacheOptions
                                        defaultOptions
                                        loadOptions={loadBranchOptions}
                                        value={branchIds}
                                        onChange={(vals) => { setBranchIds(vals || []); setProductBarcode(''); }}
                                        placeholder="All branches..."
                                        styles={{
                                            control: (base) => ({ ...base, minHeight: '31px', fontSize: '0.875rem', borderColor: '#dee2e6' }),
                                            valueContainer: (base) => ({ ...base, padding: '0 6px' }),
                                            indicatorsContainer: (base) => ({ ...base, height: '31px' }),
                                        }}
                                    />
                                </div>

                                <div className="col-12 col-sm-6 col-lg-2 d-flex flex-row flex-lg-column gap-3 gap-lg-1 pt-1">
                                    <div className="form-check form-switch mb-0">
                                        <input className="form-check-input" type="checkbox" checked={showNonZeroOnly} onChange={(e) => setShowNonZeroOnly(e.target.checked)} id="showNonZeroOnly" />
                                        <label className="form-check-label" htmlFor="showNonZeroOnly" style={{ fontSize: '0.8rem' }}>
                                              &nbsp; In stock
                                        </label>
                                    </div>
                                    <div className="form-check form-switch mb-0">
                                        <input className="form-check-input" type="checkbox" checked={showBarcodeCodes} onChange={(e) => setShowBarcodeCodes(e.target.checked)} id="showBarcodeCodes" />
                                        <label className="form-check-label" htmlFor="showBarcodeCodes" style={{ fontSize: '0.8rem' }}>
                                             &nbsp; Barcode SKU
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Data Table Card */}
                    <div className="card border-0 shadow-sm">
                        {/* Table Toolbar */}
                        <div className="card-header bg-white border-bottom py-2">
                            <div className="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div className="text-muted" style={{ fontSize: '0.8rem' }}>
                                    Showing <strong className="text-dark">{products.length}</strong> of <strong className="text-dark">{meta.total}</strong> results
                                </div>
                                <div className="d-flex align-items-center gap-2">
                                    <span className="text-muted d-none d-sm-inline" style={{ fontSize: '0.8rem' }}>Rows:</span>
                                    <select
                                        className="form-select form-select-sm"
                                        style={{ width: '80px', fontSize: '0.8rem' }}
                                        value={limit}
                                        onChange={(e) => { setLimit(parseInt(e.target.value)); setPage(1); }}
                                    >
                                        <option value={10}>10</option>
                                        <option value={25}>25</option>
                                        <option value={50}>50</option>
                                        <option value={100}>100</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {/* Table / Cards */}
                        {products.length > 0 ? (
                            <>
                                <div className="table-responsive d-none d-md-block">
                                    <table className="table table-hover mb-0 align-middle" style={{ fontSize: '0.85rem' }}>
                                        <thead>
                                            <tr>
                                                {sortableHeader('products.code', 'SKU', 'barcode', 'text-end')}
                                                {sortableHeader('products.name', 'Name', 'tag')}
                                                {sortableHeader('products.size', 'Size', 'arrows-h', 'text-end')}
                                                {sortableHeader('inventories.barcode', 'Barcode', 'qrcode', 'text-end')}
                                                {sortableHeader('products.mrp', 'Price', 'money', 'text-end')}
                                                {sortableHeader('branches.name', 'Branch', 'building')}
                                                {sortableHeader('inventories.quantity', 'Qty', 'cubes', 'text-end')}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {products.map(item => (
                                                <tr
                                                    key={item.inventory_id}
                                                    className={item.barcode === highlightedSKU ? 'table-success' : ''}
                                                    ref={el => { if (el) rowRefs.current[item.barcode] = el; }}
                                                    style={{ transition: 'background-color 0.3s' }}
                                                >
                                                    <td className="text-end">
                                                        <span className="badge bg-light text-primary border" style={{ fontFamily: 'monospace', fontSize: '0.8rem' }}>{item.code}</span>
                                                    </td>
                                                    <td className="fw-medium">{item.name}</td>
                                                    <td className="text-end text-muted">{item.size}</td>
                                                    <td className="text-end" style={{ fontFamily: 'monospace', fontSize: '0.8rem' }}>{item.barcode}</td>
                                                    <td className="text-end fw-medium">{item.mrp}</td>
                                                    <td>
                                                        <span className="badge bg-light text-dark border" style={{ fontSize: '0.75rem' }}>
                                                            <i className="fa fa-building me-1" style={{ opacity: 0.4 }} />{item.branch_name}
                                                        </span>
                                                    </td>
                                                    <td className="text-end">
                                                        <span
                                                            className={`badge rounded-pill ${item.quantity > 0 ? 'bg-success' : 'bg-danger'}`}
                                                            style={{ fontSize: '0.8rem', minWidth: '36px' }}
                                                        >
                                                            {item.quantity}
                                                        </span>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                {renderMobileCards()}
                            </>
                        ) : (
                            <div className="text-center py-5">
                                <i className="fa fa-search fa-3x mb-3 d-block" style={{ opacity: 0.15 }} />
                                <p className="text-muted mb-1">No products found</p>
                                <small className="text-muted">Try adjusting your filters or search terms</small>
                            </div>
                        )}

                        {/* Footer */}
                        <div className="card-footer bg-white border-top py-2">
                            <div className="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div className="text-muted" style={{ fontSize: '0.8rem' }}>
                                    <i className="fa fa-cubes me-1" />
                                    Total Qty: <strong className="text-dark">{totalQuantity.toLocaleString()}</strong>
                                </div>
                                {renderPagination()}
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {/* Scanner Modal */}
            {scannerOpen && (
                <div
                    className="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center"
                    style={{ backgroundColor: 'rgba(0,0,0,0.6)', zIndex: 9999, backdropFilter: 'blur(4px)' }}
                    onClick={(e) => { if (e.target === e.currentTarget) setScannerOpen(false); }}
                >
                    <div className="bg-white rounded-3 shadow-lg" style={{ width: '420px', maxWidth: '92vw', overflow: 'hidden' }}>
                        <div className="d-flex justify-content-between align-items-center p-3 border-bottom">
                            <h6 className="mb-0" style={{ fontSize: '0.95rem' }}>
                                <i className="fa fa-camera me-2 text-primary" />Barcode Scanner
                            </h6>
                            <button className="btn-close btn-close-sm" onClick={() => setScannerOpen(false)} />
                        </div>

                        <div className="position-relative" style={{ height: '260px', backgroundColor: '#1a1a1a' }}>
                            <BarcodeScanner
                                containerStyle={{ width: '100%', height: '100%' }}
                                onSuccess={(text) => {
                                    setProductBarcode(text);
                                    applyScannedCode(text);
                                    setScannerOpen(false);
                                }}
                                onError={(err) => console.error(err)}
                            />
                            <div className="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style={{ pointerEvents: 'none' }}>
                                <div style={{ width: '70%', height: '50%', border: '2px dashed rgba(255,255,255,0.4)', borderRadius: '8px' }} />
                            </div>
                        </div>

                        <div className="p-3">
                            <label className="form-label mb-1 text-muted" style={{ fontSize: '0.75rem' }}>
                                <i className="fa fa-pencil me-1" />Or enter manually
                            </label>
                            <div className="input-group input-group-sm">
                                <span className="input-group-text bg-white"><i className="fa fa-barcode text-muted" /></span>
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder="Type barcode and press Enter"
                                    autoFocus
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter' && e.target.value.trim()) {
                                            const code = e.target.value.trim().replace(/[^a-zA-Z0-9]/g, '');
                                            setProductBarcode(code);
                                            applyScannedCode(code);
                                            setScannerOpen(false);
                                        }
                                    }}
                                />
                            </div>
                            <button className="btn btn-outline-secondary btn-sm mt-3 w-100" onClick={() => setScannerOpen(false)}>
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
