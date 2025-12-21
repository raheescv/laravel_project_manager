import React, { useEffect } from "react";
import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.css";
import axios from "axios";

export default function EmployeeSelect({ onChange }) {
    useEffect(() => {
        const select = document.getElementById("employee_id");
        if (!select) return;

        // Fetch all employees once
        axios.get("/employees/employee") // Make sure your endpoint returns all employees
            .then(res => {
                const options = (res.data ?? []).map(emp => ({
                    id: emp.id,
                    name: emp.name,
                }));

                const tom = new TomSelect(select, {
                    valueField: "id",
                    labelField: "name",
                    searchField: ["name"], // searchable by name
                    maxItems: 1,
                    options: options, // preload all employees
                    onChange(value) {
                        onChange?.(value);
                    },
                });

                return () => tom.destroy();
            })
            .catch(err => console.error("Failed to load employees:", err));
    }, []);

    return (
        <select id="employee_id" className="form-control">
            <option value="">Select Employee</option>
        </select>
    );
}
