<x-error-page
    code="403"
    title="Access Denied"
    subtitle="You don't have permission to access this resource. Contact your administrator if you believe this is an error."
    infoText="This area requires elevated permissions. Your current role does not have the necessary access level."
    icon="lock"
    color="#ef4444"
    colorEnd="#dc2626"
    primaryAction="back"
    :details="$details ?? null"
/>
