import React, { useEffect, useRef, useState } from "react";
import TomSelect from "tom-select";
import axios from "axios";

export default function CustomerSelect({ value, onChange }) {
    const selectRef = useRef(null);
    const tomRef = useRef(null);
    const [options, setOptions] = useState([]);

    useEffect(() => {
        let mounted = true;

        // Fetch all customers from API
        axios.get("/account/list").then((res) => {
            if (!mounted) return;

            const customers = (res.data.items || []).map((c) => ({
                id: String(c.id),
                name: c.name,
                mobile: c.mobile ?? "",
                email: c.email ?? "",
            }));

            setOptions(customers);
        });

        return () => {
            mounted = false;
            tomRef.current?.destroy();
            tomRef.current = null;
        };
    }, []);

    useEffect(() => {
        if (!selectRef.current || options.length === 0) return;

        // Destroy previous instance
        tomRef.current?.destroy();

        // Initialize TomSelect
        tomRef.current = new TomSelect(selectRef.current, {
            valueField: "id",
            labelField: "name",
            searchField: ["name", "mobile", "email"],
            options,
            maxItems: 1,
            create: true, // allows typing new customer
            onChange(val) {
                if (val) onChange?.(Number(val));
                else onChange?.(null);
            },
        });

        // Auto-select value only if editing
        if (value) {
            tomRef.current.setValue(String(value), true);
        }
    }, [options, value]);

    return <select ref={selectRef} className="form-control" placeholder="Select  customer"></select>;
}
