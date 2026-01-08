import React, { useState } from "react";

export default function AdvanceeditPaymentModal({ open, onClose, grandTotal, onSave }) {
    const [payments, setPayments] = useState([{ method: "cash", amount: "" }]);

    if (!open) return null;

    // ✅ Define payment method map
    const PAYMENT_METHOD_MAP = {
        cash: 1,
        card: 2,
    };

    const totalPaid = payments.reduce((sum, p) => sum + Number(p.amount || 0), 0);
    const balance = grandTotal - totalPaid;

    const addRow = () => setPayments([...payments, { method: "cash", amount: "" }]);
    const updateRow = (index, key, value) => {
        const copy = [...payments];
        copy[index][key] = value;
        setPayments(copy);
    };
    const removeRow = (index) => setPayments(payments.filter((_, i) => i !== index));

    const savePayment = () => {
        if (totalPaid <= 0) {
            alert("Please enter payment amount");
            return;
        }

        // ✅ Transform payments to backend format
        const formattedPayments = payments
            .filter(p => Number(p.amount) > 0)
            .map(p => ({
                payment_method_id: PAYMENT_METHOD_MAP[p.method],
                amount: Number(p.amount),
            }));

        onSave({
            payments: formattedPayments,
            totalPaid,
            balanceDue: balance,
        });

        onClose();
    };

    return (
        <div className="modal fade show d-block" style={{ background: "rgba(0,0,0,.5)" }}>
            <div className="modal-dialog modal-xl">
                <div className="modal-content">
                    <div className="modal-header">
                        <h5>Advance / Custom Payment</h5>
                        <button className="btn-close" onClick={onClose}></button>
                    </div>

                    <div className="modal-body">
                        <div className="row mb-3">
                            <div className="col-md-4">
                                <label>Total Amount</label>
                                <input className="form-control" value={grandTotal} disabled />
                            </div>
                            <div className="col-md-4">
                                <label>Paid</label>
                                <input className="form-control text-success" value={totalPaid} disabled />
                            </div>
                            <div className="col-md-4">
                                <label>Balance</label>
                                <input className="form-control text-danger" value={balance} disabled />
                            </div>
                        </div>

                        <table className="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Payment Mode</th>
                                    <th>Amount</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                {payments.map((row, i) => (
                                    <tr key={i}>
                                        <td>
                                            <select
                                                className="form-select"
                                                value={row.method}
                                                onChange={(e) => updateRow(i, "method", e.target.value)}
                                            >
                                                <option value="cash">Cash</option>
                                                <option value="card">Card</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                className="form-control"
                                                value={row.amount}
                                                onChange={(e) => updateRow(i, "amount", e.target.value)}
                                            />
                                        </td>
                                        <td>
                                            {payments.length > 1 && (
                                                <button
                                                    className="btn btn-sm btn-danger"
                                                    onClick={() => removeRow(i)}
                                                >
                                                    ×
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <button className="btn btn-outline-primary" onClick={addRow}>
                            + Add Payment
                        </button>
                    </div>

                    <div className="modal-footer">
                        <button className="btn btn-secondary" onClick={onClose}>
                            Cancel
                        </button>
                        <button className="btn btn-success" onClick={savePayment}>
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
