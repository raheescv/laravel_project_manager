import React, { useState } from "react";
import { usePage } from "@inertiajs/react";
import MeasurementForm from "../../Components/MeasurementForm.jsx";
import SavedMeasurements from "../../Components/SavedMeasurements.jsx";

export default function Index() {
    const { customers, categories, templates, savedMeasurements, flash } = usePage().props;

    const [limit, setLimit] = useState(10);
    const [search, setSearch] = useState("");

    return (
        <div className="container-fluid">
            <div className="card">
                <div className="card-header">
                    <div className="row align-items-center">
                        <div className="col-md-4">
                            <h4 className="mb-0">Measurement Fields</h4>
                        </div>

                        <div className="col-md-2">
                            <select
                                className="form-control"
                                value={limit}
                                onChange={e => setLimit(e.target.value)}
                            >
                                <option value="10">10</option>
                                <option value="100">100</option>
                                <option value="500">500</option>
                            </select>
                        </div>

                        <div className="col-md-4">
                            <input
                                type="text"
                                className="form-control"
                                placeholder="Search..."
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                            />
                        </div>
                    </div>
                </div>

                <div className="card-body">
                    {flash?.success && (
                        <div className="alert alert-success">{flash.success}</div>
                    )}

                    <MeasurementForm
                        customers={customers}
                        categories={categories}
                        templates={templates}
                    />

                    <SavedMeasurements saved={savedMeasurements} limit={limit} search={search} />
                </div>
            </div>
        </div>
    );
}
