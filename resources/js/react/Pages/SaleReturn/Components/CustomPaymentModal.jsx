export default function CustomPaymentModal({ payments, setPayments, onClose }) {
    function addPayment() {
        setPayments([...payments, { amount: 0, name: "", payment_method_id: "" }]);
    }

    return (
        <div className="modal fade show d-block">
            <div className="modal-dialog">
                <div className="modal-content">
                    <div className="modal-header">
                        <h5>Custom Payments</h5>
                    </div>

                    <div className="modal-body">
                        {payments.map((p, i) => (
                            <input
                                key={i}
                                className="form-control mb-2"
                                value={p.amount}
                                onChange={e => {
                                    const list = [...payments];
                                    list[i].amount = e.target.value;
                                    setPayments(list);
                                }}
                            />
                        ))}
                        <button className="btn btn-sm btn-secondary" onClick={addPayment}>
                            Add
                        </button>
                    </div>

                    <div className="modal-footer">
                        <button className="btn btn-primary" onClick={onClose}>
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
