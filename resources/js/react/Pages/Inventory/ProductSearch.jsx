import React, { useState, useRef } from 'react';
import { BarcodeScanner } from '@thewirv/react-barcode-scanner';
import axios from 'axios';

export default function ProductScanner({ onProductFound }) {
    const [barcode, setBarcode] = useState('');
    const [error, setError] = useState('');
    const [product, setProduct] = useState(null); // Added to display product
    const [scanning, setScanning] = useState(true);
    const scannerRef = useRef(null);

    const searchProduct = async (code) => {
        if (!code) {
            setError("Barcode is required");
            setProduct(null);
            return;
        }

        try {
            const res = await axios.get('/inventory/product/getProduct', { params: { barcode: code } });

            const productData = Array.isArray(res.data.data) ? res.data.data[0] : res.data.data;

            if (productData) {
                setProduct(productData);  // Show product locally
                setError('');
                if (onProductFound) onProductFound(productData); // Call parent if function exists
            } else {
                setError(`Product not found: ${code}`);
                setProduct(null);
            }
        } catch (err) {
            console.error(err);
            setError(err.response?.data?.message || 'Error searching barcode');
            setProduct(null);
        }
    };

    return (
        <div style={{ padding: '20px' }}>
            <h2 style={{ textAlign: 'center' }}>Scan Product</h2>

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
                            setScanning(false);
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

            {!scanning && (
                <button onClick={() => setScanning(true)} style={{ display: 'block', margin: '10px auto' }}>
                    🔄 Scan Again
                </button>
            )}

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

            {product && (
                <div style={{ marginTop: '20px', textAlign: 'center', border: '1px solid #ddd', padding: '10px', borderRadius: '8px' }}>
                    <h3>{product.name}</h3>
                    <p><strong>Code:</strong> {product.code}</p>
                    <p><strong>Barcode:</strong> {product.barcode}</p>
                    <p><strong>MRP:</strong> {product.mrp}</p>
                </div>
            )}
        </div>
    );
}
