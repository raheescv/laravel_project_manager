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

  // Data & pagination
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
  const [highlightedSKU, setHighlightedSKU] = useState('');
  const rowRefs = useRef({});

  const debounceRef = useRef(null);

  // --- Fetch products ---
  useEffect(() => {
    fetchProducts(page);
    // eslint-disable-next-line
  }, [page, limit, sortField, sortDirection]);

  useEffect(() => {
    clearTimeout(debounceRef.current);
    debounceRef.current = setTimeout(() => {
      setPage(1);
      fetchProducts(1);
    }, 250);
    return () => clearTimeout(debounceRef.current);
    // eslint-disable-next-line
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
        current_page: (links && links.current_page) || p,
        last_page: (links && links.last_page) || 1,
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
    setTimeout(() => fetchProducts(1), 10);
  }

  function goToPage(p) {
    if (p < 1 || p > meta.last_page) return;
    setPage(p);
    fetchProducts(p);
  }

  async function loadBranchOptions(inputValue) {
    try {
      const res = await axios.get('/settings/branch/list', { params: { query: inputValue } });
      const items = res.data.items || res.data || [];
      return items.map(i => ({ value: i.id, label: i.name || i.text || i.name }));
    } catch (err) {
      console.error('Load branches error', err);
      return [];
    }
  }

  function openScanner() {
    setScannerOpen(true);
    setScannedCode('');
  }

  function closeScanner() {
    setScannerOpen(false);
  }

  // --- Apply scanned barcode ---
  async function applyScannedCode(code) {
    if (!code) return;

    setScannedCode(code);
    setHighlightedSKU(code);
    setPage(1);

    try {
      const res = await axios.get('/inventory/product/getProduct', {
        params: {
          productBarcode: code,
          show_non_zero: showNonZeroOnly ? 1 : 0,
          limit,
          page: 1,
        },
      });

      const { data, total_quantity, links, per_page, total } = res.data;

      if (!data || data.length === 0) {
        showNotification(`❌ Barcode not found: ${code}`, 'danger');
        setScannedCode('');
        setHighlightedSKU('');
        return;
      }

      setProducts(data);
      setTotalQuantity(total_quantity || 0);
      setMeta({
        current_page: (links && links.current_page) || 1,
        last_page: (links && links.last_page) || 1,
        per_page: per_page || limit,
        total: total || 0,
      });

      setProductBarcode(code);
      setTimeout(() => setHighlightedSKU(''), 1500);
    } catch (err) {
      console.error('Barcode fetch error', err);
      showNotification('Error fetching product', 'danger');
      setScannedCode('');
      setHighlightedSKU('');
    }
  }

  function showNotification(message, type = 'info') {
    const el = document.createElement('div');
    el.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    el.style.cssText = 'top:20px; right:20px; z-index:9999; min-width:260px;';
    el.innerHTML = `${message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.body.appendChild(el);
    setTimeout(() => { if (el.parentNode) el.remove(); }, 3500);
  }

  function renderPagination() {
    const pages = [];
    const current = meta.current_page;
    const last = meta.last_page;
    if (last <= 1) return null;

    const totalPagesToShow = 10;
    let start = Math.max(1, current - Math.floor(totalPagesToShow / 2));
    let end = start + totalPagesToShow - 1;
    if (end > last) { end = last; start = Math.max(1, end - totalPagesToShow + 1); }

    if (current > 1) pages.push(<button key="prev" className="btn btn-sm btn-outline-primary me-1" onClick={() => goToPage(current - 1)}>Prev</button>);
    for (let p = start; p <= end; p++) pages.push(<button key={p} className={`btn btn-sm ${p === current ? 'btn-primary' : 'btn-outline-primary'} me-1`} onClick={() => goToPage(p)}>{p}</button>);
    if (current < last) pages.push(<button key="next" className="btn btn-sm btn-outline-primary" onClick={() => goToPage(current + 1)}>Next</button>);

    return <div className="d-flex flex-wrap">{pages}</div>;
  }

  return (
    <div className="card shadow-sm border-0">
      <div className="card-body bg-light">
        {/* Filters */}
        <div className="row g-3 align-items-end">
          <div className="col-md-4">
            <label className="form-label fw-semibold mb-2">Product Name</label>
            <input className="form-control" value={productName} onChange={e => { setProductName(e.target.value); setProductBarcode(''); }} placeholder="Enter product name..." />
          </div>
          <div className="col-md-2">
            <label className="form-label fw-semibold mb-2">Product Code</label>
            <input className="form-control" value={productCode} onChange={e => { setProductCode(e.target.value); setProductBarcode(''); }} placeholder="Enter code..." />
          </div>
          <div className="col-md-2">
            <label className="form-label fw-semibold mb-2">Product Barcode</label>
            <input className="form-control" value={productBarcode} onChange={e => setProductBarcode(e.target.value)} placeholder="Enter barcode..." />
          </div>
          <div className="col-md-2">
            <label className="form-label fw-semibold mb-2">Branch</label>
            <AsyncSelect isMulti cacheOptions defaultOptions loadOptions={loadBranchOptions} value={branchIds} onChange={vals => { setBranchIds(vals || []); setProductBarcode(''); }} placeholder="Select branch..." />
          </div>
          <div className="col-md-2">
            <label className="form-label fw-semibold mb-2">Stock</label>
            <div className="form-check form-switch">
              <input className="form-check-input" type="checkbox" checked={showNonZeroOnly} onChange={e => setShowNonZeroOnly(e.target.checked)} />
              <label className="form-check-label small">In stock only</label>
            </div>
          </div>
        </div>

        {/* Actions */}
        <div className="row mt-3">
          <div className="col-12 d-flex justify-content-between align-items-center">
            <div className="d-flex gap-2">
              <button className="btn btn-outline-secondary btn-sm" onClick={clearFilters}>Clear Filters</button>
              <button className="btn btn-outline-primary btn-sm" onClick={openScanner}>Quick Scan</button>
              {loading && <div className="spinner-border spinner-border-sm text-muted"></div>}
            </div>
            <div>
              <select className="form-select form-select-sm" value={limit} onChange={e => { setLimit(parseInt(e.target.value)); setPage(1); }}>
                <option value={10}>10</option><option value={25}>25</option><option value={50}>50</option><option value={100}>100</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      {/* Product Table */}
      <div className="card-body p-0">
        {products.length > 0 ? (
          <div className="table-responsive">
            <table className="table table-hover table-striped mb-0">
              <thead className="table-light">
                <tr>
                  <th onClick={() => changeSort('products.code')}>SKU</th>
                  <th onClick={() => changeSort('products.name')}>Name</th>
                  <th onClick={() => changeSort('products.size')}>Size</th>
                  <th onClick={() => changeSort('inventories.barcode')}>Barcode</th>
                  <th onClick={() => changeSort('products.mrp')}>Price</th>
                  <th onClick={() => changeSort('branches.name')}>Branch</th>
                  <th onClick={() => changeSort('inventories.quantity')}>QTY</th>
                </tr>
              </thead>
              <tbody>
                {products.map(item => (
                  <tr key={item.inventory_id} className={item.barcode === highlightedSKU ? 'table-success' : ''} ref={el => { if (el) rowRefs.current[item.barcode] = el }}>
                    <td>{item.code}</td>
                    <td>{item.name}</td>
                    <td>{item.size}</td>
                    <td>{item.barcode}</td>
                    <td>{item.mrp}</td>
                    <td>{item.branch_name}</td>
                    <td>{item.quantity}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="text-center p-3 text-muted">No products found.</div>
        )}
      </div>

      <div className="card-footer d-flex justify-content-between align-items-center">
        <small>Total: {totalQuantity}</small>
        {renderPagination()}
      </div>

      {/* Scanner Modal */}
      {scannerOpen && (
        <div className="scanner-modal position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-75 d-flex justify-content-center align-items-center">
          <div className="position-relative bg-white rounded p-2" style={{ width: '400px', maxWidth: '90%' }}>
            <div style={{ width: '100%', height: '300px', overflow: 'hidden' }}>
              <BarcodeScanner
                containerStyle={{ width: '100%', height: '100%' }}
                onSuccess={(text) => {
                  const clean = text.replace(/\s+/g, '');
                  if (clean.length >= 4 && clean.length <= 30) {
                    applyScannedCode(clean);
                    closeScanner();
                  }
                }}
                onError={(err) => console.error('Scanner error:', err)}
              />
            </div>
            <button className="btn btn-danger btn-sm mt-2 w-100" onClick={closeScanner}>Close Scanner</button>
          </div>
        </div>
      )}
    </div>
  );
}
