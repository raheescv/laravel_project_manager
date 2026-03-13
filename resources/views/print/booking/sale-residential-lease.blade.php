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
@endphp
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>AGREEMENT FOR SALE OF SINGLE UNIT – OFF PLAN</title>
    @include('print.booking.components.styles')
</head>

<body>
    <div style="width: 100%; line-height: 10;">
        @if ($bondPaperMode)
            <div style="width: 100%; height: {{ $logoHeight }}px;"></div>
        @elseif($residentialLogoLeaseUrl)
            <img src="{{ $residentialLogoLeaseUrl }}" alt="Logo" style="width: 100%; height: auto; display: block;">
        @endif
    </div>
    <div class="container">

        <!-- Header Table -->
        <div id="header_container" style="display: flex; align-items: flex-start; gap: 20px;">
            <table class="header-table" style="flex: 1;">
                <tr>
                    <td rowspan="3" style="width: 15%; text-align: left;">Contract Serial</td>
                    <td style="width: 20%;">Sales Order No.</td>
                    <td style="width: 25%; text-align: center;">{{ $rentOut->agreement_no }}</td>
                    <td style="width: 20%; text-align: right;" lang="ar">رقم أمر البيع</td>
                    <td rowspan="3" style="width: 15%; text-align: right;" lang="ar">الرقم التسلسلي للعقد</td>
                </tr>
                <tr>
                    <td>Customer No.</td>
                    <td style="width: 25%; text-align: center;">{{ $rentOut->id }}</td>
                    <td style="width: 20%; text-align: right;" lang="ar">رقم العميل</td>
                </tr>
                <tr>
                    <td style="">Location Code</td>
                    <td style="width: 25%; text-align: center;">{{ $rentOut->group?->name }}</td>
                    <td style="width: 20%; text-align: right;" lang="ar">الموقع</td>
                </tr>
            </table>

            <!-- QR Code -->
            <div style="text-align: center; flex-shrink: 0;" id='qr_code_container'>
                <div style="margin-bottom: 1px; display: inline-block; padding: 1px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); background: white; border-radius: 4px; position: relative; z-index: 99999999; isolation: isolate; mix-blend-mode: normal;">
                    <img style="width: 60px; height: 50px;" src="data:image/png;base64,{{ DNS2D::getBarcodePNG(url('property/sale/booking/view/' . $rentOut->id), 'QRCODE', 5, 5) }}" alt="QR Code">
                    <br>
                    <small>969897909-{{ str_pad($rentOut->id, 4, '0', STR_PAD_LEFT) }}</small>
                </div>
            </div>
        </div>

        <!-- Title -->
        <div class="row">
            <div class="cell cell-en text-center">
                <div class="title-text" style="text-align: left;">AGREEMENT FOR SALE OF SINGLE UNIT – OFF PLAN</div>
                <div class="subtitle-text" style="text-align: left;">Particulars of Sale and Purchase</div>
            </div>
            <div class="cell cell-ar text-center" lang="ar">
                <div class="title-text" style="text-align: right;">عقد بيع شقة واحدة – على الخارطة</div>
                <div class="subtitle-text" style="text-align: right;">تفاصيل البيع والشراء</div>
            </div>
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
                    <p class="small-text">P.O. Box: <span class="underline">{{ $cfg('lessor_po_box') }}</span>, Doha, Qatar</p>
                    <p class="small-text">CR No. <span class="underline">{{ $cfg('lessor_cr_no') }}</span></p>
                    <p class="small-text">AUTHORIZED BY {{ $cfg('lessor_authorized_by') }}</p>
                    <p class="small-text">QID No. {{ $cfg('lessor_qid_no') }} NATIONALITY: {{ $cfg('lessor_nationality') }}</p>
                    <p class="small-text">Email: {{ $cfg('lessor_email') }}</p>
                    <p class="small-text">Tel: {{ $cfg('lessor_tel_fax') }}</p>
                </div>
                <div class="cell cell-ar" lang="ar">
                    <div class="section-title">الطرف الأول (البائع)</div>
                    <p class="bold normal-text">{{ $cfg('lessor_name_ar') }}</p>
                    <p class="small-text">ص.ب: <span class="underline">{{ $cfg('lessor_po_box') }}</span>، الدوحة، قطر</p>
                    <p class="small-text">س.ت: <span class="underline">{{ $cfg('lessor_cr_no') }}</span></p>
                    <p class="small-text">يمثلها السيد/ {{ $cfg('lessor_authorized_by') }}</p>
                    <p class="small-text">بطاقة شخصية رقم: {{ $cfg('lessor_qid_no') }} الجنسية: {{ $cfg('lessor_nationality') }}</p>
                    <p class="small-text">البريد الإلكتروني: {{ $cfg('lessor_email') }}</p>
                    <p class="small-text">هاتف: {{ $cfg('lessor_tel_fax') }}</p>
                </div>
            </div>
        </div>

        <!-- Second Party -->
        <div class="section">
            <div class="row">
                <div class="cell cell-en">
                    <div class="section-title">THE SECOND PARTY (PURCHASER)</div>
                    <p>Mr./Mrs. <b>{{ $rentOut->customer?->name }}</b></p>
                    <p>QID: <b>{{ $rentOut->customer?->id_no }}</b></p>
                    <p>NATIONALITY: <b>{{ $rentOut->customer?->nationality }}</b></p>
                    <p>Mobile: <b>{{ $rentOut->customer?->mobile }}</b></p>
                    <p>Email: <b>{{ $rentOut->customer?->email }}</b></p>
                    <p class="small-text">P.O. Box: {{ $rentOut->customer?->po_box }}</p>
                </div>
                <div class="cell cell-ar" lang="ar">
                    <div class="section-title">الطرف الثاني (المشتري)</div>
                    <p>السيد/ة <b>{{ $rentOut->customer?->name }}</b></p>
                    <p>البطاقة الشخصية: <b>{{ $rentOut->customer?->id_no }}</b></p>
                    <p>الجنسية: <b>{{ $rentOut->customer?->nationality }}</b></p>
                    <p>الجوال: <b>{{ $rentOut->customer?->mobile }}</b></p>
                    <p>البريد الإلكتروني: <b>{{ $rentOut->customer?->email }}</b></p>
                    <p class="small-text">ص.ب: {{ $rentOut->customer?->po_box }}</p>
                </div>
            </div>
        </div>

        <!-- Project Details -->
        <div class="section">
            <div class="row">
                <div class="cell cell-en">
                    <div class="section-title">PROJECT DETAILS</div>
                    <p>Project Land: {{ $rentOut->group?->name }}</p>
                    <p>Building: {{ $rentOut->building?->name }}</p>
                    <p>Unit: {{ $rentOut->property?->number }}</p>
                    <p>Floor: {{ $rentOut->property?->floor }}</p>
                    <p>Unit No.: {{ $rentOut->property?->number }}</p>
                    <p>Parking: One Parking ({{ $rentOut->property?->parking }})</p>
                </div>
                <div class="cell cell-ar" lang="ar">
                    <div class="section-title">تفاصيل المشروع</div>
                    <p>أرض المشروع: {{ $rentOut->group?->arabic_name }}</p>
                    <p>المبنى: {{ $rentOut->building?->arabic_name }}</p>
                    <p>الوحدة: {{ $rentOut->property?->number }}</p>
                    <p>الطابق: {{ $rentOut->property?->floor }}</p>
                    <p>رقم الوحدة: {{ $rentOut->property?->number }}</p>
                    <p>موقف السيارات: موقف واحد ({{ $rentOut->property?->parking }})</p>
                </div>
            </div>
        </div>
        <div class="page-break"></div>

        <!-- Preliminary Clause -->
        <br>
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
                        Unit No. :<b>Unit ({{ $rentOut->property?->number }}) – {{ $rentOut->property?->floor }} Floor – {{ $rentOut->type?->name }} Apartment </b>
                        <br>
                        The area of this unit on the map and the architectural drawing of the project (<b>{{ $rentOut->property?->size }} square meters</b>) is subject to
                        deficit and surplus, and the common facilities in the tower will be added, which are the common areas of the apartment as specified in the (Terms and Conditions of Apartment Sale).
                    </p>
                </div>
                <div class="cell cell-ar">
                    <p>
                        رقم الوحدة: <b>الوحدة ({{ $rentOut->property?->number }}) – الطابق {{ $rentOut->property?->floor }} – شقة {{ $rentOut->type?->name }}</b>
                        <br>
                        مساحة هذه الوحدة على الخريطة والرسم المعماري للمشروع (<b>{{ $rentOut->property?->size }} متر مربع</b>) تخضع للنقص والزيادة، وسيتم إضافة المرافق المشتركة في البرج، وهي المناطق المشتركة للشقة كما هو محدد في (شروط وأحكام بيع الشقة).
                    </p>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="cell cell-en">
                    <p><b>Parking: One Parking</b></p>
                </div>
                <div class="cell cell-ar">
                    <p><b>موقف السيارات: موقف واحد</b></p>
                </div>
            </div>
        </div>

        <!-- Payment Schedule -->
        <div class="page-break"></div>
        <br>
        <br>
        <!-- Schedule 1 Header -->
        <div>
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="text-decoration: underline; margin-bottom: 10px;">
                    <span style="font-weight: bold;">SCHEDULE 1 - الجدول 1</span>
                </h2>
                <h3 style="margin-bottom: 15px;">
                    <span style="text-decoration: underline;">Schedule of Instalment Payments / جدول تقسيط الدفعات</span>
                </h3>
            </div>

            <!-- Project Info -->
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 12px;">
                <div>
                    <strong>Project:</strong> {{ $rentOut->building?->name }}<br>
                </div>
                <div style="text-align: center;">
                    <strong>Building:</strong> {{ $rentOut->group?->name }}<br>
                </div>
            </div>

            <div style="margin-bottom: 20px; font-size: 12px;">
                <strong>Unit:</strong> {{ $rentOut->property?->number }} - {{ $rentOut->type?->name }} Apartment<br>
            </div>

            <!-- Payment Schedule Table -->
            <table style="width: 100%; border-collapse: collapse; border: 2px solid #000; margin-bottom: 120px;">
                <thead>
                    <tr style="background-color: #f0f0f0;">
                        <th width="30%" style="border: 1px solid #000; padding: 2px; text-align: center; font-weight: bold;">
                            Instalment<br>
                            <span style="font-size: 10px;">رقم القسط</span>
                        </th>
                        <th width="10%" style="border: 1px solid #000; padding: 2px; text-align: right; font-weight: bold;">
                            Percentage<br>
                            <span style="font-size: 10px;">النسبة (%)</span>
                        </th>
                        <th style="border: 1px solid #000; padding: 2px; text-align: center; font-weight: bold;">
                            Date<br>
                            <span style="font-size: 10px;">التاريخ</span>
                        </th>
                        <th width="30%" style="border: 1px solid #000; padding: 2px; text-align: right; font-weight: bold;">
                            Amount (QAR)<br>
                            <span style="font-size: 10px;">القيمة (ريال قطري)</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $installments = $rentOut->paymentTerms()->orderBy('due_date')->get();
                        $headerHeight = 150;
                        $rowHeight = 28;
                        $pageHeight = 1500;
                        $usablePageHeight = $pageHeight - $headerHeight;
                        $footerHeight = 50;
                        $breakThreshold = $usablePageHeight - $footerHeight;
                        $currentPosition = $headerHeight;
                        $currentPage = 1;
                        $rowsOnCurrentPage = 0;
                    @endphp
                    @foreach ($installments as $item)
                        @php
                            $positionOnPage = $headerHeight + $rowsOnCurrentPage * $rowHeight;
                            $needsBreak = false;
                            if ($rowsOnCurrentPage > 0 && $positionOnPage + $rowHeight > $breakThreshold) {
                                $needsBreak = true;
                                $currentPage++;
                                $rowsOnCurrentPage = 0;
                                $positionOnPage = $headerHeight;
                            }
                            $rowsOnCurrentPage++;
                            $trStyle = $needsBreak ? 'page-break-before: always;' : '';
                        @endphp
                        <tr style="{{ $trStyle }}">
                            <td style="border: 1px solid #000; padding: 2px;">
                                @if ($item->label == 'down payment')
                                    Reservation Down Payment<br>
                                    <span style="font-size: 10px;">الدفعة المقدمة للحجز</span>
                                @elseif($item->label == 'handover payment')
                                    Handover Payment<br>
                                    <span style="font-size: 10px;">دفعة التسليم</span>
                                @else
                                    {{ ordinal($loop->iteration) . ' ' . ucFirst($item->label) }}
                                @endif
                            </td>
                            <td style="border: 1px solid #000; padding: 2px; text-align: right;">{{ getPercentage($item->total ?? $item->amount, $rentOut->rent, 2, true) }}</td>
                            <td style="border: 1px solid #000; padding: 2px; text-align: center;">{{ systemDate($item->due_date) }}</td>
                            <td style="border: 1px solid #000; padding: 2px; text-align: right;">{{ currency($item->total ?? $item->amount) }}</td>
                        </tr>
                    @endforeach
                    @php
                        $total = $installments->sum(fn($t) => $t->total ?? $t->amount);
                        $balance = $rentOut->rent - $total;
                    @endphp
                    <tr>
                        <td style="border: 1px solid #000; padding: 2px; font-weight: bold;">Total</td>
                        <td style="border: 1px solid #000; padding: 2px; text-align: right;">{{ getPercentage($total, $rentOut->rent, 2, true) }}</td>
                        <td style="border: 1px solid #000; padding: 2px; text-align: center;"></td>
                        <td style="border: 1px solid #000; padding: 2px; text-align: right;">{{ currency($total) }}</td>
                    </tr>
                    @if ($balance > 0)
                        <tr>
                            <td style="border: 1px solid #000; padding: 2px;">
                                Monthly Installment 54 Months<br>
                                <span style="font-size: 10px;">تقسيط شهري لمدة 54 شهر</span>
                            </td>
                            <td style="border: 1px solid #000; padding: 2px; text-align: right;">{{ getPercentage($balance, $rentOut->rent, 2, true) }}</td>
                            <td style="border: 1px solid #000; padding: 2px; text-align: center;">
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
                            <td style="border: 1px solid #000; padding: 8px; text-align: center;">
                                {{ currency($balance) }} (Remaining - المبلغ المتبقي) <br>
                                {{ round($balance / 54, 2) }}(Monthly - شهري)
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>

            <table style="width: 100%; page-break-inside: avoid;">
                <tr>
                    <td>
                        <!-- Purchase Price Section -->
                        <table style="width: 100%; border-collapse: collapse; border: 2px solid #000;">
                            <tr>
                                <td style="border: 1px solid #000; padding: 15px; width: 50%; text-align: left; vertical-align: top;">
                                    <strong>Qatari Riyal Only</strong><br>
                                    <span style="font-size: 10px;">ريال قطري فقط</span>
                                </td>
                                <td style="border: 1px solid #000; padding: 15px; width: 25%; text-align: center; vertical-align: top;">
                                    <strong>Purchase Price</strong><br>
                                    <span style="font-size: 10px;">سعر الشراء</span><br>
                                </td>
                                <td style="border: 1px solid #000; padding: 15px; width: 25%; text-align: center; vertical-align: top;">
                                    <strong>STAMP</strong><br>
                                    <span style="font-size: 10px;">ختم</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="border: 1px solid #000; padding: 30px; height: 80px; text-align: center; text-transform: capitalize;">
                                    {{ $numberToWord['english'] }} <br>
                                    {{ $numberToWord['arabic'] }}
                                </td>
                                <td style="border: 1px solid #000; padding: 30px; height: 80px; text-align: center;">{{ currency($rentOut->rent) }}</td>
                                <td style="border: 1px solid #000; padding: 30px; height: 80px;"></td>
                            </tr>
                        </table>

                        <!-- Signature Section -->
                        <table style="width: 100%; border-collapse: collapse; border: 2px solid #000; margin-top: 20px;">
                            <tr>
                                <td style="border: 1px solid #000; padding: 15px; width: 50%; text-align: center;">
                                    <strong>Authorized signatory for and on the behalf of The First Party</strong>
                                </td>
                                <td style="border: 1px solid #000; padding: 15px; width: 50%; text-align: center;">
                                    <strong>Handwritten signature\Second Party</strong>
                                </td>
                            </tr>
                            <tr>
                                <td style="border: 1px solid #000; padding: 40px; height: 60px;"></td>
                                <td style="border: 1px solid #000; padding: 40px; height: 60px;"></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Articles 1-17 --}}
        <div class="page-break"></div>
        <br>
        <br>
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
                    <p>The first party has sold, transferred, and relinquished with all legal and actual warranties to the second party, a specified-bounded unit described in the sales map (off plan sale) for the project. The unit is complete with doors, windows, outlets, and services such as water, lighting, sewage, and gas, with a fully furnished finishing system.<br><br>The area of the property sold is approximately ({{ $rentOut->property?->size }} sqm) square meters, subject to deficiency and excess based on engineering and architectural design.</p>
                </div>
                <div class="cell cell-ar">
                    <div class="section-title">المادة الثانية</div>
                    <p>باع وأسقط وتنازل الطرف الأول بكافة الضمانات القانونية والفعلية للطرف الثاني القابل لذلك ما هو عبارة عن الوحدة محددة الحدود مع الأوصاف بنظام البيع على الخارطة في المشروع. مستكملة الأبواب والنوافذ والمنافذ والخدمات من مياه وإنارة وصرف صحي وغاز بنظام تشطيب كامل مفروش.<br><br>ومساحة العقار الذي يقع للوحد المباعة ({{ $rentOut->property?->size }} متر مربع) متر مربع تقريباً تحت العجز والزيادة.</p>
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
        </div>
        <div class="page-break"></div>
        <br>
        <br>
        <div class="section">
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
        </div>
        <div class="page-break"></div>
        <br>
        <br>
        <div class="section">
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
        </div>
        <div class="page-break"></div>
        <br>
        <br>
        <div class="section">
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
        <br>
        <br>
        @include('print.booking.components.terms_and_conditions')
        <div class="page-break"></div>
        <br>
        <br>
        @include('print.booking.components.schedule_two')
        <div class="page-break"></div>
        <br>
        <br>
        @include('print.booking.components.schedule_three')
    </div>
    <div class="page-footer">
        <div class="signature-row">
            <div>
                <div>____________________</div>
                <div class="signature-label">First Party Signature</div>
            </div>
            <div>
                <div>____________________</div>
                <div class="signature-label">Second Party Signature</div>
            </div>
        </div>
    </div>
</body>

</html>
