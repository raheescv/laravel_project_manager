export default function EditItemModal({ item, onSave }) {
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
                        <input
                            className="form-control"
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
