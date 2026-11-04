<!-- Schedule 2 Component -->
<div class="schedule-two">
    <div class="reg-sched-title">
        <div class="main">SCHEDULE 2 – <span lang="ar">الجدول 2</span></div>
        <div class="sub">Unit Plans / <span lang="ar">خرائط الشقة</span></div>
    </div>

    <table class="reg">
        <tr>
            <td class="text-center">
                <strong>{{ is_numeric($rentOut->property?->floor) ? ordinal((int) $rentOut->property->floor) : $rentOut->property?->floor }} Floor – {{ $rentOut->building?->name }} – Unit {{ $rentOut->property?->number }} – {{ $rentOut->property?->type?->name }} Apartment</strong>
                <p style="font-style: italic; margin-top: 1mm;">The mentioned areas refer to the internal spaces and do not include the boundaries of the walls.</p>
            </td>
            <td lang="ar" class="text-center">
                <strong>الطابق {{ $rentOut->property?->floor }} – {{ $rentOut->building?->arabic_name }} – شقة رقم {{ $rentOut->property?->number }} – شقة {{ $rentOut->property?->type?->arabic_name }}</strong>
                <p style="margin-top: 1mm; text-align: center;">المساحات المذكورة هي المساحات الداخلية ولا تشمل حدود الحوائط</p>
            </td>
        </tr>
    </table>

    <!-- Floor Plan -->
    <div style="width: 100%; height: 160mm; border: 0.6pt solid #222; display: flex; justify-content: center; align-items: center; padding: 3mm;">
        {{-- Browsershot blocks every external request, so the plan is embedded
             as a data URI rather than linked by URL. --}}
        @php
            $floorPlanPath = $rentOut->property?->floor_plan ? storage_path('app/public/' . $rentOut->property->floor_plan) : null;
            $floorPlanUrl = $floorPlanPath && is_file($floorPlanPath)
                ? 'data:' . mime_content_type($floorPlanPath) . ';base64,' . base64_encode(file_get_contents($floorPlanPath))
                : null;
        @endphp
        @if ($floorPlanUrl)
            <img src="{{ $floorPlanUrl }}" alt="Floor Plan" style="max-width: 100%; max-height: 100%; object-fit: contain;">
        @else
            <p style="text-align: center; color: #666;">Floor Plan Image Will Be Placed Here / <span lang="ar" style="display: inline;">خرائط الشقة</span></p>
        @endif
    </div>
</div>
