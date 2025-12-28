import React, { useEffect, useRef } from "react";
import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.bootstrap5.css";

export default function CommonTomSelect({
    id,
    placeholder = "Select",
    onChange,
    loadUrl = null,
    searchFields = ["name"],
    preload = false,
}) {
    const selectRef = useRef(null);

    useEffect(() => {
        if (!selectRef.current) return;

        const tom = new TomSelect(selectRef.current, {
            valueField: "id",
            labelField: "name",
            searchField: searchFields,
            maxItems: 1,
            preload,

            load(query, callback) {
                if (!loadUrl) return callback([]);

                fetch(`${loadUrl}?query=${encodeURIComponent(query)}`, {
                    credentials: "same-origin",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "Accept": "application/json",
                    },
                })
                    .then(res => res.json())
                    .then(res => callback(res.items ?? res))
                    .catch(() => callback([]));
            },

            onChange(value) {
                onChange?.(value);
            },
        });

        return () => tom.destroy();
    }, []);

    return (
        <select ref={selectRef} id={id} className="form-control">
            <option value="">{placeholder}</option>
        </select>
    );
}
