{{-- One day's canteen pre-orders for the kitchens and for delivering to classes (PrintController::studentPreOrders),
     one section per branch, each on its own page: student details and item names with quantities only, no prices.
     Data: PrintListAction. Browsershot: inline CSS, no JS, logo as a data URI. --}}
@php
    $gradeClass = App\Actions\Student\PreOrder\PrintListAction::gradeClass(...);
    /** "3 × Happy Meal · 2 × Fresh Juice": what one class gets, for the runner. */
    $classItems = fn ($orders) => collect($orders)->flatMap(fn ($order) => $order['items'])->groupBy('name')
        ->map(fn ($lines, $name) => $lines->sum('quantity').' × '.$name)->sortKeys()->implode(' · ');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Pre-orders {{ $day->format('d-m-Y') }}</title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", "Noto Sans", "Noto Sans Arabic", Tahoma, Arial, sans-serif; font-size: 11px; line-height: 1.4; color: #24282e; }
        .mast { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding-bottom: 10px; border-bottom: 2px solid #24282e; }
        .mast__brand { display: flex; align-items: center; gap: 12px; }
        .mast__brand img { height: 42px; width: auto; }
        .mast__brand b { display: block; font-size: 13px; }
        .mast__brand small { color: #6b7280; }
        .mast__title { text-align: right; }
        .mast__title h1 { margin: 0; font-size: 20px; letter-spacing: -.01em; }
        .mast__title p { margin: 2px 0 0; color: #6b7280; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin: 12px 0 14px; }
        .stat { padding: 8px 10px; border: 1px solid #d9dde3; border-radius: 6px; }
        .stat small { display: block; font-size: 9px; letter-spacing: .08em; text-transform: uppercase; color: #6b7280; }
        .stat b { font-size: 16px; }
        .stat--warn b { color: #a33a2b; }
        h2 { margin: 16px 0 6px; font-size: 12px; letter-spacing: .08em; text-transform: uppercase; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 6px 7px; border-bottom: 1.5px solid #24282e; font-size: 9.5px; letter-spacing: .06em; text-transform: uppercase; color: #4b5563; text-align: left; }
        td { padding: 6px 7px; border-bottom: 1px solid #e3e6ea; vertical-align: top; }
        tr { page-break-inside: avoid; }
        thead { display: table-header-group; }
        .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .prep td { font-size: 12px; }
        .prep td.num b { font-size: 14px; }
        .group td { padding: 9px 7px 5px; border-bottom: 1.5px solid #9ca3af; background: #f3f4f6; font-weight: 700; font-size: 11.5px; }
        .group td span { font-weight: 400; color: #6b7280; }
        .group td em { display: block; margin-top: 2px; font-style: normal; font-weight: 600; font-size: 10.5px; color: #374151; }
        .group { page-break-after: avoid; }
        .who b { display: block; font-size: 12px; }
        .who small { color: #6b7280; }
        .items div { white-space: nowrap; }
        .items b { display: inline-block; min-width: 22px; }
        .note { margin-top: 3px; font-style: italic; color: #6b7280; }
        .tick { width: 34px; text-align: center; }
        .tick i { display: inline-block; width: 15px; height: 15px; border: 1.5px solid #6b7280; border-radius: 3px; }
        .unpaid { margin-top: 14px; padding: 8px 10px; border: 1px solid #e7b9b2; border-radius: 6px; background: #fbf1ef; }
        .unpaid h2 { margin-top: 0; color: #a33a2b; }
        .muted { color: #6b7280; }
        .empty { padding: 30px; text-align: center; color: #6b7280; border: 1px dashed #d9dde3; border-radius: 6px; }
        .branch + .branch { page-break-before: always; }
        .mast__branch { display: inline-block; margin-top: 4px; padding: 3px 10px; border-radius: 999px; background: #24282e; color: #fff; font-size: 12px; font-weight: 700; letter-spacing: .02em; }
        .foot { margin-top: 14px; color: #9ca3af; font-size: 9.5px; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
    @forelse ($sections as $section)
        <section class="branch">
            <header class="mast">
                <div class="mast__brand">
                    @if ($companyLogo)
                        <img src="{{ $companyLogo }}" alt="">
                    @endif
                    <div><b>{{ $companyName }}</b><small>Canteen pre-orders</small></div>
                </div>
                <div class="mast__title">
                    <h1>{{ $day->format('l, j F Y') }}</h1>
                    <span class="mast__branch">{{ $section['branch'] }}</span>
                    <p>{{ $locked ? 'Orders closed at '.$cutoff : 'Orders still open until '.$cutoff.' — this list can still change' }}</p>
                </div>
            </header>

            <div class="stats">
                <div class="stat"><small>Students</small><b>{{ $section['students'] }}</b></div>
                <div class="stat"><small>Items</small><b>{{ collect($section['items'])->sum('quantity') }}</b></div>
                <div class="stat"><small>Classes</small><b>{{ $section['groups']->count() }}</b></div>
                <div class="stat {{ $section['unpaid'] ? 'stat--warn' : '' }}"><small>Not paid</small><b>{{ $section['unpaid'] }}</b></div>
            </div>

            @if ($section['students'] === 0)
                <div class="empty">No paid pre-orders at {{ $section['branch'] }} for this day.</div>
            @else
                <h2>To prepare</h2>
                <table class="prep">
                    <thead><tr><th>Item</th><th class="num">Quantity</th></tr></thead>
                    <tbody>
                        @foreach ($section['items'] as $item)
                            <tr><td>{{ $item['name'] }}</td><td class="num"><b>{{ $item['quantity'] }}</b></td></tr>
                        @endforeach
                    </tbody>
                </table>

                <h2>By student</h2>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 26px">#</th>
                            <th>Student</th>
                            <th>Grade &amp; class</th>
                            <th>Items</th>
                            <th class="tick">Given</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $row = 0; @endphp
                        @foreach ($section['groups'] as $label => $orders)
                            <tr class="group"><td colspan="5">{{ $label }} <span>· {{ $orders->count() }} {{ $orders->count() === 1 ? 'student' : 'students' }}</span><em>{{ $classItems($orders) }}</em></td></tr>
                            @foreach ($orders as $order)
                                <tr>
                                    <td class="muted">{{ ++$row }}</td>
                                    <td class="who">
                                        <b>{{ $order['student'] }}</b>
                                        <small>{{ $order['admission_no'] ? 'Reg '.$order['admission_no'] : '' }}</small>
                                    </td>
                                    <td><b>{{ $gradeClass($order) }}</b></td>
                                    <td class="items">
                                        @foreach ($order['items'] as $line)
                                            <div><b>{{ $line['quantity'] }} ×</b> {{ $line['name'] }}</div>
                                        @endforeach
                                        @if ($order['note'])
                                            <div class="note">Note: {{ $order['note'] }}</div>
                                        @endif
                                    </td>
                                    <td class="tick"><i></i></td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($section['unpaidOrders']->isNotEmpty())
                <div class="unpaid">
                    <h2>Not paid · {{ $locked ? 'not served' : 'waiting for payment' }}</h2>
                    <table>
                        <thead>
                            <tr><th>Student</th><th>School</th><th>Grade &amp; class</th><th>Items</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($section['unpaidOrders'] as $order)
                                <tr>
                                    <td class="who"><b>{{ $order['student'] }}</b><small>{{ $order['admission_no'] ? 'Reg '.$order['admission_no'] : '' }}</small></td>
                                    <td>{{ $order['school'] ?: '—' }}</td>
                                    <td><b>{{ $gradeClass($order) }}</b></td>
                                    <td class="items">
                                        @foreach ($order['items'] as $line)
                                            <div><b>{{ $line['quantity'] }} ×</b> {{ $line['name'] }}</div>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <footer class="foot">
                <span>Printed {{ now()->format('d-m-Y H:i') }}</span>
                <span>{{ $section['branch'] }} · {{ $companyName }}</span>
            </footer>
        </section>
    @empty
        <header class="mast">
            <div class="mast__brand"><div><b>{{ $companyName }}</b><small>Canteen pre-orders</small></div></div>
            <div class="mast__title"><h1>{{ $day->format('l, j F Y') }}</h1>@if ($branchName)<span class="mast__branch">{{ $branchName }}</span>@endif</div>
        </header>
        <div class="empty" style="margin-top: 14px;">No pre-orders{{ $branchName ? ' at '.$branchName : '' }} for this day.</div>
    @endforelse
</body>
</html>
