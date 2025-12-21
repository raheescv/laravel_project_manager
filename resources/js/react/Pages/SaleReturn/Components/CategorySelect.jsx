import React, { useEffect } from "react";
import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.css";
import axios from "axios";

export default function CategorySelect({ onChange }) {
    useEffect(() => {
        const select = document.getElementById("category_id");
        if (!select) return;

        // Fetch all categories once
        axios.get("/categories/categorie")
            .then(res => {
                const options = res.data.map(c => ({
                    id: c.id,
                    name: c.name
                }));

                const tom = new TomSelect(select, {
                    valueField: "id",
                    labelField: "name",
                    searchField: ["name"], // search enabled
                    maxItems: 1,
                    options: options,      // preload all
                    onChange(value) {
                        onChange?.(value);
                    },
                });

                return () => tom.destroy();
            })
            .catch(err => {
                console.error("Failed to load categories:", err);
            });
    }, []);

    return (
        <select id="category_id" className="form-control">
            <option value="">Select Category</option>
        </select>
    );
}
