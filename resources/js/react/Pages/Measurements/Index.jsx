import React, { useState, useEffect } from "react";
import { usePage } from "@inertiajs/react";
import MeasurementForm from "../../Components/MeasurementForm";
import SavedMeasurements from "../../Components/SavedMeasurements";

export default function Index() {
    const {
        customers,
        categories,
        templates,
        savedMeasurements = [],
        flash,
        editItem: serverEditItem
    } = usePage().props;

    const [editItem, setEditItem] = useState(null);

    useEffect(() => {
        if (serverEditItem) {
            setEditItem(serverEditItem);
        }
    }, [serverEditItem]);

    useEffect(() => {
        if (flash?.success) {
            setEditItem(null);
        }
    }, [flash]);

    return (
        <div className="container-fluid">
            <div className="card">
                <div className="card-header">
                    <h4 className="mb-0">Tailor Measurement List</h4>
                </div>

                <div className="card-body">
                    {flash?.success && (
                        <div className="alert alert-success">
                            {flash.success}
                        </div>
                    )}

                    <MeasurementForm
                        customers={customers}
                        categories={categories}
                        templates={templates}
                        editItem={editItem}
                    />

                    {/* ✅ DataTable handles everything */}
                    <SavedMeasurements saved={savedMeasurements} />
                </div>
            </div>
        </div>
    );
}
