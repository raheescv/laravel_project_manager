<!-- Schedule 2 Component -->
<div class="schedule-two">
    <div class="sched-head">
        <div class="main">SCHEDULE 2 – الجدول 2</div>
        <div class="sub">Unit Plans / خرائط الشقة</div>
    </div>

    <table class="data-table" style="margin-bottom: 6px;">
        <tr>
            <td style="width: 50%; text-align: center;">
                <strong>{{ $rentOut->property->floor }} Floor – {{ $rentOut->property->number }} - {{ $rentOut->property->type->name }}</strong>
            </td>
            <td style="width: 50%; text-align: center;" lang="ar">
                <strong>الطابق - {{ $rentOut->property->floor }} - {{ $rentOut->property->number }} - {{ $rentOut->property->type->arabic_name }}</strong>
            </td>
        </tr>
        <tr>
            <td style="text-align: center; font-style: italic;">The mentioned areas refer to the internal spaces and do not include the boundaries of the walls.</td>
            <td style="text-align: center; font-style: italic;" lang="ar">المساحات المذكورة هي المساحات الداخلية ولا تشمل حدود الحوائط</td>
        </tr>
    </table>

    <!-- Floor Plan -->
    <div style="width: 100%; height: 620px; border: 1px solid #333; display: flex; justify-content: center; align-items: center;">
        @if (isset($rentOut->property->floor_plan) && $rentOut->property->floor_plan)
            <img src="{{ asset('storage/' . $rentOut->property->floor_plan) }}" alt="Floor Plan" style="max-width: 100%; max-height: 100%; object-fit: contain;">
        @else
            <p style="text-align: center; color: #666;">Floor Plan Image Will Be Placed Here / (خرائط الشقة)</p>
        @endif
    </div>
</div>
