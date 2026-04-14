<x-error-page
    code="419"
    title="Session Expired"
    subtitle="Your security token has expired. This happens to keep your account safe."
    infoText="This page was open too long without activity. Simply refresh to continue."
    icon="shield"
    color="#f59e0b"
    colorEnd="#ef4444"
    primaryAction="refresh"
    :countdown="true"
/>
