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
                                        <th>Category</th>
                                        <th>Model</th>
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
                                            <td>{item.category_name ? item.category_name : (item.category_id ? `ID: ${item.category_id}` : '-')}</td>
                                            <td>{item.sub_category_name ? item.sub_category_name : (item.sub_category_id ? `ID: ${item.sub_category_id}` : '-')}</td>
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

export function EditItemModal({ item, onSave }) {
    if (!item) return null;

    function update(key, value) {
        onSave({ ...item, [key]: value });
    }

    return (
        <div className="modal fade show d-block">
            <div className="modal-dialog">
                <div className="modal-content">
                    <div className="modal-header">
                        <h5>Edit Item</h5>
                    </div>

                    <div className="modal-body">
                        <div className="mb-2">
                            <label className="form-label">Product</label>
                            <input className="form-control" value={item.name} readOnly />
                        </div>
                        <div className="mb-2">
                            <label className="form-label">Category</label>
                            <input className="form-control" value={item.category_name || (item.category_id ? `ID: ${item.category_id}` : '')} readOnly />
                        </div>
                        <div className="mb-2">
                            <label className="form-label">Model</label>
                            <input className="form-control" value={item.sub_category_name || (item.sub_category_id ? `ID: ${item.sub_category_id}` : '')} readOnly />
                        </div>
                        <input
                            className="form-control mt-2"
                            value={item.unit_price}
                            onChange={e => update("unit_price", e.target.value)}
                        />
                        <input
                            className="form-control mt-2"
                            value={item.discount}
                            onChange={e => update("discount", e.target.value)}
                        />
                        <input
                            className="form-control mt-2"
                            value={item.quantity}
                            onChange={e => update("quantity", e.target.value)}
                        />
                    </div>

                    <div className="modal-footer">
                        <button className="btn btn-primary" onClick={() => onSave(item)}>
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
