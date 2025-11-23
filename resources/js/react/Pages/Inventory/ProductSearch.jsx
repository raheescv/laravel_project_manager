// resources/js/Pages/Inventory/ProductSearch.jsx
import React, { useEffect, useState, useRef } from 'react';
import axios from 'axios';
import AsyncSelect from 'react-select/async';
import { BarcodeScanner } from '@thewirv/react-barcode-scanner';

export default function ProductSearch() {
  // Filters
  const [productName, setProductName] = useState('');
  const [productCode, setProductCode] = useState('');
  const [productBarcode, setProductBarcode] = useState('');
  const [branchIds, setBranchIds] = useState([]); // array of {value,label}
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
  const videoRef = useRef(null);
  const quaggaLoaded = useRef(false);

  // Highlighting
  const [highlightedSKU, setHighlightedSKU] = useState('');
  const rowRefs = useRef({});

  // debounce
  const debounceRef = useRef(null);

  // initial & dependencies fetch
  useEffect(() => {
    fetchProducts(1);
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
    fetchProducts(page);
  }, [page]);


  useEffect(() => {
    if (highlightedSKU && rowRefs.current[highlightedSKU]) {
      rowRefs.current[highlightedSKU].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }, [highlightedSKU, products]);

  async function fetchProducts(customPage = null) {
    setLoading(true);
    const p = customPage || page;
    try {
      // Only include productBarcode if non-empty — prevents blocking normal listing
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

  // ---------- applyScannedCode: check first, only replace list when barcode found ----------
async function applyScannedCode(code) {
  console.log("🚀 applyScannedCode() START", code);

  if (!code) {
    console.log("❌ applyScannedCode(): empty code");
    return;
  }

  setScannedCode(code);
  setHighlightedSKU(code);
  setPage(1);

  const checkParams = {
    productBarcode: code,
    branch_id: branchIds.map(b => b.value).join(','),
    show_non_zero: showNonZeroOnly ? 1 : 0,
    show_barcode_sku: showBarcodeCodes ? 1 : 0,
    limit: 1,
    page: 1,
    sortField,
    sortDirection,
  };

  console.log("📡 CHECKING BARCODE WITH API:", checkParams);

  try {
    const checkRes = await axios.get('/inventory/product/getProduct', { params: checkParams });

    console.log("📥 API CHECK RESPONSE:", checkRes.data);

    const found = Array.isArray(checkRes.data.data) && checkRes.data.data.length > 0;

    if (!found) {
      console.log("❌ NO PRODUCT FOUND FOR BARCODE:", code);
      showNotification(`❌ Barcode not found: ${code}`, 'danger');
      setScannedCode('');
      setHighlightedSKU('');
      return;
    }

    console.log("✅ BARCODE FOUND. Fetching full product list…");

    const resFull = await axios.get('/inventory/product/getProduct', {
      params: {
        productBarcode: code,
        branch_id: branchIds.map(b => b.value).join(','),
        show_non_zero: showNonZeroOnly ? 1 : 0,
        show_barcode_sku: showBarcodeCodes ? 1 : 0,
        limit,
        page: 1,
        sortField,
        sortDirection,
      },
    });

    console.log("📥 FULL DATA RESPONSE:", resFull.data);

    const { data, total_quantity, links, per_page, total } = resFull.data;

    setProducts(data || []);
    setTotalQuantity(total_quantity || 0);
    setMeta({
      current_page: (links && links.current_page) || 1,
      last_page: (links && links.last_page) || 1,
      per_page: per_page || limit,
      total: total || 0,
    });

    setProductBarcode(code);

    setTimeout(() => setHighlightedSKU(''), 1500);

    console.log("🎯 applyScannedCode() DONE");
  } catch (err) {
    console.error("🔥 ERROR IN applyScannedCode():", err);
    showNotification("Error checking barcode", "danger");
    setScannedCode('');
    setHighlightedSKU('');
  }
}

 function onScan(result) {
  console.log("📸 SCANNER RESULT RAW:", result);

  if (!result?.rawValue) {
    console.log("❌ No rawValue from scanner");
    return;
  }

  const code = result.rawValue;
  console.log("📦 RAW BARCODE:", code);

  const clean = code.replace(/[^a-zA-Z0-9]/g, '');
  console.log("🔎 CLEAN BARCODE:", clean);

  if (clean.length >= 4 && clean.length <= 30) {
    console.log("✅ Valid barcode scanned:", clean);
    applyScannedCode(clean);
    closeScanner();
  } else {
    console.log("❌ Barcode invalid length:", clean.length);
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

    if (last <= 1) return null; // no pagination if only 1 page

    const totalPagesToShow = 10; // maximum buttons we show
    let start = Math.max(1, current - Math.floor(totalPagesToShow / 2));
    let end = start + totalPagesToShow - 1;

    if (end > last) {
      end = last;
      start = Math.max(1, end - totalPagesToShow + 1);
    }

    // Previous button
    if (current > 1) {
      pages.push(
        <button key="prev" className="btn btn-sm btn-outline-primary me-1" onClick={() => goToPage(current - 1)}>
          Prev
        </button>
      );
    }

    // Page buttons
    for (let p = start; p <= end; p++) {
      pages.push(
        <button
          key={p}
          className={`btn btn-sm ${p === current ? 'btn-primary' : 'btn-outline-primary'} me-1`}
          onClick={() => goToPage(p)}
        >
          {p}
        </button>
      );
    }

    // Next button
    if (current < last) {
      pages.push(
        <button key="next" className="btn btn-sm btn-outline-primary" onClick={() => goToPage(current + 1)}>
          Next
        </button>
      );
    }

    return <div className="d-flex flex-wrap">{pages}</div>;
  }



  return (
    <div className="card shadow-sm border-0">
      <div className="card-body bg-light">
        {/* Filters */}
        <div className="row g-3 align-items-end">
          {/* Product Name */}
          <div className="col-md-4">
            <label className="form-label fw-semibold mb-2">Product Name</label>
            <div className="input-group">
              <span className="input-group-text bg-white border-end-0"><i className="fa fa-tag text-muted" /></span>
              <input className="form-control border-start-0" value={productName} onChange={(e) => { setProductName(e.target.value); setProductBarcode(''); }} placeholder="Enter product name..." />
            </div>
          </div>

          {/* Product Code */}
          <div className="col-md-2">
            <label className="form-label fw-semibold mb-2">Product Code</label>
            <div className="input-group">
              <span className="input-group-text bg-white border-end-0"><i className="fa fa-barcode text-muted" /></span>
              <input className="form-control border-start-0" value={productCode} onChange={(e) => { setProductCode(e.target.value); setProductBarcode(''); }} placeholder="Enter code..." />
            </div>
          </div>

          {/* Product Barcode */}
          <div className="col-md-2">
            <label className="form-label fw-semibold mb-2">Product Barcode</label>
            <div className="input-group">
              <span className="input-group-text bg-white border-end-0"><i className="fa fa-barcode text-muted" /></span>
              <input id="productBarcodeInput" className="form-control border-start-0 barcode-input" value={productBarcode} onChange={(e) => setProductBarcode(e.target.value)} placeholder="Enter barcode..." />
            </div>
          </div>

          {/* Branch */}
          <div className="col-md-2">
            <label className="form-label fw-semibold mb-2">Branch</label>
            <AsyncSelect isMulti cacheOptions defaultOptions loadOptions={loadBranchOptions} value={branchIds} onChange={(vals) => { setBranchIds(vals || []); setProductBarcode(''); }} placeholder="Select branch..." />
          </div>

          {/* Stock */}
          <div className="col-md-2">
            <label className="form-label fw-semibold mb-2">Stock</label>
            <div className="form-check form-switch">
              <input className="form-check-input" type="checkbox" checked={showNonZeroOnly} onChange={(e) => setShowNonZeroOnly(e.target.checked)} id="showNonZeroOnly" />
              <label className="form-check-label small" htmlFor="showNonZeroOnly">In stock only</label>
            </div>
          </div>

          {/* Barcode SKU */}
          <div className="col-md-2">
            <label className="form-label fw-semibold mb-2">Barcode SKU</label>
            <div className="form-check form-switch">
              <input className="form-check-input" type="checkbox" checked={showBarcodeCodes} onChange={(e) => setShowBarcodeCodes(e.target.checked)} id="showBarcodeCodes" />
              <label className="form-check-label small" htmlFor="showBarcodeCodes">Show barcode SKU</label>
            </div>
          </div>
        </div>

        {/* Actions */}
        <div className="row mt-3">
          <div className="col-12 d-flex justify-content-between align-items-center">
            <div className="d-flex gap-2">
              <button className="btn btn-outline-secondary btn-sm" onClick={clearFilters}><i className="fa fa-times me-1"></i> Clear Filters</button>
              <button className="btn btn-outline-primary btn-sm" onClick={openScanner}><i className="fa fa-camera me-1"></i> Quick Scan</button>
              {loading && <div className="d-flex align-items-center text-muted"><div className="spinner-border spinner-border-sm me-2" role="status" /><small>Searching...</small></div>}
            </div>
            <div className="d-flex gap-2 align-items-center">
              <label className="form-label mb-0 me-2 small">Show:</label>
              <select className="form-select form-select-sm" style={{ width: 'auto' }} value={limit} onChange={(e) => { setLimit(parseInt(e.target.value)); setPage(1); }}>
                <option value={10}>10</option>
                <option value={25}>25</option>
                <option value={50}>50</option>
                <option value={100}>100</option>
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
                  <th className="border-0 text-end" style={{ cursor: 'pointer' }} onClick={() => changeSort('products.code')}><i className="fa fa-barcode me-1 text-muted" />SKU {sortField === 'products.code' ? (sortDirection === 'asc' ? '▲' : '▼') : null}</th>
                  <th className="border-0" style={{ cursor: 'pointer' }} onClick={() => changeSort('products.name')}><i className="fa fa-tag me-1 text-muted" />Name {sortField === 'products.name' ? (sortDirection === 'asc' ? '▲' : '▼') : null}</th>
                  <th className="border-0 text-end" style={{ cursor: 'pointer' }} onClick={() => changeSort('products.size')}><i className="fa fa-ruler me-1 text-muted" />Size {sortField === 'products.size' ? (sortDirection === 'asc' ? '▲' : '▼') : null}</th>
                  <th className="border-0 text-end" style={{ cursor: 'pointer' }} onClick={() => changeSort('inventories.barcode')}><i className="fa fa-barcode me-1 text-muted" />Barcode {sortField === 'inventories.barcode' ? (sortDirection === 'asc' ? '▲' : '▼') : null}</th>
                  <th className="border-0 text-end" style={{ cursor: 'pointer' }} onClick={() => changeSort('products.mrp')}><i className="fa fa-money me-1 text-muted" />Price {sortField === 'products.mrp' ? (sortDirection === 'asc' ? '▲' : '▼') : null}</th>
                  <th className="border-0" style={{ cursor: 'pointer' }} onClick={() => changeSort('branches.name')}><i className="fa fa-building me-1 text-muted" />Br {sortField === 'branches.name' ? (sortDirection === 'asc' ? '▲' : '▼') : null}</th>
                  <th className="border-0 text-end" style={{ cursor: 'pointer' }} onClick={() => changeSort('inventories.quantity')}><i className="fa fa-cubes me-1 text-muted" />QTY {sortField === 'inventories.quantity' ? (sortDirection === 'asc' ? '▲' : '▼') : null}</th>
                </tr>
              </thead>

              <tbody>
                {products.map(item => (
                  <tr
                    className={`align-middle ${item.barcode === highlightedSKU ? 'table-success' : ''}`}
                    key={item.inventory_id}
                    ref={el => { if (el) rowRefs.current[item.barcode] = el }}
                  >
                    <td className="text-end"><code className="text-primary">{item.code}</code></td>
                    <td>{item.name}</td>
                    <td className="text-end"><code className="text-primary">{item.size}</code></td>
                    <td className="text-end"><code className="text-primary">{item.barcode}</code></td>
                    <td className="text-end"><code className="text-primary">{item.mrp}</code></td>
                    <td><span className="fw-medium">{item.branch_name}</span></td>
                    <td className="text-end">
                      <span className={`badge ${item.quantity > 0 ? 'bg-success' : 'bg-danger'} fs-6`}>{item.quantity}</span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="text-center p-3 text-muted">No products found.</div>
        )}
      </div>

      {/* Pagination */}
      <div className="card-footer d-flex justify-content-between align-items-center">
        <small>Total: <strong>{totalQuantity}</strong></small>
        {renderPagination()}
      </div>

      {/* Scanner Modal */}
      {/* Scanner Modal */}
{scannerOpen && (
  <div className="scanner-modal position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-75 d-flex justify-content-center align-items-center zindex-tooltip">
    <div className="position-relative bg-white rounded p-2" style={{ width: '400px', maxWidth: '90%' }}>
      
      {/* FIXED BARCODE SCANNER */}
      <div style={{ width: '100%', height: '300px', overflow: 'hidden' }}>
        <BarcodeScanner
          key={scannerOpen ? 'open' : 'closed'} // forces re-mount on open
          onCapture={onScan}
          onError={(err) => console.error('Scanner error', err)}
          fps={30}
          qrbox={250} // scanning area
          disableFlip={false}
          constraints={{ facingMode: 'environment' }} // use rear camera
        />
      </div>

      <button className="btn btn-danger btn-sm mt-2 w-100" onClick={closeScanner}>
        Close Scanner
      </button>
    </div>
  </div>
)}

    </div>
  );
}