import React, { useEffect, useState, useRef } from 'react';
import axios from 'axios';
import AsyncSelect from 'react-select/async';
import { BarcodeScanner } from '@thewirv/react-barcode-scanner';

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

    // Fetch on page/sort changes
    useEffect(() => {
        fetchProducts(page);
    }, [page, sortField, sortDirection]);

    // Debounced fetch on filter changes
    useEffect(() => {
        clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            setPage(1);
            fetchProducts(1);
        }, 250);
        return () => clearTimeout(debounceRef.current);
    }, [productName, productCode, productBarcode, branchIds, showNonZeroOnly, showBarcodeCodes, limit]);

    // Scroll to highlighted row
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
        if (sortField !== field) return null;
        return sortDirection === 'asc' ? ' \u25B2' : ' \u25BC';
    }

    function renderPagination() {
        const { current_page: current, last_page: last } = meta;
        if (last <= 1) return null;

        const pages = [];
        const maxButtons = 5;
        let start = Math.max(1, current - Math.floor(maxButtons / 2));
        let end = Math.min(last, start + maxButtons - 1);
        if (end - start < maxButtons - 1) {
            start = Math.max(1, end - maxButtons + 1);
        }

        if (current > 1) {
            pages.push(
                <button key="prev" className="btn btn-sm btn-outline-primary me-1" onClick={() => goToPage(current - 1)}>
                    <i className="fa fa-chevron-left" />
                </button>
            );
        }

        for (let p = start; p <= end; p++) {
            pages.push(
                <button
                    key={p}
                    className={`btn btn-sm me-1 ${p === current ? 'btn-primary' : 'btn-outline-primary'}`}
                    onClick={() => goToPage(p)}
                >
                    {p}
                </button>
            );
        }

        if (current < last) {
            pages.push(
                <button key="next" className="btn btn-sm btn-outline-primary" onClick={() => goToPage(current + 1)}>
                    <i className="fa fa-chevron-right" />
                </button>
            );
        }

        return <div className="d-flex flex-wrap gap-1">{pages}</div>;
    }

    const sortableHeader = (field, label, icon, align = '') => (
        <th
            className={`border-0 ${align} user-select-none`}
            style={{ cursor: 'pointer', whiteSpace: 'nowrap', fontSize: '0.8rem' }}
            onClick={() => changeSort(field)}
        >
            <i className={`fa fa-${icon} me-1 text-muted`} />
            <span className="d-none d-md-inline">{label}</span>
            {renderSortIcon(field)}
        </th>
    );

    // Mobile card view for each product row
    function renderMobileCards() {
        return (
            <div className="d-md-none p-2">
                {products.map(item => (
                    <div
                        key={item.inventory_id}
                        className={`card mb-2 ${item.barcode === highlightedSKU ? 'border-success bg-success bg-opacity-10' : ''}`}
                        ref={el => { if (el) rowRefs.current[item.barcode] = el; }}
                    >
                        <div className="card-body p-2">
                            <div className="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <span className="fw-bold">{item.name}</span>
                                    <br />
                                    <small className="text-muted">
                                        <i className="fa fa-barcode me-1" />{item.code}
                                        {item.size && <span className="ms-2"><i className="fa fa-arrows-h me-1" />{item.size}</span>}
                                    </small>
                                </div>
                                <span className={`badge ${item.quantity > 0 ? 'bg-success' : 'bg-danger'} fs-6`}>
                                    {item.quantity}
                                </span>
                            </div>
                            <div className="d-flex justify-content-between align-items-center">
                                <small className="text-muted">
                                    {item.barcode && <span><i className="fa fa-qrcode me-1" />{item.barcode}</span>}
                                </small>
                                <small>
                                    <span className="text-muted me-2"><i className="fa fa-money me-1" />{item.mrp}</span>
                                    <span className="text-muted"><i className="fa fa-building me-1" />{item.branch_name}</span>
                                </small>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        );
    }

    return (
        <div className="card shadow-sm border-0">
            {/* Filters */}
            <div className="card-body bg-light pb-2">
                <div className="row g-2 g-md-3 align-items-end">
                    <div className="col-12 col-sm-6 col-md-3">
                        <label className="form-label fw-semibold mb-1 small">
                            <i className="fa fa-tag me-1 text-muted" />Product Name
                        </label>
                        <div className="input-group input-group-sm">
                            <span className="input-group-text bg-white border-end-0"><i className="fa fa-search text-muted" /></span>
                            <input
                                className="form-control border-start-0"
                                value={productName}
                                onChange={(e) => { setProductName(e.target.value); setProductBarcode(''); }}
                                placeholder="Search by name..."
                            />
                        </div>
                    </div>

                    <div className="col-6 col-sm-6 col-md-2">
                        <label className="form-label fw-semibold mb-1 small">
                            <i className="fa fa-code me-1 text-muted" />Product Code
                        </label>
                        <div className="input-group input-group-sm">
                            <span className="input-group-text bg-white border-end-0"><i className="fa fa-code text-muted" /></span>
                            <input
                                className="form-control border-start-0"
                                value={productCode}
                                onChange={(e) => { setProductCode(e.target.value); setProductBarcode(''); }}
                                placeholder="Code..."
                            />
                        </div>
                    </div>

                    <div className="col-6 col-sm-6 col-md-2">
                        <label className="form-label fw-semibold mb-1 small">
                            <i className="fa fa-barcode me-1 text-muted" />Barcode
                        </label>
                        <div className="input-group input-group-sm">
                            <span className="input-group-text bg-white border-end-0"><i className="fa fa-barcode text-muted" /></span>
                            <input
                                id="productBarcodeInput"
                                className="form-control border-start-0 barcode-input"
                                value={productBarcode}
                                onChange={(e) => setProductBarcode(e.target.value)}
                                placeholder="Barcode..."
                            />
                        </div>
                    </div>

                    <div className="col-12 col-sm-6 col-md-3">
                        <label className="form-label fw-semibold mb-1 small">
                            <i className="fa fa-building me-1 text-muted" />Branch
                        </label>
                        <AsyncSelect
                            isMulti
                            cacheOptions
                            defaultOptions
                            loadOptions={loadBranchOptions}
                            value={branchIds}
                            onChange={(vals) => { setBranchIds(vals || []); setProductBarcode(''); }}
                            placeholder="Select branch..."
                            styles={{
                                control: (base) => ({ ...base, minHeight: '31px', fontSize: '0.875rem' }),
                                valueContainer: (base) => ({ ...base, padding: '0 6px' }),
                                indicatorsContainer: (base) => ({ ...base, height: '31px' }),
                            }}
                        />
                    </div>

                    <div className="col-12 col-sm-6 col-md-2 d-flex flex-row flex-md-column gap-2 gap-md-1">
                        <div className="form-check form-switch mb-0">
                            <input className="form-check-input" type="checkbox" checked={showNonZeroOnly} onChange={(e) => setShowNonZeroOnly(e.target.checked)} id="showNonZeroOnly" />
                            <label className="form-check-label small" htmlFor="showNonZeroOnly">
                                <i className="fa fa-cubes me-1 text-muted" />In stock
                            </label>
                        </div>
                        <div className="form-check form-switch mb-0">
                            <input className="form-check-input" type="checkbox" checked={showBarcodeCodes} onChange={(e) => setShowBarcodeCodes(e.target.checked)} id="showBarcodeCodes" />
                            <label className="form-check-label small" htmlFor="showBarcodeCodes">
                                <i className="fa fa-qrcode me-1 text-muted" />Barcode SKU
                            </label>
                        </div>
                    </div>
                </div>

                {/* Actions Row */}
                <div className="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-2 border-top gap-2">
                    <div className="d-flex gap-2 align-items-center">
                        <button className="btn btn-outline-secondary btn-sm" onClick={clearFilters}>
                            <i className="fa fa-times me-1" /> Clear
                        </button>
                        <button className="btn btn-outline-primary btn-sm" onClick={() => { setScannerOpen(true); setScannedCode(''); }}>
                            <i className="fa fa-camera me-1" /> Scan
                        </button>
                        {loading && (
                            <span className="text-muted small ms-2">
                                <span className="spinner-border spinner-border-sm me-1" role="status" />
                                Searching...
                            </span>
                        )}
                    </div>
                    <div className="d-flex align-items-center gap-2">
                        <small className="text-muted d-none d-sm-inline">Per page:</small>
                        <select
                            className="form-select form-select-sm"
                            style={{ width: '70px' }}
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

            {/* Product Table (desktop) / Cards (mobile) */}
            <div className="card-body p-0">
                {products.length > 0 ? (
                    <>
                        {/* Desktop table */}
                        <div className="table-responsive d-none d-md-block">
                            <table className="table table-hover table-striped mb-0 align-middle">
                                <thead className="table-light">
                                    <tr>
                                        {sortableHeader('products.code', 'SKU', 'barcode', 'text-end')}
                                        {sortableHeader('products.name', 'Name', 'tag')}
                                        {sortableHeader('products.size', 'Size', 'arrows-h', 'text-end')}
                                        {sortableHeader('inventories.barcode', 'Barcode', 'barcode', 'text-end')}
                                        {sortableHeader('products.mrp', 'Price', 'money', 'text-end')}
                                        {sortableHeader('branches.name', 'Branch', 'building')}
                                        {sortableHeader('inventories.quantity', 'QTY', 'cubes', 'text-end')}
                                    </tr>
                                </thead>
                                <tbody>
                                    {products.map(item => (
                                        <tr
                                            key={item.inventory_id}
                                            className={item.barcode === highlightedSKU ? 'table-success' : ''}
                                            ref={el => { if (el) rowRefs.current[item.barcode] = el; }}
                                        >
                                            <td className="text-end"><code className="text-primary">{item.code}</code></td>
                                            <td>{item.name}</td>
                                            <td className="text-end"><code className="text-primary">{item.size}</code></td>
                                            <td className="text-end"><code className="text-primary">{item.barcode}</code></td>
                                            <td className="text-end"><code className="text-primary">{item.mrp}</code></td>
                                            <td><span className="fw-medium">{item.branch_name}</span></td>
                                            <td className="text-end">
                                                <span className={`badge fs-6 ${item.quantity > 0 ? 'bg-success' : 'bg-danger'}`}>
                                                    {item.quantity}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Mobile cards */}
                        {renderMobileCards()}
                    </>
                ) : (
                    <div className="text-center py-5 text-muted">
                        <i className="fa fa-search fa-2x mb-2 d-block" style={{ opacity: 0.3 }} />
                        No products found.
                    </div>
                )}
            </div>

            {/* Footer: Total + Pagination */}
            <div className="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small className="text-muted">
                    <i className="fa fa-cubes me-1" />
                    Total Qty: <strong className="text-dark">{totalQuantity}</strong>
                    <span className="mx-2">|</span>
                    {meta.total} items
                </small>
                {renderPagination()}
            </div>

            {/* Scanner Modal */}
            {scannerOpen && (
                <div
                    className="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center"
                    style={{ backgroundColor: 'rgba(0,0,0,0.75)', zIndex: 9999 }}
                >
                    <div className="bg-white rounded-3 shadow-lg p-3" style={{ width: '400px', maxWidth: '92vw' }}>
                        <div className="d-flex justify-content-between align-items-center mb-2">
                            <h6 className="mb-0"><i className="fa fa-camera me-2" />Barcode Scanner</h6>
                            <button className="btn-close" onClick={() => setScannerOpen(false)} />
                        </div>

                        <div className="position-relative rounded overflow-hidden" style={{ height: '280px' }}>
                            <BarcodeScanner
                                containerStyle={{ width: '100%', height: '100%' }}
                                onSuccess={(text) => {
                                    setProductBarcode(text);
                                    applyScannedCode(text);
                                    setScannerOpen(false);
                                }}
                                onError={(err) => console.error(err)}
                            />
                            <div
                                className="position-absolute top-0 start-0 w-100 h-100"
                                style={{ border: '2px dashed rgba(255,0,0,0.5)', pointerEvents: 'none' }}
                            />
                        </div>

                        <div className="mt-3">
                            <label className="form-label small text-muted mb-1">
                                <i className="fa fa-pencil me-1" />Or enter manually
                            </label>
                            <input
                                type="text"
                                className="form-control form-control-sm"
                                placeholder="Type barcode and press Enter"
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

                        <button className="btn btn-danger btn-sm mt-3 w-100" onClick={() => setScannerOpen(false)}>
                            <i className="fa fa-times me-1" /> Close
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
