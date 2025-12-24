import "../bootstrap"; // axios + csrf handled here
import { createRoot } from "react-dom/client";
import { createInertiaApp } from "@inertiajs/react";
import React from "react";

/* 🔔 Toast imports */
import { ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";

createInertiaApp({
    resolve: (name) => {
        // Auto-load all React pages inside resources/js/react/Pages
        const pages = import.meta.glob("./Pages/**/*.jsx");

        if (!pages[`./Pages/${name}.jsx`]) {
            throw new Error(`Page not found: ./Pages/${name}.jsx`);
        }

        return pages[`./Pages/${name}.jsx`]();
    },

    setup({ el, App, props }) {
        createRoot(el).render(
            <>
                <App {...props} />

                {/* ✅ GLOBAL TOASTER (ONLY ONCE) */}
                <ToastContainer
                    position="top-right"
                    autoClose={3000}
                    hideProgressBar={false}
                    newestOnTop
                    closeOnClick
                    pauseOnHover
                    draggable
                />
            </>
        );
    },

    progress: {
        color: "#29d",
    },
});
