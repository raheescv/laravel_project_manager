<x-error-page
    code="500"
    title="Something Went Wrong"
    subtitle="We hit an unexpected problem on our side. It's not something you did, and our team has been notified."
    infoText="Try again in a moment. If it keeps happening, share the reference below with support so we can trace exactly what went wrong."
    icon="server"
    color="#f97316"
    colorEnd="#e11d48"
    primaryAction="refresh"
    primaryLabel="Try Again"
    :retryUrl="request()->isMethod('GET') ? null : url()->previous()"
    detailsTitle="Support Details"
    :details="[
        'reference' => errorReference(),
        'occurred_at' => now()->format('d M Y, h:i A'),
        'url' => request()->path() === '/' ? null : request()->path(),
    ]"
/>
