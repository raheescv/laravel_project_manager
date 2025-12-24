import React from "react";

export default function CartSidebar({
    items,
    onIncrease,
    onDecrease,
    onRemove,
    onView,
    onDeleteAll
}) {
    const totalQty = items.reduce((s, i) => s + i.quantity, 0);

    return (
        <aside className="product-order-list">
            <div className="product-added block-section">
                <div className="cart-summary mb-3">
                    <div className="d-flex align-items-center justify-content-between bg-white rounded-lg p-3 shadow-sm">
                        <div className="d-flex align-items-center gap-3">
                            <span className="cart-badge">{totalQty}</span>
                            <div>
                                <h6 className="mb-0 text-dark">Cart Items</h6>
                                <small className="text-muted">
                                    {totalQty} items in cart
                                </small>
                            </div>
                        </div>

                        {totalQty > 0 && (
                            <div className="action-group d-flex gap-2">
                                <div className="d-flex flex-column align-items-center">
                                    <button
                                        type="button"
                                        className="action-btn view-btn"
                                        onClick={onView}
                                    >
                                        <i className="fa fa-list"></i>
                                    </button>
                                    <small>View</small>
                                </div>

                                <div className="d-flex flex-column align-items-center">
                                    <button
                                        type="button"
                                        className="action-btn delete-btn"
                                        onClick={onDeleteAll}
                                    >
                                        <i className="fa fa-trash"></i>
                                    </button>
                                    <small>Delete</small>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                <div className="product-wrap">
                    {items.map((item, idx) => (
                        <div
                            key={item.id}
                            className={`product-list d-flex align-items-center justify-content-between ${
                                idx % 2 ? "bg-custom-gray" : ""
                            }`}
                        >
                            <div className="product-info">
                                <h6>{item.name}</h6>
                                <p className="text-success">₹{item.total}</p>
                            </div>

                            <div className="qty-item">
                                <i
                                    className="fa fa-minus-circle dec hover-lift"
                                    onClick={() => onDecrease(item.id)}
                                ></i>

                                <input
                                    type="text"
                                    className="form-control text-center"
                                    value={item.quantity}
                                    readOnly
                                />

                                <i
                                    className="fa fa-plus-circle inc hover-lift"
                                    onClick={() => onIncrease(item.id)}
                                ></i>
                            </div>

                            <div className="action">
                                <i
                                    className="fa fa-trash pointer text-danger hover-lift"
                                    onClick={() => onRemove(item.id)}
                                ></i>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </aside>
    );
}
