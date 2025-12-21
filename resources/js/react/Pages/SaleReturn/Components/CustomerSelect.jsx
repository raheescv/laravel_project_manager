import React, { useEffect } from "react";
import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.css";
import axios from "axios";

export default function CustomerSelect({ onChange }) {
    useEffect(() => {
        const select = document.getElementById("customer_id");
        if (!select) return;

        // Fetch all customers once
        axios.get("/account/list") // make sure your endpoint returns all customers
            .then(res => {
                const options = (res.data.items ?? []).map(c => ({
                    id: c.id,
                    name: c.name,
                    mobile: c.mobile,
                    email: c.email,
                }));

                const tom = new TomSelect(select, {
                    valueField: "id",
                    labelField: "name",
                    searchField: ["name", "mobile", "email"], // search works
                    maxItems: 1,
                    options: options, // preload all customers
                    onChange(value) {
                        onChange?.(value);
                    },
                });

                return () => tom.destroy();
            })
            .catch(err => console.error("Failed to load customers:", err));
    }, []);

    return (
        <select id="customer_id" className="form-control">
            <option value="">Select Customer</option>
        </select>
    );
}
