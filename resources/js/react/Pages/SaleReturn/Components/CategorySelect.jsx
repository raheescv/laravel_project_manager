import React, { useEffect, useRef, useState } from "react";
import TomSelect from "tom-select";
import axios from "axios";
import "./CategorySelect.css";

export default function CategorySelect({ value, onChange }) {
    const selectRef = useRef(null);
    const tomRef = useRef(null);
    const [options, setOptions] = useState([]);

    useEffect(() => {
        let mounted = true;

        // Fetch categories from API
        axios.get("/categories/categorie").then((res) => {
            if (!mounted) return;

            const categories = (res.data || []).map((c) => ({
                id: String(c.id),
                name: c.name,
            }));

            setOptions(categories);
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
            searchField: ["name"],
            options,
            maxItems: 1,
            create: false,
            onChange(val) {
                if (val) onChange?.(Number(val));
                else onChange?.(null);
            },
        });

        // Auto-select value (edit mode)
        if (value) {
            tomRef.current.setValue(String(value), true);
        }
    }, [options, value]);

    return (
        <select
            ref={selectRef}
            className="form-control"
            placeholder="Select category"
        />
    );
}
