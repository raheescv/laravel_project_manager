import React from "react";
import { createRoot } from "react-dom/client";
import { createInertiaApp } from "@inertiajs/react";

createInertiaApp({
  resolve: (name) => {
    // Dynamically import pages by name
    const pages = {
      "Scan/Scanner": () => import("./Pages/Scan/Scanner.jsx"),
      Home: () => import("./Pages/Home.jsx"),
    };

    if (pages[name]) {
      return pages[name]();
    }

    throw new Error(`Page ${name} not found`);
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
});
