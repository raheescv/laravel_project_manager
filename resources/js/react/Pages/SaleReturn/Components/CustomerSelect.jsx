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

    // Initialize TomSelect only once
    useEffect(() => {
        if (!selectRef.current) return;

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
                onChange?.(val ? Number(val) : null);
            },
        });

        return () => { tomRef.current?.destroy(); tomRef.current = null; };
    }, []);

    // Update options in TomSelect when they change
    useEffect(() => {
        if (!tomRef.current) return;
        tomRef.current.clearOptions();
        options.forEach(o => tomRef.current.addOption(o));
        tomRef.current.refreshOptions(false);
    }, [options]);

    // Auto-select existing value
    useEffect(() => {
        if (!tomRef.current || !value) return;

        const existing = options.find(o => o.id === String(value));
        if (existing) {
            tomRef.current.setValue(existing.id, true);
        } else {
            // Fetch customer if not in options
            axios.get(`/account/${value}`).then(res => {
                const sel = { id: String(res.data.id), name: `${res.data.name} (${res.data.mobile || "-"})` };
                setOptions(prev => [sel, ...prev]); // prepend
                tomRef.current.addOption(sel);
                tomRef.current.setValue(sel.id, true);
            });
        }
    }, [value, options]);

    // Auto-select newly added customer
  useEffect(() => {
    if (!newCustomer || !newCustomer.id) return;

    const sel = {
        id: String(newCustomer.id),
        name: `${newCustomer.name} (${newCustomer.mobile || "-"})`
    };

    setOptions(prev => [sel, ...prev]);

    if (tomRef.current) {
        tomRef.current.addOption(sel);
        tomRef.current.setValue(sel.id, true);
    }
}, [newCustomer]);

    return <select ref={selectRef} className="form-control" placeholder="Select customer"></select>;
}
