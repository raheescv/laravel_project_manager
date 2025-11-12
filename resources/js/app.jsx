import React from "react";
import { createRoot } from "react-dom/client";
import { createInertiaApp } from "@inertiajs/react";
import Scanner from "./Pages/Scan/Scanner.jsx";
import Home from "./Pages/Home.jsx";

createInertiaApp({
  resolve: (name) => {
    if (name === "Scan/Scanner") return Scanner;
    if (name === "Home") return Home;
    throw new Error(`Page ${name} not found`);
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
});
