import React, { useEffect, useRef, useState } from "react";
import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.css";
import axios from "axios";

export default function EmployeeSelectedit({ value, onChange }) {
    const selectRef = useRef(null);
    const tom = useRef(null);
    const [optionsLoaded, setOptionsLoaded] = useState(false);

    useEffect(() => {
        axios.get("/employees/employee")
            .then(res => {
                const employees = res.data?.data || res.data;

                const options = employees.map(emp => ({
                    id: String(emp.id),
                    name: emp.name,
                }));

                // Initialize TomSelect
                tom.current = new TomSelect(selectRef.current, {
                    valueField: "id",
                    labelField: "name",
                    searchField: ["name"],
                    maxItems: 1,
                    options: options,
                    onChange(val) {
                        onChange?.(val ? Number(val) : null);
                    },
                });

                setOptionsLoaded(true);
            })
            .catch(err => console.error("❌ Failed to load employees:", err));

        // Cleanup
        return () => tom.current?.destroy();
    }, []);

    // ✅ Update TOM value whenever `value` changes and options are loaded
    useEffect(() => {
        if (tom.current && optionsLoaded && value != null) {
            const strVal = String(value);
            if (tom.current.options[strVal]) {
                tom.current.setValue(strVal, true);
                console.log("🔁 Employee auto-selected:", strVal);
            }
        }
    }, [value, optionsLoaded]);

    return (
        <select ref={selectRef} className="form-control">
            <option value="">Select Employee</option>
        </select>
    );
}
