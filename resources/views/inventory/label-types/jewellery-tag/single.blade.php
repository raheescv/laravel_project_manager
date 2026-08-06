<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Barcode Print</title>
    @include('inventory.label-types.jewellery-tag.styles')
    @if (!empty($isPreview))
        <style>
            body {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #ffffff;
            }
        </style>
    @endif
</head>

<body>
    @include('inventory.label-types.jewellery-tag.label')
</body>

</html>
