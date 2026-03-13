<!-- Schedule 2 Component -->
<div class="schedule-two">
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <td style="width: 50%; border: 1px solid #000; padding: 10px; text-align: center;">
                <h3>SCHEDULE 2</h3>
            </td>
            <td style="width: 50%; border: 1px solid #000; padding: 10px; text-align: center;">
                <h3>الجدول 2</h3>
            </td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; padding: 10px; text-align: center;">
                <h4 style="text-decoration: underline;">Unit Plans</h4>
            </td>
            <td style="border: 1px solid #000; padding: 10px; text-align: center;">
                <h4 style="text-decoration: underline;">خرائط الشقة</h4>
            </td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; padding: 10px; text-align: center;">
                <p><strong>{{ $rentOut->property->floor }} Floor – {{ $rentOut->property->number }} - {{ $rentOut->property->type->name }}</strong></p>
            </td>
            <td style="border: 1px solid #000; padding: 10px; text-align: center;">
                <p><strong>الطابق - {{ $rentOut->property->floor }} - {{ $rentOut->property->number }} - {{ $rentOut->property->type->arabic_name }}</strong></p>
            </td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; padding: 10px; text-align: center;">
                <p>(Unit Plan and Drawings here)</p>
            </td>
            <td style="border: 1px solid #000; padding: 10px; text-align: center;">
                <p>(خرائط الشقة)</p>
            </td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; padding: 10px; text-align: center;">
                <p style="font-style: italic;">The mentioned areas refer to the internal spaces and do not include the boundaries of the walls.</p>
            </td>
            <td style="border: 1px solid #000; padding: 10px; text-align: center;">
                <p style="font-style: italic;">المساحات المذكورة هي المساحات الداخلية ولا تشمل حدود الحوائط</p>
            </td>
        </tr>
    </table>

    <!-- Space for Floor Plan Image -->
    <div style="width: 100%; height: 600px; border: 1px solid #000; margin-bottom: 20px; display: flex; justify-content: center; align-items: center;">
        @if (isset($rentOut->property->floor_plan) && $rentOut->property->floor_plan)
            <img src="{{ asset('storage/' . $rentOut->property->floor_plan) }}" alt="Floor Plan" style="max-width: 100%; max-height: 100%; object-fit: contain;">
        @else
            <p style="text-align: center; color: #666;">Floor Plan Image Will Be Placed Here</p>
        @endif
    </div>
</div>
