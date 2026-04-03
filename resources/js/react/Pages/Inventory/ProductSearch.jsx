import React, { useEffect, useState, useRef, useCallback } from 'react';
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
    const [filtersOpen, setFiltersOpen] = useState(false);

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
    const [scannerMounted, setScannerMounted] = useState(false);
    const [scannedCode, setScannedCode] = useState('');
    const scannerContainerRef = useRef(null);

    // Highlighting
    const [highlightedSKU, setHighlightedSKU] = useState('');
    const rowRefs = useRef({});
    const debounceRef = useRef(null);

    // Mobile sort dropdown
    const [mobileSortOpen, setMobileSortOpen] = useState(false);

    // Keep a ref to every MediaStream we acquire so we can kill them reliably
    const activeStreamsRef = useRef(new Set());

    /**
     * Monkey-patch getUserMedia once so we can track every stream the
     * BarcodeScanner (or anything else) opens during this component's life.
     */
    useEffect(() => {
        const origGetUserMedia = navigator.mediaDevices?.getUserMedia?.bind(navigator.mediaDevices);
        if (!origGetUserMedia) return;

        navigator.mediaDevices.getUserMedia = async function patched(constraints) {
            const stream = await origGetUserMedia(constraints);
            activeStreamsRef.current.add(stream);
            return stream;
        };

        return () => {
            // Restore original on unmount
            navigator.mediaDevices.getUserMedia = origGetUserMedia;
        };
    }, []);

    /**
     * Force-stop every camera / media stream we know about,
     * plus every <video> element on the page as a nuclear fallback.
     */
    const stopAllCameraStreams = useCallback(() => {
        // 1. Stop all tracked streams
        activeStreamsRef.current.forEach(stream => {
            try {
                stream.getTracks().forEach(track => track.stop());
            } catch (_) { /* already stopped */ }
        });
        activeStreamsRef.current.clear();

        // 2. Stop every <video> element's srcObject on the whole page
        document.querySelectorAll('video').forEach(video => {
            try {
                if (video.srcObject) {
                    video.srcObject.getTracks().forEach(track => track.stop());
                    video.srcObject = null;
                }
                video.pause();
                video.remove();
            } catch (_) { /* ignore */ }
        });
    }, []);

    /**
     * Close the scanner modal and release the camera.
     */
    const closeScanner = useCallback(() => {
        // 1. Kill streams immediately BEFORE unmounting (DOM is still alive)
        stopAllCameraStreams();

        // 2. Unmount the BarcodeScanner component
        setScannerMounted(false);

        // 3. Close the modal after a tick; run cleanup once more as a safety net
        setTimeout(() => {
            stopAllCameraStreams();
            setScannerOpen(false);
        }, 150);
    }, [stopAllCameraStreams]);

    /**
     * Open the scanner modal.
     */
    const openScanner = useCallback(() => {
        setScannedCode('');
        setScannerOpen(true);
        // Mount the BarcodeScanner after the modal DOM is ready
        setTimeout(() => setScannerMounted(true), 50);
    }, []);

    // Cleanup camera on unmount (navigating away, etc.)
    useEffect(() => {
        return () => stopAllCameraStreams();
    }, [stopAllCameraStreams]);

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
        el.style.cssText = 'top:20px; right:20px; z-index:9999; min-width:220px; max-width:90vw; font-size:0.85rem;';
        el.innerHTML = `${message} <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>`;
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
            closeScanner();
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
        const maxButtons = 3;
        let start = Math.max(1, current - Math.floor(maxButtons / 2));
        let end = Math.min(last, start + maxButtons - 1);
        if (end - start < maxButtons - 1) start = Math.max(1, end - maxButtons + 1);

        pages.push(
            <li key="prev" className={`page-item ${current === 1 ? 'disabled' : ''}`}>
                <button className="page-link px-2 px-sm-3" onClick={() => goToPage(current - 1)}>
                    <i className="fa fa-chevron-left" />
                </button>
            </li>
        );

        if (start > 1) {
            pages.push(
                <li key={1} className="page-item">
                    <button className="page-link px-2 px-sm-3" onClick={() => goToPage(1)}>1</button>
                </li>
            );
            if (start > 2) {
                pages.push(
                    <li key="dots-start" className="page-item disabled">
                        <span className="page-link px-1 px-sm-2">...</span>
                    </li>
                );
            }
        }

        for (let p = start; p <= end; p++) {
            pages.push(
                <li key={p} className={`page-item ${p === current ? 'active' : ''}`}>
                    <button className="page-link px-2 px-sm-3" onClick={() => goToPage(p)}>{p}</button>
                </li>
            );
        }

        if (end < last) {
            if (end < last - 1) {
                pages.push(
                    <li key="dots-end" className="page-item disabled">
                        <span className="page-link px-1 px-sm-2">...</span>
                    </li>
                );
            }
            pages.push(
                <li key={last} className="page-item">
                    <button className="page-link px-2 px-sm-3" onClick={() => goToPage(last)}>{last}</button>
                </li>
            );
        }

        pages.push(
            <li key="next" className={`page-item ${current === last ? 'disabled' : ''}`}>
                <button className="page-link px-2 px-sm-3" onClick={() => goToPage(current + 1)}>
                    <i className="fa fa-chevron-right" />
                </button>
            </li>
        );

        return <nav><ul className="pagination pagination-sm mb-0 flex-wrap">{pages}</ul></nav>;
    }

    const sortableHeader = (field, label, icon, align = '') => (
        <th
            className={`${align} user-select-none`}
            style={{ cursor: 'pointer', whiteSpace: 'nowrap', fontSize: '0.75rem', textTransform: 'uppercase', letterSpacing: '0.5px', fontWeight: 600, color: '#6c757d', borderBottom: '2px solid #dee2e6' }}
            onClick={() => changeSort(field)}
        >
            <i className={`fa fa-${icon} me-1`} style={{ opacity: 0.5 }} />
            {label}
            {renderSortIcon(field)}
        </th>
    );

    const sortOptions = [
        { field: 'products.code', label: 'SKU' },
        { field: 'products.name', label: 'Name' },
        { field: 'products.mrp', label: 'Price' },
        { field: 'inventories.quantity', label: 'Quantity' },
        { field: 'branches.name', label: 'Branch' },
    ];

    function renderMobileCards() {
        return (
            <div className="d-md-none">
                {products.map((item, idx) => (
                    <div
                        key={item.inventory_id}
                        className={`px-3 py-3 ${idx !== products.length - 1 ? 'border-bottom' : ''} ${item.barcode === highlightedSKU ? 'bg-success bg-opacity-10' : ''}`}
                        ref={el => { if (el) rowRefs.current[item.barcode] = el; }}
                        style={{ transition: 'background-color 0.3s' }}
                    >
                        {/* Row 1: Name + Quantity badge */}
                        <div className="d-flex justify-content-between align-items-start gap-2">
                            <div style={{ minWidth: 0, flex: 1 }}>
                                <div className="fw-semibold text-dark" style={{ fontSize: '0.88rem', lineHeight: 1.3 }}>
                                    {item.name}
                                </div>
                            </div>
                            <span
                                className={`badge rounded-pill flex-shrink-0 ${item.quantity > 0 ? 'bg-success' : 'bg-danger'}`}
                                style={{ fontSize: '0.82rem', minWidth: '38px', padding: '5px 10px' }}
                            >
                                {item.quantity}
                            </span>
                        </div>

                        {/* Row 2: Badges */}
                        <div className="mt-2 d-flex flex-wrap gap-1">
                            <span className="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style={{ fontSize: '0.68rem', fontFamily: 'monospace' }}>
                                {item.code}
                            </span>
                            {item.size && (
                                <span className="badge bg-light text-dark border" style={{ fontSize: '0.68rem' }}>
                                    {item.size}
                                </span>
                            )}
                            {item.barcode && (
                                <span className="badge bg-light text-muted border" style={{ fontSize: '0.68rem', fontFamily: 'monospace' }}>
                                    <i className="fa fa-barcode me-1" style={{ fontSize: '0.6rem' }} />{item.barcode}
                                </span>
                            )}
                        </div>

                        {/* Row 3: Branch + Price */}
                        <div className="d-flex justify-content-between align-items-center mt-2">
                            <span className="text-muted" style={{ fontSize: '0.78rem' }}>
                                <i className="fa fa-building me-1" style={{ opacity: 0.5 }} />{item.branch_name}
                            </span>
                            <span className="fw-semibold text-dark" style={{ fontSize: '0.85rem' }}>
                                {item.mrp}
                            </span>
                        </div>
                    </div>
                ))}
            </div>
        );
    }

    const hasActiveFilters = productName || productCode || productBarcode || branchIds.length > 0 || showBarcodeCodes;
    const activeFilterCount = [productName, productCode, productBarcode, branchIds.length > 0, showBarcodeCodes].filter(Boolean).length;

    // Label style shared across filter inputs
    const labelStyle = { fontSize: '0.72rem', fontWeight: 600, color: '#6c757d', textTransform: 'uppercase', letterSpacing: '0.3px' };

    return (
        <>
            <Head title="Product Inventory" />

            {/* Page Header */}
            <div className="content__header content__boxed overlapping">
                <div className="content__wrap">
                    <nav aria-label="breadcrumb" className="d-none d-sm-block">
                        <ol className="breadcrumb mb-0" style={{ fontSize: '0.8rem' }}>
                            <li className="breadcrumb-item"><a href="/">Home</a></li>
                            <li className="breadcrumb-item"><a href="/inventory">Inventory</a></li>
                            <li className="breadcrumb-item active" aria-current="page">Product Search</li>
                        </ol>
                    </nav>
                    <div className="d-flex justify-content-between align-items-center mt-sm-2">
                        <div style={{ minWidth: 0 }}>
                            <h1 className="page-title mb-0" style={{ fontSize: '1.25rem' }}>
                                <i className="fa fa-search me-2 text-primary" />
                                <span className="d-none d-sm-inline">Product Inventory</span>
                                <span className="d-sm-none">Inventory</span>
                            </h1>
                            <p className="text-muted mb-0 mt-1 d-none d-md-block" style={{ fontSize: '0.82rem' }}>
                                Search, filter, and manage product stock across branches
                            </p>
                        </div>
                        <button
                            className="btn btn-primary btn-sm d-flex align-items-center gap-1 flex-shrink-0"
                            onClick={openScanner}
                            style={{ whiteSpace: 'nowrap' }}
                        >
                            <i className="fa fa-camera" />
                            <span className="d-none d-sm-inline">Scan Barcode</span>
                            <span className="d-sm-none">Scan</span>
                        </button>
                    </div>
                </div>
            </div>

            {/* Main Content */}
            <div className="content__boxed">
                <div className="content__wrap">

                    {/* Compact Summary Strip */}
                    <div className="row g-2 mb-3">
                        <div className="col-6 col-md-3">
                            <div className="card border-0 shadow-sm h-100">
                                <div className="card-body p-2 p-md-3 d-flex align-items-center">
                                    <div className="rounded-circle d-flex align-items-center justify-content-center me-2 me-md-3 flex-shrink-0" style={{ width: 36, height: 36, backgroundColor: '#e8f4fd' }}>
                                        <i className="fa fa-cubes text-primary" style={{ fontSize: '0.85rem' }} />
                                    </div>
                                    <div style={{ minWidth: 0 }}>
                                        <div className="text-muted text-truncate" style={{ fontSize: '0.65rem', textTransform: 'uppercase', letterSpacing: '0.3px' }}>Total Qty</div>
                                        <div className="fw-bold text-dark" style={{ fontSize: '1.05rem' }}>{totalQuantity.toLocaleString()}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-6 col-md-3">
                            <div className="card border-0 shadow-sm h-100">
                                <div className="card-body p-2 p-md-3 d-flex align-items-center">
                                    <div className="rounded-circle d-flex align-items-center justify-content-center me-2 me-md-3 flex-shrink-0" style={{ width: 36, height: 36, backgroundColor: '#e8fdf0' }}>
                                        <i className="fa fa-list text-success" style={{ fontSize: '0.85rem' }} />
                                    </div>
                                    <div style={{ minWidth: 0 }}>
                                        <div className="text-muted text-truncate" style={{ fontSize: '0.65rem', textTransform: 'uppercase', letterSpacing: '0.3px' }}>Records</div>
                                        <div className="fw-bold text-dark" style={{ fontSize: '1.05rem' }}>{meta.total.toLocaleString()}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-6 col-md-3">
                            <div className="card border-0 shadow-sm h-100">
                                <div className="card-body p-2 p-md-3 d-flex align-items-center">
                                    <div className="rounded-circle d-flex align-items-center justify-content-center me-2 me-md-3 flex-shrink-0" style={{ width: 36, height: 36, backgroundColor: '#fef4e8' }}>
                                        <i className="fa fa-building text-warning" style={{ fontSize: '0.85rem' }} />
                                    </div>
                                    <div style={{ minWidth: 0 }}>
                                        <div className="text-muted text-truncate" style={{ fontSize: '0.65rem', textTransform: 'uppercase', letterSpacing: '0.3px' }}>Branches</div>
                                        <div className="fw-bold text-dark" style={{ fontSize: '1.05rem' }}>{branchIds.length > 0 ? branchIds.length : 'All'}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-6 col-md-3">
                            <div className="card border-0 shadow-sm h-100">
                                <div className="card-body p-2 p-md-3 d-flex align-items-center">
                                    <div className="rounded-circle d-flex align-items-center justify-content-center me-2 me-md-3 flex-shrink-0" style={{ width: 36, height: 36, backgroundColor: '#f4e8fd' }}>
                                        <i className="fa fa-file-text-o" style={{ color: '#8b5cf6', fontSize: '0.85rem' }} />
                                    </div>
                                    <div style={{ minWidth: 0 }}>
                                        <div className="text-muted text-truncate" style={{ fontSize: '0.65rem', textTransform: 'uppercase', letterSpacing: '0.3px' }}>Page</div>
                                        <div className="fw-bold text-dark" style={{ fontSize: '1.05rem' }}>{meta.current_page}/{meta.last_page}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Mobile Quick Search + Filter Toggle */}
                    <div className="d-md-none mb-3">
                        <div className="input-group shadow-sm">
                            <span className="input-group-text bg-white border-end-0">
                                <i className="fa fa-search text-muted" />
                            </span>
                            <input
                                className="form-control border-start-0"
                                value={productName}
                                onChange={(e) => { setProductName(e.target.value); setProductBarcode(''); }}
                                placeholder="Search products..."
                                style={{ fontSize: '0.9rem' }}
                            />
                            <button
                                className={`btn ${filtersOpen ? 'btn-primary' : 'btn-outline-secondary'} d-flex align-items-center gap-1`}
                                onClick={() => setFiltersOpen(!filtersOpen)}
                                type="button"
                            >
                                <i className="fa fa-sliders" />
                                {activeFilterCount > 0 && (
                                    <span className="badge bg-danger rounded-pill" style={{ fontSize: '0.6rem' }}>{activeFilterCount}</span>
                                )}
                            </button>
                        </div>
                    </div>

                    {/* Filters Card */}
                    <div className={`card border-0 shadow-sm mb-3 ${!filtersOpen ? 'd-none d-md-block' : ''}`}>
                        <div className="card-header bg-white border-bottom py-2 py-md-3">
                            <div className="d-flex justify-content-between align-items-center">
                                <h6 className="mb-0" style={{ fontSize: '0.88rem' }}>
                                    <i className="fa fa-filter me-2 text-muted" />Filters
                                    {hasActiveFilters && (
                                        <span className="badge bg-primary rounded-pill ms-2" style={{ fontSize: '0.6rem' }}>Active</span>
                                    )}
                                </h6>
                                <div className="d-flex align-items-center gap-2">
                                    {loading && (
                                        <span className="text-primary" style={{ fontSize: '0.75rem' }}>
                                            <i className="fa fa-spinner fa-spin me-1" />Loading...
                                        </span>
                                    )}
                                    {hasActiveFilters && (
                                        <button className="btn btn-outline-secondary btn-sm" onClick={clearFilters} style={{ fontSize: '0.72rem' }}>
                                            <i className="fa fa-times me-1" />Clear
                                        </button>
                                    )}
                                    <button className="btn btn-sm btn-link text-muted d-md-none p-0" onClick={() => setFiltersOpen(false)}>
                                        <i className="fa fa-chevron-up" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div className="card-body py-2 py-md-3">
                            <div className="row g-2 g-md-3 align-items-end">
                                {/* Product Name - hidden on mobile (already in quick search) */}
                                <div className="col-12 col-sm-6 col-lg-3 d-none d-md-block">
                                    <label className="form-label mb-1" style={labelStyle}>
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

                                <div className="col-6 col-lg-2">
                                    <label className="form-label mb-1" style={labelStyle}>
                                        <i className="fa fa-code me-1" />Code
                                    </label>
                                    <input
                                        className="form-control form-control-sm"
                                        value={productCode}
                                        onChange={(e) => { setProductCode(e.target.value); setProductBarcode(''); }}
                                        placeholder="Code..."
                                    />
                                </div>

                                <div className="col-6 col-lg-2">
                                    <label className="form-label mb-1" style={labelStyle}>
                                        <i className="fa fa-barcode me-1" />Barcode
                                    </label>
                                    <input
                                        id="productBarcodeInput"
                                        className="form-control form-control-sm barcode-input"
                                        value={productBarcode}
                                        onChange={(e) => setProductBarcode(e.target.value)}
                                        placeholder="Barcode..."
                                    />
                                </div>

                                <div className="col-12 col-sm-6 col-lg-3">
                                    <label className="form-label mb-1" style={labelStyle}>
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
                                            control: (base) => ({ ...base, minHeight: '31px', fontSize: '0.85rem', borderColor: '#dee2e6' }),
                                            valueContainer: (base) => ({ ...base, padding: '0 6px' }),
                                            indicatorsContainer: (base) => ({ ...base, height: '31px' }),
                                            menu: (base) => ({ ...base, zIndex: 10 }),
                                        }}
                                    />
                                </div>

                                <div className="col-12 col-sm-6 col-lg-2 d-flex flex-row flex-lg-column gap-3 gap-lg-1 pt-1">
                                    <div className="form-check form-switch mb-0">
                                        <input className="form-check-input" type="checkbox" checked={showNonZeroOnly} onChange={(e) => setShowNonZeroOnly(e.target.checked)} id="showNonZeroOnly" />
                                        <label className="form-check-label" htmlFor="showNonZeroOnly" style={{ fontSize: '0.78rem' }}>
                                            In stock
                                        </label>
                                    </div>
                                    <div className="form-check form-switch mb-0">
                                        <input className="form-check-input" type="checkbox" checked={showBarcodeCodes} onChange={(e) => setShowBarcodeCodes(e.target.checked)} id="showBarcodeCodes" />
                                        <label className="form-check-label" htmlFor="showBarcodeCodes" style={{ fontSize: '0.78rem' }}>
                                            Barcode SKU
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
                            <div className="d-flex justify-content-between align-items-center gap-2">
                                <div className="text-muted" style={{ fontSize: '0.78rem' }}>
                                    <strong className="text-dark">{products.length}</strong>
                                    <span className="d-none d-sm-inline"> of <strong className="text-dark">{meta.total}</strong> results</span>
                                    {loading && <i className="fa fa-spinner fa-spin ms-2 text-primary" />}
                                </div>
                                <div className="d-flex align-items-center gap-2">
                                    {/* Mobile sort button */}
                                    <div className="d-md-none position-relative">
                                        <button
                                            className="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                                            onClick={() => setMobileSortOpen(!mobileSortOpen)}
                                            style={{ fontSize: '0.75rem' }}
                                        >
                                            <i className="fa fa-sort" />Sort
                                        </button>
                                        {mobileSortOpen && (
                                            <>
                                                <div className="position-fixed top-0 start-0 w-100 h-100" style={{ zIndex: 99 }} onClick={() => setMobileSortOpen(false)} />
                                                <div className="position-absolute end-0 mt-1 bg-white border rounded-2 shadow-lg py-1" style={{ zIndex: 100, minWidth: '160px' }}>
                                                    {sortOptions.map(opt => (
                                                        <button
                                                            key={opt.field}
                                                            className={`dropdown-item small py-2 ${sortField === opt.field ? 'active' : ''}`}
                                                            onClick={() => { changeSort(opt.field); setMobileSortOpen(false); }}
                                                        >
                                                            {opt.label}
                                                            {sortField === opt.field && (
                                                                <i className={`fa fa-sort-${sortDirection === 'asc' ? 'asc' : 'desc'} ms-2`} />
                                                            )}
                                                        </button>
                                                    ))}
                                                </div>
                                            </>
                                        )}
                                    </div>
                                    <select
                                        className="form-select form-select-sm"
                                        style={{ width: '70px', fontSize: '0.75rem' }}
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
                                                <th className="px-3 py-3 border-bottom" style={{ width: '50px' }}>
                                                    <i className="fa fa-image text-muted" />
                                                </th>
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
                                                    <td>
                                                        {item.thumbnail ? (
                                                            <img src={item.thumbnail} alt={item.name} className="rounded" style={{ width: '36px', height: '36px', objectFit: 'cover' }} loading="lazy" />
                                                        ) : (
                                                            <span className="d-inline-flex align-items-center justify-content-center rounded bg-light text-muted" style={{ width: '36px', height: '36px' }}>
                                                                <i className="fa fa-image" />
                                                            </span>
                                                        )}
                                                    </td>
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
                                <i className="fa fa-search fa-2x mb-3 d-block" style={{ opacity: 0.12 }} />
                                <p className="text-muted mb-1" style={{ fontSize: '0.9rem' }}>No products found</p>
                                <small className="text-muted">Try adjusting your filters or search terms</small>
                            </div>
                        )}

                        {/* Footer */}
                        <div className="card-footer bg-white border-top py-2">
                            <div className="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div className="text-muted" style={{ fontSize: '0.78rem' }}>
                                    <i className="fa fa-cubes me-1" />
                                    Qty: <strong className="text-dark">{totalQuantity.toLocaleString()}</strong>
                                </div>
                                {renderPagination()}
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {/* ════════ Scanner Modal ════════ */}
            {scannerOpen && (() => {
                const isDesktop = window.innerWidth >= 768;
                return (
                    <div
                        className="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
                        style={{ zIndex: 9999, backgroundColor: 'rgba(0,0,0,0.6)' }}
                        onClick={(e) => { if (e.target === e.currentTarget && isDesktop) closeScanner(); }}
                    >
                        {/* Force video to fill container */}
                        <style>{`
                            .scanner-camera-area video {
                                width: 100% !important;
                                height: 100% !important;
                                object-fit: cover !important;
                            }
                            .scanner-camera-area > div {
                                width: 100% !important;
                                height: 100% !important;
                            }
                        `}</style>

                        <div
                            ref={scannerContainerRef}
                            className="bg-white d-flex flex-column overflow-hidden"
                            style={isDesktop ? {
                                width: 520,
                                maxHeight: '90vh',
                                borderRadius: 12,
                                boxShadow: '0 25px 60px rgba(0,0,0,0.3)',
                            } : {
                                position: 'fixed',
                                top: 0, left: 0, right: 0, bottom: 0,
                                width: '100%',
                                height: '100%',
                            }}
                        >
                            {/* Header */}
                            <div
                                className="d-flex justify-content-between align-items-center px-3 flex-shrink-0"
                                style={{ height: 50, background: '#1a1a2e' }}
                            >
                                <span className="text-white fw-semibold" style={{ fontSize: '0.92rem' }}>
                                    <i className="fa fa-camera me-2" style={{ opacity: 0.8 }} />
                                    Barcode Scanner
                                </span>
                                <button
                                    className="btn p-0 d-flex align-items-center justify-content-center"
                                    onClick={closeScanner}
                                    style={{ width: 34, height: 34, borderRadius: '50%', backgroundColor: 'rgba(255,255,255,0.15)' }}
                                >
                                    <i className="fa fa-times text-white" />
                                </button>
                            </div>

                            {/* Camera */}
                            <div
                                className="scanner-camera-area position-relative flex-grow-1"
                                style={{
                                    minHeight: isDesktop ? 320 : 200,
                                    backgroundColor: '#0a0a0a',
                                    overflow: 'hidden',
                                }}
                            >
                                {scannerMounted && (
                                    <BarcodeScanner
                                        containerStyle={{ width: '100%', height: '100%' }}
                                        onSuccess={(text) => {
                                            setProductBarcode(text);
                                            applyScannedCode(text);
                                            closeScanner();
                                        }}
                                        onError={(err) => console.error(err)}
                                    />
                                )}

                                {/* Guide overlay */}
                                <div
                                    className="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center"
                                    style={{ pointerEvents: 'none' }}
                                >
                                    <div style={{
                                        width: isDesktop ? '55%' : '65%',
                                        maxWidth: 280,
                                        aspectRatio: '5 / 3',
                                        border: '2px solid rgba(255,255,255,0.6)',
                                        borderRadius: 14,
                                        boxShadow: '0 0 0 9999px rgba(0,0,0,0.4)',
                                    }} />
                                    <span
                                        className="text-white mt-3 px-3 py-1 rounded-pill"
                                        style={{ fontSize: '0.73rem', backgroundColor: 'rgba(0,0,0,0.55)' }}
                                    >
                                        Place barcode inside the frame
                                    </span>
                                </div>
                            </div>

                            {/* Bottom — manual input + cancel */}
                            <div className="flex-shrink-0 bg-white border-top p-3">
                                <label className="form-label mb-1 text-muted" style={{ fontSize: '0.7rem', fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                                    Manual entry
                                </label>
                                <div className="input-group mb-2">
                                    <span className="input-group-text bg-light border-end-0">
                                        <i className="fa fa-barcode text-secondary" />
                                    </span>
                                    <input
                                        type="text"
                                        className="form-control border-start-0 bg-light"
                                        placeholder="Type barcode and press Enter"
                                        autoFocus
                                        style={{ fontSize: '0.95rem', height: 44 }}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter' && e.target.value.trim()) {
                                                const code = e.target.value.trim().replace(/[^a-zA-Z0-9]/g, '');
                                                setProductBarcode(code);
                                                applyScannedCode(code);
                                                closeScanner();
                                            }
                                        }}
                                    />
                                </div>
                                <button
                                    className="btn btn-outline-secondary w-100"
                                    onClick={closeScanner}
                                    style={{ height: 44, fontSize: '0.9rem' }}
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                );
            })()}
        </>
    );
}
