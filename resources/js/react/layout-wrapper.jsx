// resources/js/react/layout-wrapper.jsx

import React from "react";

export default function applyLayout(Page, Layout) {
    Page.layout = (pageProps) => (
        <Layout user={pageProps.props.auth?.user}>
            <Page {...pageProps} />
        </Layout>
    );

    return Page;
}
