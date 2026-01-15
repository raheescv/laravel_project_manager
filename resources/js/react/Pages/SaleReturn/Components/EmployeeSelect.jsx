import React, { useEffect } from "react";
import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.css";
import axios from "axios";

export default function EmployeeSelect({ value = null, onChange }) {
    const selectRef = React.useRef(null);
    const tomRef = React.useRef(null);

    useEffect(() => {
        const select = selectRef.current;
        if (!select) return;

        let mounted = true;

        axios.get("/employees/employee")
            .then(res => {
                if (!mounted) return;

                const options = (res.data ?? []).map(emp => ({
                    id: String(emp.id),
                    name: emp.name,
                }));

                tomRef.current = new TomSelect(select, {
                    valueField: "id",
                    labelField: "name",
                    searchField: ["name"],
                    maxItems: 1,
                    options: options,
                    onChange(val) {
                        onChange?.(val ? Number(val) : null);
                    },
                });

                // set initial value if provided
                if (value != null) {
                    tomRef.current.setValue(String(value), true);
                }
            })
            .catch(err => console.error("Failed to load employees:", err));

        return () => {
            mounted = false;
            tomRef.current?.destroy();
            tomRef.current = null;
        };
    }, []);

    // react to external value changes
    useEffect(() => {
        if (tomRef.current && value != null) {
            tomRef.current.setValue(String(value), true);
        }
    }, [value]);

    return (
        <select ref={selectRef} className="form-control">
            <option value="">Select Tailor</option>
        </select>
    );
}
