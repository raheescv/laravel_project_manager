import React from "react";

export default function ViewItemsModal({ items, onClose, onUpdate, onRemove, employee = null }) {
    const handleChange = (id, field, value) => {
        const item = items.find(i => i.id === id);
        const updated = {
            ...item,
            [field]: parseFloat(value),
            total: (field === "quantity" ? value : item.quantity) * (field === "unit_price" ? value : item.unit_price)
        };
        onUpdate(updated);
    };

    const totalQuantity = items.reduce((sum, i) => sum + i.quantity, 0);
    const totalDiscount = items.reduce((sum, i) => sum + i.discount, 0);
    const totalTax = items.reduce((sum, i) => sum + i.tax, 0);
    const totalAmount = items.reduce((sum, i) => sum + i.total, 0);

    return (
        <div className="modal fade show d-block">
            <div className="modal-dialog modal-xl">
                <div className="modal-content">
                    <div className="modal-header">
                        <div className="d-flex flex-column w-100">
                            <div className="d-flex align-items-center justify-content-between w-100">
                                <h5 className="mb-0">Cart Items</h5>
                                {employee && (
                                    <div className="ms-2">
                                        <div className="badge bg-primary text-white" style={{ fontSize: '0.9rem', padding: '0.45rem 0.7rem', borderRadius: 6 }}>
                                            {employee.name}
                                        </div>
                                    </div>
                                )}
                            </div>
                            {employee && employee.email && (
                                <small className="text-white-50 mt-1">{employee.email}</small>
                            )}
                        </div>
                        <button className="btn-close" onClick={onClose}></button>
                    </div>
                    <div className="modal-body">
                        <div className="table-responsive">
                            <table className="table table-striped">
                                <thead>
                                    <tr>
                                        <th>SL No</th>
                                        <th>Product</th>
                                        <th className="text-end">Unit Price</th>
                                        <th className="text-end">Quantity</th>
                                        <th className="text-end">Discount</th>
                                        <th className="text-end">Tax %</th>
                                        <th className="text-end">Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item, idx) => (
                                        <tr key={item.id}>
                                            <td>{idx + 1}</td>
                                            <td>{item.name}</td>
                                            <td>
                                                <input
                                                    type="number"
                                                    value={item.unit_price}
                                                    className="form-control form-control-sm"
                                                    onChange={e => handleChange(item.id, "unit_price", e.target.value)}
                                                />
                                            </td>
                                            <td>
                                                <input
                                                    type="number"
                                                    value={item.quantity}
                                                    className="form-control form-control-sm"
                                                    onChange={e => handleChange(item.id, "quantity", e.target.value)}
                                                />
                                            </td>
                                            <td>
                                                <input
                                                    type="number"
                                                    value={item.discount}
                                                    className="form-control form-control-sm"
                                                    onChange={e => handleChange(item.id, "discount", e.target.value)}
                                                />
                                            </td>
                                            <td>
                                                <input
                                                    type="number"
                                                    value={item.tax}
                                                    className="form-control form-control-sm"
                                                    onChange={e => handleChange(item.id, "tax", e.target.value)}
                                                />
                                            </td>
                                            <td className="text-end">₹{item.total.toFixed(2)}</td>
                                            <td>
                                                <button className="btn btn-sm btn-danger" onClick={() => onRemove(item.id)}>Remove</button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colSpan="3" className="text-end">Total</th>
                                        <th className="text-end">{totalQuantity}</th>
                                        <th className="text-end">{totalDiscount}</th>
                                        <th className="text-end">{totalTax}</th>
                                        <th className="text-end">₹{totalAmount.toFixed(2)}</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div className="modal-footer justify-content-end">
                        <button className="btn btn-secondary" onClick={onClose}>Cancel</button>
                        <button className="btn btn-primary"  onClick={onClose}>Submit</button>
                         
                    </div>
                </div>
            </div>
        </div>
    );
}
