<!DOCTYPE html>
@php
    use Carbon\Carbon;
    use App\Models\Configuration;
    $residentialLogoLease = Configuration::where('key', 'lease_residential_logo')->value('value');
    $residentialLogoLeaseUrl = null;
    if ($residentialLogoLease) {
        $residentialLogoLeasePath = storage_path('app/public/' . $residentialLogoLease);
        if (file_exists($residentialLogoLeasePath)) {
            $residentialLogoLeaseUrl = 'data:image/' . pathinfo($residentialLogoLeasePath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($residentialLogoLeasePath));
        }
    }

    $bondPaperMode = Configuration::where('key', 'reservation_bond_paper_mode')->value('value') === 'yes';
    $logoHeight = (int) (Configuration::where('key', 'reservation_logo_height')->value('value') ?: 80);
    $footerHeight = (int) (Configuration::where('key', 'reservation_footer_height')->value('value') ?: 30);
@endphp
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>AGREEMENT FOR SALE OF SINGLE UNIT – OFF PLAN</title>
    @include('print.booking.components.styles')
    {{-- Reserved bands, on EVERY page:
         · Top — @page margin-top keeps the letterhead area clear (bond mode:
           the pre-printed logo band; normal mode: a small breathing gap).
           Page 1 draws its own top band inline (logo image or blank spacer),
           so its @page top margin is zeroed to avoid double spacing.
         · Bottom — the .doc-wrap table's <tfoot> repeats the signature strip
           (plus, in bond mode, a blank strip for the pre-printed footer) and
           reserves its height, so content breaks to the next page instead of
           ever rendering into it. No <br> spacing hacks are needed. --}}
    <style>
        @page { margin-top: {{ $bondPaperMode ? $logoHeight : 24 }}px; }
        @page :first { margin-top: 0; }
    </style>
</head>

<body>
    {{-- The whole document is wrapped in a table so the signature block in
         <tfoot> repeats at the bottom of EVERY page and the browser reserves its
         space (unlike position:fixed, which Chrome does not repeat reliably). --}}
    <table class="doc-wrap" style="width: 100%; border-collapse: collapse; border: 0;">
        <tfoot>
            <tr>
                <td style="border: 0; padding: 0;">
                    <div class="signature-row">
                        <div>
                            <div class="signature-line"></div>
                            <div class="signature-label">First Party Signature / توقيع الطرف الأول</div>
                        </div>
                        <div>
                            <div class="signature-line"></div>
                            <div class="signature-label">Second Party Signature / توقيع الطرف الثاني</div>
                        </div>
                    </div>
                    @if ($bondPaperMode)
                        {{-- Blank strip reserved for the pre-printed bond-paper footer,
                             below the signatures, on every page. --}}
                        <div style="width: 100%; height: {{ $footerHeight }}px;"></div>
                    @endif
                </td>
            </tr>
        </tfoot>
        <tbody>
            <tr>
                <td style="border: 0; padding: 0;">
                    @if ($bondPaperMode)
                        <div style="width: 100%; height: {{ $logoHeight }}px;"></div>
                    @elseif ($residentialLogoLeaseUrl)
                        <img src="{{ $residentialLogoLeaseUrl }}" alt="Logo" style="width: 100%; height: auto; display: block;">
                    @endif
                    <div class="container">

                        <!-- Contract meta + QR -->
                        <div id="header_container" style="display: flex; align-items: stretch; gap: 6px; margin-bottom: 6px;">
                            <table class="header-table" style="flex: 1;">
                                <tr>
                                    <td rowspan="3" style="width: 15%; text-align: left;">Contract Serial</td>
                                    <td style="width: 20%;">Sales Order No.</td>
                                    <td style="width: 25%; text-align: center;" class="bold">{{ $rentOut->agreement_no }}</td>
                                    <td style="width: 20%; text-align: right;" lang="ar">رقم أمر البيع</td>
                                    <td rowspan="3" style="width: 15%; text-align: right;" lang="ar">الرقم التسلسلي للعقد</td>
                                </tr>
                                <tr>
                                    <td>Customer No.</td>
                                    <td style="text-align: center;" class="bold">{{ $rentOut->id }}</td>
                                    <td style="text-align: right;" lang="ar">رقم العميل</td>
                                </tr>
                                <tr>
                                    <td>Location Code</td>
                                    <td style="text-align: center;" class="bold">{{ $rentOut->group?->name }}</td>
                                    <td style="text-align: right;" lang="ar">الموقع</td>
                                </tr>
                            </table>

                            <div class="qr-box" id="qr_code_container">
                                <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG(url('property/sale/booking/view/' . $rentOut->id), 'QRCODE', 5, 5) }}" alt="QR Code">
                                <small>969897909-{{ str_pad($rentOut->id, 4, '0', STR_PAD_LEFT) }}</small>
                            </div>
                        </div>

                        <!-- Title -->
                        <div class="doc-title">
                            <div class="doc-title-en">AGREEMENT FOR SALE OF SINGLE UNIT – OFF PLAN</div>
                            <div class="doc-title-ar" lang="ar">عقد بيع شقة واحدة – على الخارطة</div>
                            <div class="doc-subtitle">Particulars of Sale and Purchase &nbsp;–&nbsp; <span lang="ar" style="display: inline;">تفاصيل البيع والشراء</span></div>
                        </div>

                        <!-- First Party -->
                        @php
                            $cfg = fn ($key, $default = '') => Configuration::where('key', $key)->value('value') ?? $default;
                        @endphp
                        <div class="section">
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">THE FIRST PARTY (SELLER)</div>
                                    <p class="bold normal-text">{{ $cfg('lessor_name_en') }}</p>
                                    <p class="small-text">P.O. Box: <span class="underline">{{ $cfg('lessor_po_box') }}</span>, Doha, Qatar &nbsp;|&nbsp; CR No. <span class="underline">{{ $cfg('lessor_cr_no') }}</span></p>
                                    <p class="small-text">AUTHORIZED BY {{ $cfg('lessor_authorized_by') }}</p>
                                    <p class="small-text">QID No. {{ $cfg('lessor_qid_no') }} &nbsp;|&nbsp; NATIONALITY: {{ $cfg('lessor_nationality') }}</p>
                                    <p class="small-text">Email: {{ $cfg('lessor_email') }} &nbsp;|&nbsp; Tel: {{ $cfg('lessor_tel_fax') }}</p>
                                </div>
                                <div class="cell cell-ar" lang="ar">
                                    <div class="section-title">الطرف الأول (البائع)</div>
                                    <p class="bold normal-text">{{ $cfg('lessor_name_ar') }}</p>
                                    <p class="small-text">ص.ب: <span class="underline">{{ $cfg('lessor_po_box') }}</span>، الدوحة، قطر &nbsp;|&nbsp; س.ت: <span class="underline">{{ $cfg('lessor_cr_no') }}</span></p>
                                    <p class="small-text">يمثلها السيد/ {{ $cfg('lessor_authorized_by') }}</p>
                                    <p class="small-text">بطاقة شخصية رقم: {{ $cfg('lessor_qid_no') }} &nbsp;|&nbsp; الجنسية: {{ $cfg('lessor_nationality') }}</p>
                                    <p class="small-text">البريد الإلكتروني: {{ $cfg('lessor_email') }} &nbsp;|&nbsp; هاتف: {{ $cfg('lessor_tel_fax') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Second Party -->
                        <div class="section">
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">THE SECOND PARTY (PURCHASER)</div>
                                    <p>Mr./Mrs. <b>{{ $rentOut->customer?->name }}</b></p>
                                    <p>QID: <b>{{ $rentOut->customer?->id_no }}</b> &nbsp;|&nbsp; NATIONALITY: <b>{{ $rentOut->customer?->nationality }}</b></p>
                                    <p>Mobile: <b>{{ $rentOut->customer?->mobile }}</b> &nbsp;|&nbsp; Email: <b>{{ $rentOut->customer?->email }}</b></p>
                                    <p class="small-text">P.O. Box: {{ $rentOut->customer?->po_box }}</p>
                                </div>
                                <div class="cell cell-ar" lang="ar">
                                    <div class="section-title">الطرف الثاني (المشتري)</div>
                                    <p>السيد/ة <b>{{ $rentOut->customer?->name }}</b></p>
                                    <p>البطاقة الشخصية: <b>{{ $rentOut->customer?->id_no }}</b> &nbsp;|&nbsp; الجنسية: <b>{{ $rentOut->customer?->nationality }}</b></p>
                                    <p>الجوال: <b>{{ $rentOut->customer?->mobile }}</b> &nbsp;|&nbsp; البريد الإلكتروني: <b>{{ $rentOut->customer?->email }}</b></p>
                                    <p class="small-text">ص.ب: {{ $rentOut->customer?->po_box }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Project Details -->
                        <div class="section">
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">PROJECT DETAILS</div>
                                    <p>Project Land: <b>{{ $rentOut->group?->name }}</b></p>
                                    <p>Building: <b>{{ $rentOut->building?->name }}</b></p>
                                    <p>Unit No.: <b>{{ $rentOut->property?->number }}</b> &nbsp;|&nbsp; Floor: <b>{{ $rentOut->property?->floor }}</b></p>
                                    <p>Parking: One Parking ({{ $rentOut->property?->parking }})</p>
                                </div>
                                <div class="cell cell-ar" lang="ar">
                                    <div class="section-title">تفاصيل المشروع</div>
                                    <p>أرض المشروع: <b>{{ $rentOut->group?->arabic_name }}</b></p>
                                    <p>المبنى: <b>{{ $rentOut->building?->arabic_name }}</b></p>
                                    <p>رقم الوحدة: <b>{{ $rentOut->property?->number }}</b> &nbsp;|&nbsp; الطابق: <b>{{ $rentOut->property?->floor }}</b></p>
                                    <p>موقف السيارات: موقف واحد ({{ $rentOut->property?->parking }})</p>
                                </div>
                            </div>
                        </div>

                        <!-- Preliminary Clause -->
                        <div class="section">
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">PRELIMINARY CLAUSE</div>
                                    <p>
                                        The first party owns the piece of land located in the registered area with the property details as described above. The first party has started obtaining permits
                                        for the construction of the project, according to the approved drawings and engineering specifications by the relevant authorities.
                                    </p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">البند التمهيدي</div>
                                    <p>
                                        يملك الطرف الأول قطعة الأرض الواقعة في المنطقة المسجلة بتفاصيل الملكية كما هو موضح أعلاه. وقد بدأ الطرف الأول في الحصول على التصاريح لبناء المشروع، وفقاً للرسومات والمواصفات الهندسية المعتمدة من الجهات المختصة.
                                    </p>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="cell cell-en">
                                    <p>
                                        The second party has confirmed the will to purchase a residential unit in this project, and according to the provisions
                                        of this contract and the price mentioned therein, the two parties have agreed to draft this contract under the following legal and lawful conditions:
                                    </p>
                                </div>
                                <div class="cell cell-ar">
                                    <p>
                                        وقد أكد الطرف الثاني رغبته في شراء وحدة سكنية في هذا المشروع، ووفقاً لأحكام هذا العقد والسعر المذكور فيه، فقد اتفق الطرفان على صياغة هذا العقد بالشروط القانونية والنظامية التالية:
                                    </p>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="cell cell-en">
                                    <p>
                                        Unit No.: <b>Unit ({{ $rentOut->property?->number }}) – {{ $rentOut->property?->floor }} Floor – {{ $rentOut->type?->name }} Apartment</b>
                                        <br>
                                        The area of this unit on the map and the architectural drawing of the project (<b>{{ $rentOut->property?->size }} square meters</b>) is subject to
                                        deficit and surplus, and the common facilities in the tower will be added, which are the common areas of the apartment as specified in the (Terms and Conditions of Apartment Sale).
                                    </p>
                                    <p><b>Parking: One Parking</b></p>
                                </div>
                                <div class="cell cell-ar">
                                    <p>
                                        رقم الوحدة: <b>الوحدة ({{ $rentOut->property?->number }}) – الطابق {{ $rentOut->property?->floor }} – شقة {{ $rentOut->type?->name }}</b>
                                        <br>
                                        مساحة هذه الوحدة على الخريطة والرسم المعماري للمشروع (<b>{{ $rentOut->property?->size }} متر مربع</b>) تخضع للنقص والزيادة، وسيتم إضافة المرافق المشتركة في البرج، وهي المناطق المشتركة للشقة كما هو محدد في (شروط وأحكام بيع الشقة).
                                    </p>
                                    <p><b>موقف السيارات: موقف واحد</b></p>
                                </div>
                            </div>
                        </div>

                        <!-- Schedule 1: Payment Schedule -->
                        <div class="page-break"></div>
                        <div class="sched-head">
                            <div class="main">SCHEDULE 1 – الجدول 1</div>
                            <div class="sub">Schedule of Instalment Payments / جدول تقسيط الدفعات</div>
                        </div>

                        <table class="header-table" style="margin-bottom: 6px;">
                            <tr>
                                <td style="width: 34%;"><b>Project:</b> {{ $rentOut->building?->name }}</td>
                                <td style="width: 33%;"><b>Building:</b> {{ $rentOut->group?->name }}</td>
                                <td style="width: 33%;"><b>Unit:</b> {{ $rentOut->property?->number }} - {{ $rentOut->type?->name }} Apartment</td>
                            </tr>
                        </table>

                        {{-- Row pagination is handled by CSS: rows never split across pages
                             (tr { page-break-inside: avoid }) and the <thead> repeats
                             automatically at the top of every page the table spans. --}}
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">
                                        Instalment<br>
                                        <span style="font-size: 8pt;" lang="ar">رقم القسط</span>
                                    </th>
                                    <th style="width: 13%;">
                                        Percentage<br>
                                        <span style="font-size: 8pt;" lang="ar">النسبة (%)</span>
                                    </th>
                                    <th>
                                        Date<br>
                                        <span style="font-size: 8pt;" lang="ar">التاريخ</span>
                                    </th>
                                    <th style="width: 25%;">
                                        Amount (QAR)<br>
                                        <span style="font-size: 8pt;" lang="ar">القيمة (ريال قطري)</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $installments = $rentOut->paymentTerms()->orderBy('due_date')->get();
                                @endphp
                                @foreach ($installments as $item)
                                    <tr>
                                        <td>
                                            @if ($item->label == 'down payment')
                                                Reservation Down Payment
                                                <span style="font-size: 8pt;" lang="ar">الدفعة المقدمة للحجز</span>
                                            @elseif($item->label == 'handover payment')
                                                Handover Payment
                                                <span style="font-size: 8pt;" lang="ar">دفعة التسليم</span>
                                            @else
                                                {{ ordinal($loop->iteration) . ' ' . ucFirst($item->label) }}
                                            @endif
                                        </td>
                                        <td style="text-align: right;">{{ getPercentage($item->total ?? $item->amount, $rentOut->rent, 2, true) }}</td>
                                        <td style="text-align: center;">{{ systemDate($item->due_date) }}</td>
                                        <td style="text-align: right;">{{ currency($item->total ?? $item->amount) }}</td>
                                    </tr>
                                @endforeach
                                @php
                                    $total = $installments->sum(fn ($t) => $t->total ?? $t->amount);
                                    $balance = $rentOut->rent - $total;
                                @endphp
                                <tr class="total-row">
                                    <td>Total</td>
                                    <td style="text-align: right;">{{ getPercentage($total, $rentOut->rent, 2, true) }}</td>
                                    <td></td>
                                    <td style="text-align: right;">{{ currency($total) }}</td>
                                </tr>
                                @if ($balance > 0)
                                    <tr>
                                        <td>
                                            Monthly Installment 54 Months
                                            <span style="font-size: 8pt;" lang="ar">تقسيط شهري لمدة 54 شهر</span>
                                        </td>
                                        <td style="text-align: right;">{{ getPercentage($balance, $rentOut->rent, 2, true) }}</td>
                                        <td style="text-align: center;">
                                            @php
                                                $lastPaymentDate = $rentOut->paymentTerms()->orderBy('due_date', 'DESC')->value('due_date');
                                                if ($lastPaymentDate) {
                                                    $startDate = Carbon::parse($lastPaymentDate)->addMonth();
                                                    $endDate = $startDate->copy()->addMonths(53);
                                                    $fromDate = $startDate->format('F Y');
                                                    $toDate = $endDate->format('F Y');
                                                } else {
                                                    $fromDate = '';
                                                    $toDate = '';
                                                }
                                            @endphp
                                            @if ($fromDate && $toDate)
                                                From {{ $fromDate }} to {{ $toDate }}
                                            @endif
                                        </td>
                                        <td style="text-align: right;">
                                            {{ currency($balance) }} (Remaining - المبلغ المتبقي)<br>
                                            {{ round($balance / 54, 2) }} (Monthly - شهري)
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>

                        <!-- Purchase Price + Contract Signatures -->
                        <div class="avoid-break" style="margin-top: 8px;">
                            <table class="data-table">
                                <tr>
                                    <td style="width: 50%; background: #f1f3f5;">
                                        <strong>Qatari Riyal Only</strong>
                                        <span style="font-size: 8pt;" lang="ar">ريال قطري فقط</span>
                                    </td>
                                    <td style="width: 25%; text-align: center; background: #f1f3f5;">
                                        <strong>Purchase Price</strong><br>
                                        <span style="font-size: 8pt;" lang="ar">سعر الشراء</span>
                                    </td>
                                    <td style="width: 25%; text-align: center; background: #f1f3f5;">
                                        <strong>STAMP</strong><br>
                                        <span style="font-size: 8pt;" lang="ar">ختم</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="height: 60px; text-align: center; vertical-align: middle; text-transform: capitalize;">
                                        {{ $numberToWord['english'] }}<br>
                                        {{ $numberToWord['arabic'] }}
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;" class="bold">{{ currency($rentOut->rent) }}</td>
                                    <td></td>
                                </tr>
                            </table>

                            <table class="data-table" style="margin-top: 6px;">
                                <tr>
                                    <td style="width: 50%; text-align: center; background: #f1f3f5;">
                                        <strong>Authorized signatory for and on the behalf of The First Party</strong>
                                    </td>
                                    <td style="width: 50%; text-align: center; background: #f1f3f5;">
                                        <strong>Handwritten signature \ Second Party</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="height: 60px;"></td>
                                    <td style="height: 60px;"></td>
                                </tr>
                            </table>
                        </div>

                        {{-- Articles 1-17 --}}
                        <div class="page-break"></div>
                        <div class="section">
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">Article One</div>
                                    <p>The preliminary section is considered an integral part of this contract and is complement to its provisions, in addition to the approved plans and architecture drawings from the relevant authorities, as well as the appendixes accompanying this contract.</p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">المادة الأولى</div>
                                    <p>يعتبر الجزء التمهيدي جزء لا يتجزأ من هذا العقد متمماً له ولبنوده – اضافة الى المخططات والرسومات الهندسية المعتمدة من الجهات المختصة والملاحق المرفقة لهذا العقد.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">Article Two</div>
                                    <p>The first party has sold, transferred, and relinquished with all legal and actual warranties to the second party, a specified-bounded unit described in the sales map (off plan sale) for the project. The unit is complete with doors, windows, outlets, and services such as water, lighting, sewage, and gas, with a fully furnished finishing system.<br>The area of the property sold is approximately ({{ $rentOut->property?->size }} sqm) square meters, subject to deficiency and excess based on engineering and architectural design.</p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">المادة الثانية</div>
                                    <p>باع وأسقط وتنازل الطرف الأول بكافة الضمانات القانونية والفعلية للطرف الثاني القابل لذلك ما هو عبارة عن الوحدة محددة الحدود مع الأوصاف بنظام البيع على الخارطة في المشروع. مستكملة الأبواب والنوافذ والمنافذ والخدمات من مياه وإنارة وصرف صحي وغاز بنظام تشطيب كامل مفروش.<br>ومساحة العقار الذي يقع للوحد المباعة ({{ $rentOut->property?->size }} متر مربع) متر مربع تقريباً تحت العجز والزيادة.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">Article Three</div>
                                    <p>This sale has been finalized for a total amount of ({{ currency($rentOut->rent) }}) Qatari Riyals only. The second party has made a payment following the specified installment plan, as agreed between both parties in this contract.</p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">المادة الثالثة</div>
                                    <p>تم هذا البيع نظير مبلغ اجمالي وقدره ({{ currency($rentOut->rent) }}) – فقط ريال قطري لا غير وقد قام الطرف الثاني بدفع المبلغ ثمن الوحدة وبالنظام المحدد بطريقة أقساط.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">Article Four</div>
                                    <p>The first party acknowledges that the ownership of the land on which the project is established has been transferred to the property developer through purchase. Both parties are aware that the ownership of the second party for the sold unit, after its completion and handover, is subject to the provisions of real estate ownership stipulated by civil law.</p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">المادة الرابعة</div>
                                    <p>يقر الطرف الأول بأن ملكية الأرض المقام عليها المشروع قد آلت إليه عن طريق الشراء كما أنه من المعلوم للطرفين أن تملك الطرف الثاني للوحدة المباعة بعد استلامها بعد اتمام المشروع يخضع لأحكام ملكية الطبقات المنصوص عليها بالقانون المدني.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">Article Five</div>
                                    <p>The buyer acknowledges, agrees, and understands that they are obligated to directly pay service fees/charges to the seller or the company managing the tower. The buyer agrees and undertakes to comply with the provisions of the management agreement and community rules.</p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">المادة الخامسة</div>
                                    <p>يقر المشتري ويوافق ويفهم أنه ملزم بدفع رسوم الخدمة مباشرة للبائع أو الشركة التي ستدير البرج. يوافق المشتري ويتعهد بالامتثال لأحكام اتفاقية الإدارة وقواعد المجتمع.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">Article Six</div>
                                    <p>The second party, the buyer, acknowledges that they have examined the approved plans and drawings of the project during its construction phase. The buyer accepts the unit in its current condition, under the agreed-upon terms.</p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">المادة السادسة</div>
                                    <p>يقر الطرف الثاني – المشتري – بأنه قد عاين المخططات والرسومات الهندسية المعتمدة قيد التشييد وبناء المشروع وقبلها على حالتها وبالشروط المتفق عليها.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">Article Seven</div>
                                    <p>The first party affirms to the second party that the sold unit is free from all real property rights, regardless of their type or nature.</p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">المادة السابعة</div>
                                    <p>يقر الطرف الأول للطرف الثاني بأن الوحدة المباعة خالية من كافة الحقوق العينية أياً كان نوعها.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">Article Eight</div>
                                    <p>The second party acknowledges that they are prohibited from engaging in any activities that could compromise the common usufruct or harm other property owners or occupants.</p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">المادة الثامنة</div>
                                    <p>يقر الطرف الثاني بأنه ممنوع من القيام بأي من الأعمال من شأنها أن تخل بالإنتفاع المشترك أو تضر بباقي الملاك أو شاغليه.</p>
                                </div>
                            </div>
                        </div>
                    </br>
                        <div class="section">
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">Article Nine</div>
                                    <p>The first party undertakes to initiate the registration procedures of the segregated unit according to the provisions of the Real Estate Registration Law.</p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">المادة التاسعة</div>
                                    <p>يلتزم الطرف الأول باتخاذ اجراءات تسجيل الوحدة المفرزة وفقاً لأحكام قانون التسجيل العقاري.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">Article Ten</div>
                                    <p>The Buyer shall pay all installments due to the Seller on the due dates and up to the date of full payment.</p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">المادة العاشرة</div>
                                    <p>يجب على المشتري دفع جميع الأقساط المستحقة للبائع في تاريخ الاستحقاق وحتى تاريخ الوفاء التام.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">Article Eleven</div>
                                    <p>The parties to this contract agree that in the event of a breach of any obligations by either party arising from this contract, the defaulting party shall be obligated to pay the other party a compensation as agreed upon.</p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">المادة الحادي عشر</div>
                                    <p>اتفق أطراف هذا العقد بأنه في حال اخلال أي طرف لأي من التزاماته الناشئة عن هذا العقد يكون ملزماً بدفع تعويض متفق عليه.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">Article Twelve</div>
                                    <p>The second party undertakes to bear all expenses, fees, and charges related to the demarcation and survey of their unit.</p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">المادة الثاني عشر</div>
                                    <p>يلتزم الطرف الثاني بأن يقع على عاتقه كافة المصروفات ورسوم وأتعاب انهاء الفرز والتجنيب لوحدته.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">Article Thirteen</div>
                                    <p>In the event of the buyer taking action on the unit, the buyer commits to paying 2% of the resale value to the seller as administrative fees.</p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">المادة الثالثة عشر</div>
                                    <p>في حال التصرف في الوحدة يلتزم المشتري بسداد 2% من قيمة اعادة البيع الى البائع كرسوم ادارية.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">Article Fourteen</div>
                                    <p>This contract is subject to the provisions of applicable real estate development law.</p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">المادة الرابعة عشر</div>
                                    <p>يخضع هذا العقد لأحكام القانون الخاص بتنظيم التطوير العقاري.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">Article Fifteen</div>
                                    <p>This contract is drafted in two copies, each having the same legal effect.</p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">المادة الخامسة عشر</div>
                                    <p>حرر هذا العقد من نسختين – نسخة لكل طرف للعمل عليها عند اللزوم.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">Article Sixteen</div>
                                    <p>The prementioned addresses in this contract shall be considered as the valid addresses for notifications, correspondence, and any potential disputes.</p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">المادة السادسة عشر</div>
                                    <p>تعتبر العناوين المسبقة بصدد هذا العقد هي التي يعتد بها في الإخطارات والمراسلات والمنازعات.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="cell cell-en">
                                    <div class="section-title">Article Seventeen</div>
                                    <p>The appendices attached to this contract are integral to it, and they constitute an inseparable part of its terms and conditions.</p>
                                </div>
                                <div class="cell cell-ar">
                                    <div class="section-title">المادة السابعة عشر</div>
                                    <p>تعتبر الملاحق المرفقة لهذا العقد متمماً له ولبنوده وشروطه وتعد جزأً لا يتجزأ منه.</p>
                                </div>
                            </div>
                        </div>
                        <div class="page-break"></div>
                        @include('print.booking.components.terms_and_conditions')
                        <div class="page-break"></div>
                        @include('print.booking.components.schedule_two')
                        <div class="page-break"></div>
                        @include('print.booking.components.schedule_three')
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>
