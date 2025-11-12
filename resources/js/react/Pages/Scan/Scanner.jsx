import React, { useState } from 'react';
import { BarcodeScanner } from '@thewirv/react-barcode-scanner';
import axios from 'axios';

export default function Scanner() {
    const [barcode, setBarcode] = useState('');
    const [product, setProduct] = useState(null);
    const [error, setError] = useState('');

    const searchProduct = async (code) => {
        try {
            const res = await axios.post('/scan/search', { barcode: code });
            setProduct(res.data);
            setError('');
        } catch (err) {
            setProduct(null);
            setError(err.response?.data?.message || 'Error');
        }
    };

    return (
        <div style={{ padding: '20px' }}>
            <h1 style={{ textAlign: 'center' }}>Scan Product</h1>

            <div style={{
                display: 'flex',
                justifyContent: 'center',
                margin: '20px 0'
            }}>
                <div style={{
                    width: '100%',
                    maxWidth: '500px',
                    border: '2px solid #333',
                    borderRadius: '8px',
                    overflow: 'hidden',
                    position: 'relative'
                }}>
                    <BarcodeScanner
                        containerStyle={{ width: '100%', height: '400px' }}
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
                        boxSizing: 'border-box',
                        pointerEvents: 'none'
                    }}></div>
                </div>
            </div>

            {!barcode && <p style={{ textAlign: 'center', marginTop: '10px' }}>🔴 Scanning...</p>}
            

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

            {error && <p style={{ color: 'red', textAlign: 'center', marginTop: '10px' }}>{error}</p>}
            {product && (
                <div style={{ marginTop: '20px', textAlign: 'center' }}>
                    <h2>{product.name}</h2>
                    <p>Code: {product.code}</p>
                    <p>Barcode: {product.barcode}</p>
                </div>
            )}
        </div>
    );
}
