import React, { useState } from "react";

export default function ProductGrid({ products, onAddToCart }) {
    const [search, setSearch] = useState("");

    // Filter products by search (name or barcode)
    const filteredProducts = products.filter(p =>
        p.name.toLowerCase().includes(search.toLowerCase()) ||
        (p.barcode && p.barcode.toLowerCase().includes(search.toLowerCase()))
    );

    return (
        <div>
            {/* Search */}
            <div className="search-section mb-2">
                <div className="input-group">
                    <span className="input-group-text bg-light border-end-0">
                        <i className="fa fa-search"></i>
                    </span>
                    <input
                        type="search"
                        className="form-control border-start-0"
                        placeholder="Search Products/Barcode"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>
            </div>

            {/* Product Grid */}
            <div className="product-grid">
                {filteredProducts.length === 0 && <div>No products found</div>}
                {filteredProducts.map(product => (
                    <div
                        key={product.id}
                        className="product-card"
                        onClick={() => onAddToCart(product)}
                        style={{ cursor: "pointer" }}
                    >
                       <div className="product-image">
<img src={product.image || "/logo.png"} alt={product.name} />


</div>
                        <div className="product-content">
                            <h6 className="product-name">{product.name}</h6>
                            <div className="d-flex justify-content-between align-items-center">
                                <span className="product-price">₹{product.mrp}</span>
                                {product.type === "product" && (
                                    <span className="product-quantity">
                                        <i className="fa fa-cube me-1"></i>
                                        {product.quantity}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {/* Styles */}
            <style jsx>{`
                .product-grid {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 0.5rem;
                    padding: 0.5rem;
                }
                @media (max-width: 1200px) {
                    .product-grid {
                        grid-template-columns: repeat(3, 1fr);
                    }
                }
                @media (max-width: 768px) {
                    .product-grid {
                        grid-template-columns: repeat(2, 1fr);
                    }
                }
                @media (max-width: 576px) {
                    .product-grid {
                        grid-template-columns: repeat(1, 1fr);
                    }
                }
                .product-card {
                    background: white;
                    border: 1px solid rgba(0,0,0,.125);
                    border-radius: 0.5rem;
                    transition: all 0.2s ease-in-out;
                    height: 100%;
                }
                .product-card:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
                    border-color: #0d6efd;
                }
                .product-image {
                    position: relative;
                    padding-top: 75%;
                    border-top-left-radius: 0.5rem;
                    border-top-right-radius: 0.5rem;
                    background: #f8f9fa;
                    overflow: hidden;
                }
                .product-image img {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }
                .product-content {
                    padding: 1rem;
                }
                .product-name {
                    font-size: 0.875rem;
                    font-weight: 600;
                    color: #212529;
                    margin-bottom: 0.5rem;
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                    overflow: hidden;
                }
                .product-price {
                    font-size: 1rem;
                    font-weight: 700;
                    color: #0d6efd;
                }
                .product-quantity {
                    font-size: 0.875rem;
                    color: #6c757d;
                }
            `}</style>
        </div>
    );
}
