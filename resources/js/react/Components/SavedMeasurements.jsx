import React from "react";

export default function SavedMeasurements({ saved, limit, search }) {
    const filtered = saved
        .filter(
            m =>
                m.customer?.name.toLowerCase().includes(search.toLowerCase()) ||
                m.template?.name.toLowerCase().includes(search.toLowerCase())
        )
        .slice(0, limit);

    return (
        <div className="table-responsive mt-4">
            <table className="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Category</th>
                        <th>Template</th>
                        <th>Values</th>
                        <th width="120">Action</th>
                    </tr>
                </thead>
                <tbody>
                    {filtered.length > 0 ? (
                        filtered.map((m, i) => (
                            <tr key={i}>
                                <td>{i + 1}</td>
                                <td>{m.customer?.name ?? "Unknown"}</td>
                                <td>{m.template?.category?.name ?? "Unknown"}</td>
                                <td>{m.template?.name ?? "Unknown"}</td>
                              <span className="ml-2">
                        {m.values && typeof m.values === 'object'
                            ? Object.entries(m.values).map(([k, v], idx) => (
                                <span key={idx}>
                                    {v}{idx < Object.keys(m.values).length - 1 ? ', ' : ''}
                                </span>
                            ))
                            : "No values"}
                    </span>
                                <td>
                                    <button
                                        className="btn btn-danger btn-sm"
                                        onClick={() =>
                                            window.confirm("Delete this?") && console.log("delete", m.id)
                                        }
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))
                    ) : (
                        <tr>
                            <td colSpan={6} className="text-center">
                                No measurements found
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}
