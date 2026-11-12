<!-- Disclosure Statement Component: framed as one group, printed on its own page -->
<table class="reg group">
    <tr class="group-head">
        <td colspan="2"><div><span>Disclosure Statement</span><span lang="ar">بيان الافصاح عن معلومات</span></div></td>
    </tr>

    <!-- Building Description -->
    <tr>
        <td>
            <strong>Description of Building or Project:</strong><br>
            {{ $rentOut->group?->name }} - Lusail
        </td>
        <td lang="ar">
            <strong>وصف المبنى أو المشروع:</strong><br>
            {{ $rentOut->group?->arabic_name }} - لوسيل
        </td>
    </tr>

    <!-- Intended Use -->
    <tr>
        <td>
            <strong>Intended land uses within the Building or Project:</strong><br>
            Residential and Commercial Towers
        </td>
        <td lang="ar">
            <strong>الاستخدام المفروض للمبنى والمشروع:</strong><br>
            ابراج سكنية وتجارية
        </td>
    </tr>

    <!-- Furniture and Fixtures -->
    <tr>
        <td>
            <strong>Furniture, Fixtures and Fittings included in the sale:</strong><br>
            Cupboards, kitchen cabinets, oven, stove, refrigerator/freezer, washing machine, living room set, master bedroom, dining room set, tv, secondary bedroom.
        </td>
        <td lang="ar">
            <strong>الأثاث والتركيبات والتجهيزات المشمولة بالبيع:</strong><br>
            خزائن، خزائن مطبخ، فرن، موقد، ثلاجة / فريزر، غسالة، غرفة نوم رئيسية، غرفة نوم ثانوية، غرفة جلوس، سفرة، تلفاز.
        </td>
    </tr>

    <!-- First Party Details -->
    <tr>
        <td>
            <strong>First Party's Name:</strong> {{ \App\Support\RentOutPrintSettings::lessor('lessor_name_en') }}<br>
            <strong>First Party's Representative:</strong><br>
            <strong>Signature &amp; Stamp:</strong>
            <div class="sign-space"></div>
        </td>
        <td lang="ar">
            <strong>اسم الطرف الاول : {{ \App\Support\RentOutPrintSettings::lessor('lessor_name_ar') }}</strong><br>
            <strong>ممثل الطرف الاول :</strong><br>
            <strong>توقيع وخاتم الطرف الاول :</strong>
            <div class="sign-space"></div>
        </td>
    </tr>

    <!-- Second Party Details -->
    <tr>
        <td>
            <strong>Second Party's Name: Mr. {{ $rentOut->customer?->name }}</strong><br>
            <strong>Signature: \ handwritten signature \</strong>
            <div class="sign-space"></div>
        </td>
        <td lang="ar">
            <strong>اسم الطرف الثاني: السيد / {{ $rentOut->customer?->name }}</strong><br>
            <strong>التوقيع: / توقيع بخط اليد /</strong>
            <div class="sign-space"></div>
        </td>
    </tr>
</table>
