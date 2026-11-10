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

    $cfg = fn (string $key): string => \App\Support\RentOutPrintSettings::lessor($key);
    // Escapes a free-text value and keeps any hyphenated ID in it (10-3804-00) whole and left-to-right.
    $withLtrIds = fn (string $value): string => preg_replace('/\d+(?:-\d+)+/', '<span class="ltr-id">$0</span>', e($value));
    $floor = $rentOut->property?->floor;
    $floorOrdinal = is_numeric($floor) ? ordinal((int) $floor) : $floor;
@endphp
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>AGREEMENT FOR SALE OF SINGLE UNIT – OFF PLAN</title>
    @include('print.booking.components.styles')
    {{-- Page bands. Top: the pre-printed letterhead (bond) or a breathing gap;
         in plain mode page 1 draws the logo inline, so its top margin is zero.
         Bottom: the pre-printed footer (bond) plus the strip where Chrome prints
         the page number (GenerateResidentialLeaseAction::saleFooterHtml). The
         initials strip in the .doc-wrap <tfoot> repeats just above it. --}}
    <style>
        @page {
            margin: {{ $bondPaperMode ? $logoHeight : 45 }}px 0 {{ $bondPaperMode ? $footerHeight + 30 : 45 }}px 0;
        }

        @page :first {
            margin-top: {{ $bondPaperMode ? $logoHeight : 0 }}px;
        }
    </style>
</head>

<body>
    {{-- The whole document sits in one table so the initials strip in <tfoot>
         repeats at the bottom of EVERY page and the browser reserves its height. --}}
    <table class="doc-wrap" style="width: 100%; border: 0;">
        <tfoot>
            <tr>
                <td style="border: 0; padding: 0;">
                    <div class="reg-initials">
                        <div><i></i>First Party · <span lang="ar">الطرف الأول</span></div>
                        <div><i></i>Second Party · <span lang="ar">الطرف الثاني</span></div>
                    </div>
                </td>
            </tr>
        </tfoot>
        <tbody>
            <tr>
                <td style="border: 0; padding: 0;">
                    @if (! $bondPaperMode && $residentialLogoLeaseUrl)
                        <img src="{{ $residentialLogoLeaseUrl }}" alt="Logo" style="width: 100%; height: auto; display: block; margin-bottom: 4mm;">
                    @elseif (! $bondPaperMode)
                        <div style="height: 12mm;"></div>
                    @endif
                    <div class="container">

                        <!-- Contract meta + QR -->
                        <div class="reg-meta">
                            <table>
                                <tr>
                                    <td rowspan="3" class="k" style="width: 15%;">Contract Serial</td>
                                    <td class="k" style="width: 18%;">Sales Order No.</td>
                                    <td class="text-center bold">{{ $rentOut->agreement_no }}</td>
                                    <td class="k" style="width: 16%;" lang="ar">رقم أمر البيع</td>
                                    <td rowspan="3" class="k" style="width: 15%;" lang="ar">الرقم التسلسلي للعقد</td>
                                </tr>
                                <tr>
                                    <td class="k">Customer No.</td>
                                    <td class="text-center bold">{{ $rentOut->id }}</td>
                                    <td class="k" lang="ar">رقم العميل</td>
                                </tr>
                                <tr>
                                    <td class="k">Location Code</td>
                                    <td class="text-center bold">{{ $rentOut->group?->name }}</td>
                                    <td class="k" lang="ar">الموقع</td>
                                </tr>
                            </table>
                            <div class="qr">
                                <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG(url('property/sale/booking/view/' . $rentOut->id), 'QRCODE', 5, 5) }}" alt="QR Code">
                                <small>969897909-{{ str_pad($rentOut->id, 4, '0', STR_PAD_LEFT) }}</small>
                            </div>
                        </div>

                        <!-- Title -->
                        <div class="reg-title">
                            <div class="en">AGREEMENT FOR SALE OF SINGLE UNIT – OFF PLAN</div>
                            <div class="ar" lang="ar">عقد بيع شقة واحدة – على الخارطة</div>
                            <div class="sub">Particulars of Sale and Purchase · <span lang="ar">تفاصيل البيع والشراء</span></div>
                        </div>

                        <!-- Particulars of sale and purchase -->
                        <table class="reg">
                            <tr>
                                <td>
                                    <p>On <b>{{ $rentOut->start_date ? Carbon::parse($rentOut->start_date)->format('j F Y') : '' }}</b> this contract has been issued between both parties</p>
                                </td>
                                <td lang="ar">
                                    <p>أنه في يوم <b>{{ $rentOut->start_date ? Carbon::parse($rentOut->start_date)->locale('ar')->translatedFormat('j F Y') : '' }}</b> تحرر هذا العقد بين كل من الطرفين</p>
                                </td>
                            </tr>

                            {{-- Party and project details: one row per detail, English and
                                 Arabic side by side, so each line sits level with its translation. --}}
                            @php
                                $field = fn (string $label, string $valueHtml): string => '<span class="lbl">' . e($label) . ':</span> <b>' . $valueHtml . '</b>';
                                $estId = $cfg('lessor_establishment_id');
                                $sellerLines = array_filter([
                                    ($cfg('lessor_capital_english') || $cfg('lessor_capital_arabic'))
                                        ? [$field('Capital', e($cfg('lessor_capital_english'))), $field('رأس المال', e($cfg('lessor_capital_arabic')))] : null,
                                    [$field('P.O. Box', e($cfg('lessor_po_box_english'))), $field('صندوق البريد', e($cfg('lessor_po_box_arabic')))],
                                    [
                                        $field('CR No.', $withLtrIds($cfg('lessor_cr_no_english'))) . ($estId ? ' · <span class="lbl">Est ID:</span> <b class="ltr-id">' . e($estId) . '</b>' : ''),
                                        $field('السجل التجاري', $withLtrIds($cfg('lessor_cr_no_arabic'))) . ($estId ? ' · <span class="lbl">قيد المنشأة:</span> <b class="ltr-id">' . e($estId) . '</b>' : ''),
                                    ],
                                    [$field('Authorized by', e($cfg('lessor_authorized_by_english'))), $field('ويمثلها', e($cfg('lessor_authorized_by_arabic')))],
                                    [$field('QID No.', e($cfg('lessor_qid_no'))), $field('بطاقة شخصية رقم', e($cfg('lessor_qid_no')))],
                                    [$field('Nationality', e($cfg('lessor_nationality'))), $field('الجنسية', e($cfg('lessor_nationality')))],
                                    $cfg('lessor_mobile') ? [$field('Mobile', e($cfg('lessor_mobile'))), $field('رقم هاتف', e($cfg('lessor_mobile')))] : null,
                                    [$field('Email', e($cfg('lessor_email'))), $field('البريد الإلكتروني', e($cfg('lessor_email')))],
                                    [$field('Tel.', e($cfg('lessor_tel_fax'))), $field('تليفون', e($cfg('lessor_tel_fax')))],
                                ]);
                                $customer = $rentOut->customer;
                                $purchaserLines = [
                                    [$field('QID', e($customer?->id_no)), $field('البطاقة الشخصية', e($customer?->id_no))],
                                    [$field('Nationality', e($customer?->nationality)), $field('الجنسية', e($customer?->nationality))],
                                    [$field('Mobile', e($customer?->mobile)), $field('الجوال', e($customer?->mobile))],
                                    [$field('Email', e($customer?->email)), $field('البريد الإلكتروني', e($customer?->email))],
                                    [$field('P.O. Box', e($customer?->po_box)), $field('ص.ب', e($customer?->po_box))],
                                ];
                                $parking = $rentOut->property?->parking ? ' (' . e($rentOut->property->parking) . ')' : '';
                                $projectLines = [
                                    [$field('Project Land', e($rentOut->group?->name)), $field('أرض المشروع', e($rentOut->group?->arabic_name))],
                                    [$field('Building', e($rentOut->building?->name)), $field('المبنى', e($rentOut->building?->arabic_name))],
                                    [$field('Unit No. / Floor', e($rentOut->property?->number) . ' / ' . e($floor)), $field('رقم الوحدة / الطابق', e($rentOut->property?->number) . ' / ' . e($floor))],
                                    [$field('Parking', 'One Parking' . $parking), $field('موقف السيارات', 'موقف واحد' . $parking)],
                                ];
                            @endphp

                        </table>

                        <table class="reg group">
                            <!-- First Party -->
                            <tr class="group-head">
                                <td colspan="2"><div><span>The First Party (Seller)</span><span lang="ar">الطرف الأول (البائع)</span></div></td>
                            </tr>
                            <tr class="line">
                                <td class="bold">{{ $cfg('lessor_name_en') }}</td>
                                <td lang="ar" class="bold">{{ $cfg('lessor_name_ar') }}</td>
                            </tr>
                            @foreach ($sellerLines as [$english, $arabic])
                                <tr class="line"><td>{!! $english !!}</td><td lang="ar">{!! $arabic !!}</td></tr>
                            @endforeach
                            <tr class="line">
                                <td><p class="here">{Hereinafter referred to as the first party or Seller}</p></td>
                                <td lang="ar"><p class="here">{ويشار إليها فيما بعد بالطرف الأول أو البائع}</p></td>
                            </tr>

                        </table>

                        <table class="reg group">
                            <!-- Second Party -->
                            <tr class="group-head">
                                <td colspan="2"><div><span>The Second Party (Purchaser)</span><span lang="ar">الطرف الثاني (المشتري)</span></div></td>
                            </tr>
                            <tr class="line">
                                <td>Mr./Mrs. <b>{{ $customer?->name }}</b></td>
                                <td lang="ar">السيد/ة <b>{{ $customer?->name }}</b></td>
                            </tr>
                            @foreach ($purchaserLines as [$english, $arabic])
                                <tr class="line"><td>{!! $english !!}</td><td lang="ar">{!! $arabic !!}</td></tr>
                            @endforeach
                            <tr class="line">
                                <td><p class="here">{Hereinafter referred to as the second party – Purchaser}</p></td>
                                <td lang="ar"><p class="here">{ويشار إليه فيما بعد بالطرف الثاني أو المشتري}</p></td>
                            </tr>

                        </table>

                        <table class="reg group">
                            <!-- Project Details -->
                            <tr class="group-head">
                                <td colspan="2"><div><span>Project Details</span><span lang="ar">تفاصيل المشروع</span></div></td>
                            </tr>
                            @foreach ($projectLines as [$english, $arabic])
                                <tr class="line"><td>{!! $english !!}</td><td lang="ar">{!! $arabic !!}</td></tr>
                            @endforeach

                        </table>

                        <table class="reg">
                            <!-- Preliminary Clause -->
                            <tr>
                                <td>
                                    <h3>Preliminary Clause</h3>
                                    <p>
                                        The First Party owns the plot of land located in the Lusail area, registered under number (69060039) in the Real Estate Registry, with a total area of (25,120) square meters,
                                        and covered by Building Permit. The land is designated for the {{ $rentOut->group?->name }} residential tower project, in accordance with the architectural drawings and
                                        specifications approved by the official authorities. The entire project shall be completed, and a Building Completion Certificate shall be obtained in accordance with the
                                        provisions of Law No. (6) of 2014 concerning the regulation of real estate development, as stipulated in Chapter One under the Definitions section.
                                    </p>
                                </td>
                                <td lang="ar">
                                    <h3>بند تمهيدي</h3>
                                    <p>
                                        يمتلك الطرف الأول قطعة الأرض الكائنة في منطقة لوسيل المشهرة برقم (69060039) في السجل العقاري والبالغ مساحتها (25,120) متر مربع بترخيص بناء الموصوف بمشروع {{ $rentOut->group?->arabic_name }} برج سكني – وذلك وفق الرسومات والمواصفات الهندسية التي تم اعتمادها من الجهات الرسمية، سوف يتم الانتهاء من كامل المشروع واستخراج شهادة اتمام بناء وفق أحكام القانون رقم (6) لسنة 2014 الخاص بتنظيم التطوير العقاري الوارد في الفصل الأول من باب التعاريف.
                                    </p>
                                </td>
                            </tr>
                            <tr class="cont">
                                <td>
                                    <p>
                                        The second party has confirmed the will to purchase a residential unit in this project, and according to the provisions
                                        of this contract and the price mentioned therein, the two parties have agreed to draft this contract under the following legal and lawful conditions:
                                    </p>
                                </td>
                                <td lang="ar">
                                    <p>
                                        فقد أبدى الطرف الثاني رغبته في شراء وحدة سكنية بهذا المشروع ووفقاً لأحكام هذا القانون وبالثمن الذي تم ذكره في هذا العقد فقد تم الاتفاق بين الطرفين على تحرير هذا العقد بالشروط والأوضاع الشرعية والقانونية التالية:
                                    </p>
                                </td>
                            </tr>

                            <!-- Unit -->
                            <tr>
                                <td>
                                    <p>
                                        <b>Unit No.: {{ $rentOut->building?->name }} – Unit ({{ $rentOut->property?->number }}) – {{ $floorOrdinal }} Floor – {{ $rentOut->type?->name }} Apartment.</b>
                                        The area of this unit on the map and the architectural drawing of the project of {{ $rentOut->group?->name }} (<b>{{ $rentOut->property?->size }} square meters</b>) is subject to
                                        deficit and surplus, and the common facilities in the tower are added, which are the common areas of the apartment as specified in the (Terms and Conditions of Apartment Sale).
                                    </p>
                                    <p><b>Parking:</b> One Parking</p>
                                </td>
                                <td lang="ar">
                                    <p>
                                        <b>رقم الشقة: {{ $rentOut->building?->arabic_name }} – شقة رقم ({{ $rentOut->property?->number }}) – الدور {{ $floor }} – شقة {{ $rentOut->type?->arabic_name ?: $rentOut->type?->name }}</b>،
                                        تبلغ مساحة هذه الوحدة على الخارطة والرسم الهندسي لمشروع {{ $rentOut->group?->arabic_name }} (<b>{{ $rentOut->property?->size }} متر مربع</b>) تحت العجز والزيادة شاملة الأجزاء المشتركة في البرج وهي ملحقات الشقة (كما هو محدد في "شروط واحكام بيع الشقة").
                                    </p>
                                    <p><b>مواقف السيارات:</b> موقف عدد 1</p>
                                </td>
                            </tr>

                            <!-- Expected Completion -->
                            <tr>
                                <td>
                                    <h3>Expected Completion Period</h3>
                                    <p><b>Last quarter of 2029</b></p>
                                    <p>
                                        The two parties of this contract have agreed that the expected completion date represents the anticipated date for handing over the apartment, so that it will be
                                        ready for occupancy by Dec 31, 2029, extendable for an additional period of Twelve months (12) as a grace period without any responsibility on the part of the first
                                        party, the seller. In the event of extending the expected completion date for any reason (including government approvals), the actual delivery date will be determined
                                        by a written letter sent by the seller to the buyer at least thirty (30) days before the delivery date.
                                    </p>
                                </td>
                                <td lang="ar">
                                    <h3>تاريخ الإنجاز المتوقع</h3>
                                    <p><b>الربع الرابع من سنة 2029</b></p>
                                    <p>
                                        اتفق الطرفان في العقد على أن تاريخ الإنجاز المتوقع يمثل التاريخ المتوقع حالياً وأن تسليم الشقة ستكون جاهزة للإشغال في تاريخ 2029/12/31 يمكن تمديده لمدة (12) شهر إضافية كفترة سماح دون أي مسؤولية على الطرف الأول البائع. وفي حالة تمديد تاريخ الإنجاز المتوقع لأي سبب كان (من ضمنها موافقات الجهات الحكومية) سيتم تحديد تاريخ الاستلام الفعلي بموجب كتاب خطي يرسله البائع الى المشتري قبل تاريخ الاستلام بثلاثين (30) يوماً على الأقل.
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <!-- Schedule 1: Payment Schedule -->
                        <div class="page-break"></div>
                        <div class="reg-sched-title">
                            <div class="main">SCHEDULE 1 – <span lang="ar">الجدول 1</span></div>
                            <div class="sub">Schedule of Instalment Payments / <span lang="ar">جدول تقسيط الدفعات</span></div>
                        </div>

                        <table class="reg-data">
                            <tr>
                                <td style="width: 34%;"><b>Project:</b> {{ $rentOut->group?->name }}</td>
                                <td style="width: 33%;"><b>Building:</b> {{ $rentOut->building?->name }}</td>
                                <td style="width: 33%;"><b>Unit:</b> {{ $rentOut->property?->number }} - {{ $rentOut->type?->name }} Apartment</td>
                            </tr>
                        </table>

                        {{-- Rows never split across pages and the <thead> repeats at the
                             top of every page the table spans. --}}
                        <table class="reg-data">
                            <thead>
                                <tr>
                                    <th style="width: 34%;">Instalment<span lang="ar">رقم القسط</span></th>
                                    <th style="width: 14%;">Percentage<span lang="ar">النسبة (%)</span></th>
                                    <th>Date<span lang="ar">التاريخ</span></th>
                                    <th style="width: 26%;">Amount (QAR)<span lang="ar">القيمة (ريال قطري)</span></th>
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
                                        <td class="r num">{{ getPercentage($item->total ?? $item->amount, $rentOut->rent, 2, true) }}</td>
                                        <td class="c num">{{ systemDate($item->due_date) }}</td>
                                        <td class="r num">{{ currency($item->total ?? $item->amount) }}</td>
                                    </tr>
                                @endforeach
                                @php
                                    $total = $installments->sum(fn ($t) => $t->total ?? $t->amount);
                                @endphp
                                <tr class="total">
                                    <td>Total · <span lang="ar">الإجمالي</span></td>
                                    <td class="r num">{{ getPercentage($total, $rentOut->rent, 2, true) }}</td>
                                    <td></td>
                                    <td class="r num">{{ currency($total) }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Purchase Price + Contract Signatures -->
                        <div class="avoid-break">
                            <table class="reg-data">
                                <tr>
                                    <td style="width: 50%;"><h4>Purchase Price in Words · <span lang="ar">سعر الشراء كتابة</span></h4></td>
                                    <td style="width: 25%;"><h4>Purchase Price · <span lang="ar">سعر الشراء</span></h4></td>
                                    <td style="width: 25%;"><h4>Stamp · <span lang="ar">ختم</span></h4></td>
                                </tr>
                                <tr>
                                    <td class="c" style="height: 22mm;">
                                        <div style="text-transform: capitalize;">{{ $numberToWord['english'] }} Qatari Riyals Only</div>
                                        <div lang="ar" style="text-align: center;">{{ $numberToWord['arabic'] }}</div>
                                    </td>
                                    <td class="c bold num" style="font-size: 11pt;">QAR {{ currency($rentOut->rent) }}</td>
                                    <td></td>
                                </tr>
                            </table>

                            <table class="reg">
                                <tr>
                                    <td>
                                        <b>Authorized signatory for and on behalf of the First Party</b>
                                        <div lang="ar">المفوض بالتوقيع عن الطرف الأول</div>
                                        <div class="sign-space"></div>
                                    </td>
                                    <td>
                                        <b>Handwritten signature – Second Party</b>
                                        <div lang="ar">توقيع الطرف الثاني بخط اليد</div>
                                        <div class="sign-space"></div>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        {{-- Articles 1-17: English | article number | Arabic --}}
                        <div class="page-break"></div>
                        <table class="reg">
                            <colgroup><col><col class="no"><col></colgroup>
                            <tr>
                                <td>
                                    <h3>Article One</h3>
                                    <p>The preliminary section is considered an integral part of this contract and is complement to its provisions, in addition to the approved plans and architecture drawings from the relevant authorities, as well as the appendixes accompanying this contract.</p>
                                </td>
                                <td class="no"><span>1</span></td>
                                <td lang="ar">
                                    <h3>البند الأول</h3>
                                    <p>يعتبر الجزء التمهيدي جزء لا يتجزأ من هذا العقد متمماً له ولبنوده – اضافة الى المخططات والرسومات الهندسية المعتمدة من الجهات المختصة والملاحق المرفقة لهذا العقد.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Article Two</h3>
                                    <p>The first party has sold, transferred, and relinquished with all legal and actual warranties to the second party, a specified-bounded unit described in the sales map (off plan sale) for the project of {{ $rentOut->group?->name }} in the Lusail area, on the land plot number (69060039) registered in the real estate registry. The unit is complete with doors, windows, outlets, and services such as water, lighting, sewage, and gas, with a fully furnished finishing system.</p>
                                </td>
                                <td class="no"><span>2</span></td>
                                <td lang="ar">
                                    <h3>البند الثاني</h3>
                                    <p>باع وأسقط وتنازل الطرف الأول بكافة الضمانات القانونية والفعلية للطرف الثاني القابل لذلك ما هو عبارة عن الوحدة محددة الحدود مع الأوصاف بنظام البيع على الخارطة في مشروع {{ $rentOut->group?->arabic_name }} بمنطقة لوسيل على قطعة الأرض رقم (69060039) المشهرة في التسجيل العقاري. مستكملة الأبواب والنوافذ والمنافذ والخدمات من مياه وإنارة وصرف صحي وغاز بنظام تشطيب كامل مفروش.</p>
                                </td>
                            </tr>
                            <tr class="cont">
                                <td>
                                    <p>In this sale a share in the land, property benefits, and common parts such as building sections designated for shared use, main walls, facilities, corridors, roofs, hallways, elevators, swimming pool, gym will be added to the mentioned size below, and the share will be defined based on final strata, Annual service charge fees will be calculated accordingly.</p>
                                    <p>The area of the property sold is approximately ({{ $rentOut->property?->size }} sqm) square meters, subject to deficiency and excess based on engineering and architectural design.</p>
                                    <p>– “The area mentioned above represents the total of the unit area and the commons areas together. Service charges are calculated based on this total area, referred to in this contract as the total area or the comprehensive area.”</p>
                                </td>
                                <td class="no"></td>
                                <td lang="ar">
                                    <p>ويضاف لهذا البيع حصة في الأرض ومنافع العقار والأجزاء المشتركة مثل أجزاء البناء المعدة للاستعمال المشترك والجدران الرئيسية والمرافق والمناور والأسطح والدهاليز والمصاعد وحمام السباحة والحصة يتم احتسابها عند الفرز النهائي للعقار حيث سيتم احتساب رسوم الخدمات السنوية بناء عليه.</p>
                                    <p>ومساحة العقار الذي يقع للوحدة المباعة ({{ $rentOut->property?->size }} متر مربع) متر مربع تقريباً تحت العجز والزيادة – حسب ما يرد في المخططات الهندسية النهائية.</p>
                                    <p>• تمثل المساحة المذكورة أعلاه مجموع مساحتي الوحدة و المناطق المشتركة سويا ويتم احتساب رسوم الخدمات بناء على مجموع المساحتين و يشار اليها في هذا العقد بالمساحة الكلية او المساحة الشاملة.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Article Three</h3>
                                    <p>This sale has been finalized for a total amount of ({{ currency($rentOut->rent) }}) – only (<span style="text-transform: capitalize;">{{ $numberToWord['english'] }}</span> Qatari Riyals). The second party has made a payment following the specified installment plan, as agreed between both parties in this contract. The first party acknowledges receiving the down payment, according to the installment schedule attached to this contract. The signing of this contract is not considered a final settlement of the agreed-upon price until all installments are paid.</p>
                                </td>
                                <td class="no"><span>3</span></td>
                                <td lang="ar">
                                    <h3>البند الثالث</h3>
                                    <p>تم هذا البيع نظير مبلغ اجمالي وقدره ({{ currency($rentOut->rent) }}) – فقط ({{ $numberToWord['arabic'] }} ريال قطري) لا غير وقد قام الطرف الثاني بدفع المبلغ ثمن الوحدة وبالنظام المحدد بطريقة أقساط الى الطرف الأول بموجب هذا العقد ويقر الأخير باستلامه الدفعة المقدمة حسب نظام جدول الأقساط المرفقة لهذا العقد ويعتبر التوقيع على هذا العقد ليس بمخالصة نهائية بالثمن إلا بعد سداد كامل الأقساط.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Article Four</h3>
                                    <p>The first party acknowledges that the ownership of the land on which the project is established, registered under number (69060039) in the real estate registry or in the Lusail area, has been transferred to the property developer through purchase. Both parties are aware that the ownership of the second party for the sold unit, after its completion and handover, is subject to the provisions of real estate ownership stipulated by civil law.</p>
                                </td>
                                <td class="no"><span>4</span></td>
                                <td lang="ar">
                                    <h3>البند الرابع</h3>
                                    <p>يقر الطرف الأول بأن ملكية الأرض المقام عليها المشروع المشهرة برقم (69060039) بالسجل العقاري أو منطقة لوسيل – لدى المطور العقاري لها – قد الت اليه عن طريق الشراء كما أنه من المعلوم للطرفين أن تملك الطرف الثاني للوحدة المباعة بعد استلامها بعد اتمام المشروع يخضع لأحكام ملكية الطبقات المنصوص عليها بالقانون المدني.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Article Five</h3>
                                    <p>The buyer acknowledges, agrees, and understands that they are obligated to directly pay service fees/charges to the seller or the company managing the tower during the first three years extendable (the manager), appointed by the seller for this purpose. The company determines the recurring service fees, which are payable based on the actual cost of services, plus any other relevant fees (if applicable). This is to contribute to the maintenance, administration, and supervision expenses of shared facilities, properties, and designated common areas, as calculated and due according to the terms of the announcement.</p>
                                </td>
                                <td class="no"><span>5</span></td>
                                <td lang="ar">
                                    <h3>البند الخامس</h3>
                                    <p>يقر المشتري ويوافق ويفهم أنه ملزم بدفع رسوم الخدمة مباشرة للبائع أو الشركة التي ستدير البرج خلال الثلاث سنوات الأولى قابلة للتجديد (المدير) التي سيتم تعيينها من قبل البائع لهذا الغرض. تقوم الشركة بتحديد رسوم الخدمة المذكورة والمتوجب دفعها على اساس التكلفة الفعلية من رسوم الخدمات مضاف اليها اي رسوم أخرى ذات صلة (ان وجد)، وذلك من أجل المساهمة في نفقات الصيانة والإدارة والإشراف على مرافق الاستخدام المشترك والممتلكات والأجزاء المشتركة المحسوبة والمستحقة الدفع وفقاً لأحكام الإعلان.</p>
                                </td>
                            </tr>
                            <tr class="cont">
                                <td>
                                    <p>The buyer agrees and undertakes to comply with the provisions of the management agreement and community rules and undertakes to ensure that the occupants of the apartment also comply with them. The buyer also undertakes to pay the service fees on their due dates. The buyer also acknowledges that the manager is authorized (any authority that can be delegated) to take any action they deem necessary to collect the service fees in case of non-payment, including initiating legal proceedings. The buyer also agrees that the manager has the right to place a lien on the apartment for any unpaid service fees, and the buyer agrees that the manager has the right to initiate legal proceedings against them to collect unpaid service fees. The buyer also acknowledges that the project and common areas are exclusively managed by the manager in accordance with the terms and conditions of the management agreement, in exchange for the payment of management fees to the company as per applicable laws. The buyer also agrees to the manager’s powers under the management agreement and agrees to abide by the rules enforced by the manager throughout the duration of the management agreement.</p>
                                </td>
                                <td class="no"></td>
                                <td lang="ar">
                                    <p>يوافق المشتري ويتعهد بالامتثال لأحكام اتفاقية الإدارة وقواعد المجتمع ويتعهد بضمان امتثال شاغلي الشقة لذلك أيضا، ويتعهد بسداد رسوم الخدمة في مواعيد استحقاقها. يقر المشتري أيضا بان المدير مفوضين (أي سلطة يمكن تفويضها) باتخاذ أي اجراء يرونه ضروريا لتحصيل قيمة رسوم الخدمة في حال عدم سدادها، بما في ذلك مباشرة الإجراءات القانونية. يوافق المشتري كذلك على ان للمدير حق حجز على الشقة مقابل أي رسوم خدمات غير مدفوعة ويوافق المشتري على انه يحق للمدير مباشرة الإجراءات القانونية ضده لتحصيل رسوم الخدمة غير المسددة. يقر المشتري كذلك بان المشروع و الأجزاء المشتركة يتم ادارتها بشكل حصري من قبل المدير طبقا لشروط واحكام اتفاقية الإدارة وبمقابل سداد لإدارة الشركة كرسوم الإدارة طبقا للقوانين المرعية. يوافق المشتري أيضا على صلاحيات المدير طبقا لاتفاقية الإدارة، ويوافق على تطبيق القواعد من قبل المدير طوال مدة اتفاقية الإدارة.</p>
                                </td>
                            </tr>
                            <tr class="cont">
                                <td>
                                    <p>As of the completion date, the buyer is required to settle all fees (including operation fees to the apartment) for water, electricity, sewage, chilled water, air conditioning (separate bills), and any and all other services and facilities related to the apartment, in addition to paying all property taxes (if any), service fees, municipality fees in Lusail, home fees, and any other similar fees that may be imposed or levied regarding the apartment.</p>
                                    <p>The buyer must comply in all circumstances with the provisions of the law, decrees, regulations, governing documents, and the governing community of documents and rules, as well as any other requirements from any relevant authority or from the seller or the manager regarding the project, the apartment, or the common areas.</p>
                                </td>
                                <td class="no"></td>
                                <td lang="ar">
                                    <p>اعتبارا من تاريخ الإنجاز، يتوجب على المشتري سداد جميع رسوم (بما في ذلك رسوم التشغيل الى الشقة) استهلاك المياه والكهرباء و الصرف الصحي والمياه المبردة والتكييف (فواتير منفصلة) واي وجميع الخدمات والمرافق الأخرى المتعلقة بالشقة، بالإضافة الى سداد جميع ضرائب العقارات (ان وجدت) ورسوم الخدمة ورسوم بلدية لوسيل ورسوم المنزل وباقي الرسوم المشابهة الأخرى التي قد يتم فرضها او جبايتها فيما يتعلق بالشقة.</p>
                                    <p>يجب على المشتري الامتثال في جميع الأحوال لأحكام القانون والمراسيم والقواعد والمستندات الحاكمة والمجتمع الحاكم للمستندات والقواعد، واي متطلبات أخرى من أي جهة ذات صلة او من البائع او من المدير فيما يتعلق بالمشروع او الشقة او الأجزاء المشتركة.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Article Six</h3>
                                    <p>The second party, the buyer, acknowledges that they have examined the approved plans and drawings of the project during its construction phase – in person and with the eye – for the sold unit and its annexes. This examination is a legally and lawfully valid denial of ignorance. The buyer accepts the unit in its current condition, under the agreed-upon terms, and cannot hold the first party accountable for any issues in the future. The built area based on the plans and drawings is subject to deficiency and excess, and the second party does not have the right to reduce the unit price or terminate the contract due to this. The buyer’s signature on this preliminary contract is an affirmation of their consent to the area specified in the plans and drawings, a binding acknowledgment with no recourse, and this acknowledgment is valid upon unit receipt.</p>
                                </td>
                                <td class="no"><span>6</span></td>
                                <td lang="ar">
                                    <h3>البند السادس</h3>
                                    <p>يقر الطرف الثاني – المشتري – بأنه قد عاين المخططات والرسومات الهندسية المعتمدة قيد التشييد وبناء المشروع – على الطبيعة وبالعين المجردة للوحدة المبيعة وملحقاتها – المعاينة النافية للجهالة شرعاً وقانوناً وقبلها على حالتها وبالشروط المتفق عليها ولا يحق له الرجوع على الطرف الأول مستقبلاً بسبب ذلك وأن المساحة المبنية على المخططات والرسومات تعد تحت العجز والزيادة ولا يحق للطرف الثاني تخفيض ثمن الوحدة بسبب ذلك أو فسخ العقد وأن توقيعه على هذا العقد الإبتدائي بمثابة اقرار منه بالموافقة على المساحة الواردة بالمخططات والرسومات واقرار لا رجعة فيه وذلك عند استلامه الوحدة.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Article Seven</h3>
                                    <p>The first party affirms to the second party that the sold unit, according to the map, is free from all real property rights, regardless of their type or nature, such as privileges, mortgages, or usufruct rights. Additionally, the unit is clear of any personal rights belonging to third parties. The first party commits to preventing any future exposure of the second party to third-party claims arising from any of the mentioned rights.</p>
                                </td>
                                <td class="no"><span>7</span></td>
                                <td lang="ar">
                                    <h3>البند السابع</h3>
                                    <p>يقر الطرف الأول للطرف الثاني بأن الوحدة المباعة بنظام الخارطة خالية من كافة الحقوق العينية أياً كان نوعها أو خفية مثل حقوق الإمتياز أو الرهن أو الوقف وحقوق الإنتفاع وكذلك يخلوها من كافة الحقوق الشخصية للغير ويلتزم بمنع أي تعرض قد يحدث مستقبلاً للطرف الثاني من الغير ويكون ناشئاً عن أي من هذه الحقوق المذكورة.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Article Eight</h3>
                                    <p>The second party acknowledges that they are prohibited from engaging in any activities that could compromise the common usufruct or harm other property owners or occupants. They are not allowed to make any modifications to the property’s façade or install any fixtures that could adversely affect the external aesthetic appearance of the tower.</p>
                                </td>
                                <td class="no"><span>8</span></td>
                                <td lang="ar">
                                    <h3>البند الثامن</h3>
                                    <p>يقر الطرف الثاني بأنه ممنوع من القيام بأي من الأعمال من شأنها أن تخل بالإنتفاع المشترك أو تضر بباقي الملاك أو شاغليه. كما لا يجوز له أن يجري أي تعديلات بواجهة العقار أو انه يضع فيها أي تركيبات ممن يكون من شأنه أن يخل بالشكل الخارجي الجمالي للبرج.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Article Nine</h3>
                                    <p>The first party undertakes to initiate the registration procedures of the segregated unit according to the provisions of the Real Estate Registration Law in the Land Registry after the completion of the project and the issuance of a completion certificate upon full payment of the price, in preparation for the issuance of a title deed to be delivered to the second party that is after the consultant finishes the subdivision and allocation survey (Strata) by the first party.</p>
                                </td>
                                <td class="no"><span>9</span></td>
                                <td lang="ar">
                                    <h3>البند التاسع</h3>
                                    <p>يلتزم الطرف الأول باتخاذ اجراءات تسجيل الوحدة المفرزة وفقاً لأحكام قانون التسجيل العقاري بالسجل العقاري بعد اتمام المشروع واستخراج شهادة اتمام بناء بعد سداد كامل الثمن وذلك تمهيداً للإستخراج سند ملكية وتسليمها للطرف الثاني بعد أن ينتهي استشاري الفرز والتجنيب من فرز العين من قبل الطرف الأول.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Article Ten</h3>
                                    <p>The Buyer shall pay all installments due to the Seller on the due dates and up to the date of full payment. In the event of non-payment and default for a period of thirty (30) days from the due date of the installment, the First Party, after undertaking the necessary legal procedures related to the cheque for the due installment, shall issue an initial legal notice to the Buyer, granting the Buyer a period not exceeding thirty (30) days to notify the Buyer of the necessity to settle the overdue payments. Should the Buyer fail to comply with the content of the initial notice, the Buyer shall be notified by a second written notice requiring immediate payment to avoid breaching the contractual obligations, with a grace period not exceeding thirty (30) days.</p>
                                    <p>Upon the expiry of the notice period, the Buyer shall be deemed to have defaulted on their payment obligation for a period of ninety (90) days (from the installment due date). Consequently, the First Party shall have the right to terminate the contract by mutual agreement, subject to conditions which may include, but are not limited to, the First Party offering the unit for sale until it is sold and subsequently completing the necessary procedures with the Second Party under this contract regarding the overdue payments, installments, and all financial obligations. Alternatively, the First Party may repurchase the unit from the Second Party under the terms and price determined by the First Party in this contract.</p>
                                    <p>In the event the parties resort to such measures, the contract shall be deemed mutually terminated, and an addendum shall be prepared specifying the procedures accompanying the mutual termination.</p>
                                </td>
                                <td class="no"><span>10</span></td>
                                <td lang="ar">
                                    <h3>البند العاشر</h3>
                                    <p>يجب على المشتري دفع جميع الأقساط المستحقة للبائع في تاريخ الاستحقاق وحتى تاريخ الوفاء التام. في حالة عدم السداد والتقصير بالدفع لمدة ثلاثين (30) يوم من تاريخ استحقاق الدفعة، يكون للطرف الأول بعد اتخاذ الإجراءات القانونية اللازمة فيما يتعلق بالشيك الخاص بالدفعة المستحقة، تقديم إشعار قانوني اولي و امهال المشتري فترة لا تزيد عن ثلاثين (30) لأخطار المشتري بضرورة سداد المتأخرات وفي حال عدم امتثال المشتري لفحوى الاخطار الأول يخطر المشتري بموجب اخطار كتابي ثاني بضرورة السداد الفوري تفاديا من الاخلال بالالتزامات العقدية مع امهال المشتري لمدة لا تتجاوز 30 يوم وبانتهاء فترة الاخطار المذكور يكون المشتري مخلا بالالتزام المفروض عليه بالسداد لمدة 90 يوم (من تاريخ استحقاق الدفعة) وعليه يحق للطرف الأول فسخ العقد اتفاقيا بشروط قد تشتمل على سبيل المثال لا الحصر، قيام الطرف الأول بعرض الوحدة للبيع حتى يتم بيعها و استيفاء ثمنها ومن ثم اكمال الإجراءات مع الطرف الثاني بهذا العقد فيما يتعلق بالمتأخرات و الدفعات و كافة الالتزامات المالية، او ان يقوم الطرف الأول بإعادة شراء الوحدة من الطرف الثاني بالشروط و السعر الذي يقدره الطرف الأول بهذا العقد وفي حال لجوء الأطراف الى هذه التدابير يعد العقد مفسوخا بشكل اتفاقي ويتم اعداد ملحق يتم فيه تحديد الإجراءات المصاحبة للفسخ الاتفاقي.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Article Eleven</h3>
                                    <p>The parties to this contract agree that in the event of a breach of any obligations by either party arising from this contract, the defaulting party shall be obligated to pay the other party a compensation of (100.000 Qatari Riyal) One Hundred Thousand Qatari Riyal Only.</p>
                                </td>
                                <td class="no"><span>11</span></td>
                                <td lang="ar">
                                    <h3>البند الحادي عشر</h3>
                                    <p>اتفق أطراف هذا العقد بأنه في حال اخلال أي طرف لأي من التزاماته الناشئة عن هذا العقد يكون ملزماً بأن يدفع للطرف الأخر تعويض مبلغ (100.000 ريال قطري) مائة ألف ريال قطري فقط لا غير.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Article Twelve</h3>
                                    <p>The second party undertakes to bear all expenses, fees, and charges related to the demarcation and survey of their unit at the Ministry of Justice, as well as all periodic fees or expenses for the maintenance of the unit and any subsequent maintenance activities. The specific details and items to be covered by these expenses will be clarified and specified in a separate agreement.</p>
                                </td>
                                <td class="no"><span>12</span></td>
                                <td lang="ar">
                                    <h3>البند الثاني عشر</h3>
                                    <p>يلتزم الطرف الثاني بأن يقع على عاتقه كافة المصروفات ورسوم وأتعاب انهاء الفرز والتجنيب لوحدته لدى وزارة العدل وكافة الرسوم أو المصاريف الدورية لصيانة الوحدة وكل ما يتبعه بصيانة الوحدة وذلك حسب ما سوف يتم بيانه وتحديده.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Article Thirteen</h3>
                                    <p>In the event of the buyer taking action on the unit after fulfilling the conditions stated in this contract, the buyer commits to paying 2% of the resale value to the seller as administrative fees.</p>
                                </td>
                                <td class="no"><span>13</span></td>
                                <td lang="ar">
                                    <h3>البند الثالث عشر</h3>
                                    <p>في حال التصرف في الوحدة بعد استيفاء الشروط المذكورة في هذا العقد، يلتزم المشتري بسداد 2% من قيمة اعادة البيع الى البائع كرسوم إدارية.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Article Fourteen</h3>
                                    <p>This contract is subject to the provisions of Law No. 6 of the year 2014 concerning the regulation of real estate development.</p>
                                </td>
                                <td class="no"><span>14</span></td>
                                <td lang="ar">
                                    <h3>البند الرابع عشر</h3>
                                    <p>يخضع هذا العقد لأحكام القانون رقم 6 لسنة 2014 الخاص بتنظيم التطوير العقاري.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Article Fifteen</h3>
                                    <p>This contract is drafted in two copies, each having the same legal effect, to be used as needed by the parties involved.</p>
                                </td>
                                <td class="no"><span>15</span></td>
                                <td lang="ar">
                                    <h3>البند الخامس عشر</h3>
                                    <p>حرر هذا العقد من نسختين – نسخة لكل طرف للعمل عليها عند اللزوم.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Article Sixteen</h3>
                                    <p>The prementioned addresses in this contract shall be considered as the valid addresses for notifications, correspondence, and any potential disputes.</p>
                                </td>
                                <td class="no"><span>16</span></td>
                                <td lang="ar">
                                    <h3>البند السادس عشر</h3>
                                    <p>تعتبر العناوين المسبقة بصدد هذا العقد هي التي يعتد بها في الإخطارات والمراسلات والمنازعات لا قدر الله.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Article Seventeen</h3>
                                    <p>The appendices attached to this contract are integral to it, and they constitute an inseparable part of its terms and conditions.</p>
                                </td>
                                <td class="no"><span>17</span></td>
                                <td lang="ar">
                                    <h3>البند السابع عشر</h3>
                                    <p>تعتبر الملاحق المرفقة لهذا العقد متمماً له ولبنوده وشروطه وتعد جزأً لا يتجزأ منه.</p>
                                </td>
                            </tr>
                        </table>

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
