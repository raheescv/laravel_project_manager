import React, { useState, useRef } from 'react';
import { BarcodeScanner } from '@thewirv/react-barcode-scanner';
import axios from 'axios';

export default function ProductScanner() {
  const [barcode, setBarcode] = useState('');
  const [error, setError] = useState('');
  const [scanning, setScanning] = useState(true);
  const [products, setProducts] = useState([]); // store scanned products
  const scannerRef = useRef(null);

  const searchProduct = async (code) => {
    try {
      if (!code) return;
      const res = await axios.get('/inventory/product/getProduct', { params: { productBarcode: code } });
      if (res.data.data?.length > 0) {
        const product = res.data.data[0];
        setProducts(prev => {
          // prevent duplicates
          if (!prev.find(p => p.barcode === product.barcode)) {
            return [...prev, product];
          }
          return prev;
        });
        setError('');
      } else {
        setError(`Product not found: ${code}`);
      }
    } catch (err) {
      console.error(err);
      setError(err.response?.data?.message || 'Error searching barcode');
    }
  };

  return (
    <div style={{ padding: '20px' }}>
      <h2 style={{ textAlign: 'center' }}>Scan Products</h2>

      {scanning && (
        <div style={{
          width: '100%',
          maxWidth: '500px',
          margin: '20px auto',
          position: 'relative',
          border: '2px solid #333',
          borderRadius: '8px',
          overflow: 'hidden'
        }}>
          <BarcodeScanner
            containerStyle={{ width: '100%', height: '300px' }}
            onSuccess={(text) => {
              setBarcode(text);
              searchProduct(text);
            }}
            onError={(err) => console.error(err)}
          />
          <div style={{
            position: 'absolute',
            top: 0,
            left: 0,
            width: '100%',
            height: '100%',
            border: '2px dashed red',
            pointerEvents: 'none'
          }}></div>
        </div>
      )}

      <button onClick={() => setScanning(!scanning)} style={{ display: 'block', margin: '10px auto' }}>
        {scanning ? 'Stop Scanning' : 'Scan Again'}
      </button>

      <input
        type="text"
        value={barcode}
        onChange={e => setBarcode(e.target.value)}
        placeholder="Enter barcode manually"
        style={{ padding: '8px', width: '100%', maxWidth: '500px', display: 'block', margin: '10px auto' }}
      />
      <button onClick={() => searchProduct(barcode)} style={{ display: 'block', margin: '10px auto' }}>
        Search
      </button>

      {error && <p style={{ color: 'red', textAlign: 'center' }}>{error}</p>}

      {/* Display scanned products */}
      {products.length > 0 && (
        <table style={{ width: '100%', maxWidth: '700px', margin: '20px auto', borderCollapse: 'collapse' }}>
          <thead>
            <tr>
              <th style={{ border: '1px solid #ccc', padding: '8px' }}>Name</th>
              <th style={{ border: '1px solid #ccc', padding: '8px' }}>Code</th>
              <th style={{ border: '1px solid #ccc', padding: '8px' }}>Barcode</th>
              <th style={{ border: '1px solid #ccc', padding: '8px' }}>Price</th>
            </tr>
          </thead>
          <tbody>
            {products.map(p => (
              <tr key={p.barcode}>
                <td style={{ border: '1px solid #ccc', padding: '8px' }}>{p.name}</td>
                <td style={{ border: '1px solid #ccc', padding: '8px' }}>{p.code}</td>
                <td style={{ border: '1px solid #ccc', padding: '8px' }}>{p.barcode}</td>
                <td style={{ border: '1px solid #ccc', padding: '8px' }}>{p.mrp}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}
