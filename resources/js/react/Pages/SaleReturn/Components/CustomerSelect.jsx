import React, { useEffect, useRef, useState } from "react";
import TomSelect from "tom-select";
import axios from "axios";

export default function CustomerSelect({ value, onChange, newCustomer }) {

    const selectRef = useRef(null);
    const tomRef = useRef(null);
    const [options, setOptions] = useState([]);

    // Load initial customers
    useEffect(() => {
        let mounted = true;

        axios.get("/account/list").then(res => {
            if (!mounted) return;
            const customers = (res.data.items || []).map(c => ({
                id: String(c.id),
                name: `${c.name} (${c.mobile || "-"})`,
            }));
            setOptions(customers);
        });

        return () => { mounted = false; };
    }, []);

    // Initialize TomSelect
    useEffect(() => {
        if (!selectRef.current) return;

        // Destroy previous instance
        tomRef.current?.destroy();

        tomRef.current = new TomSelect(selectRef.current, {
            valueField: "id",
            labelField: "name",
            searchField: ["name"],
            options,
            maxItems: 1,
            create: true,
            load: function(query, callback) {
                if (!query.length) return callback(options);
                axios.get("/account/list", { params: { query } })
                    .then(res => {
                        const data = (res.data.items || []).map(c => ({
                            id: String(c.id),
                            name: `${c.name} (${c.mobile || "-"})`,
                        }));
                        callback(data);
                    })
                    .catch(() => callback());
            },
            onChange(val) {
                if (val) onChange?.(Number(val));
                else onChange?.(null);
            },
        });

        

        // Auto-select in edit mode
        if (value) {
            const existing = options.find(o => o.id === String(value));
            if (existing) {
                tomRef.current.setValue(existing.id, true);
            } else {
                // Fetch the customer and add to options
                axios.get(`/account/${value}`).then(res => {
                    const sel = { id: String(res.data.id), name: `${res.data.name} (${res.data.mobile || "-"})` };
                    setOptions(prev => [sel, ...prev]); // prepend to options
                    tomRef.current.addOption(sel);
                    tomRef.current.setValue(sel.id, true);
                });
            }
        }

        return () => { tomRef.current?.destroy(); tomRef.current = null; };
    }, [options, value]);

    return <select ref={selectRef} className="form-control" placeholder="Select customer"></select>;
}
