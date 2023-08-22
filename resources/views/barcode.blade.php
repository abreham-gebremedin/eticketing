<!DOCTYPE html>
    <html>

    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Laravel 8 Barcode Demo - codeanddeploy.com</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    </head>

    <body>
        <div class="container mt-5">
            <div class="container mt-4">
            <div class="mb-3">{!! DNS2D::getBarcodeHTML("$productCode", 'QRCODE') !!}</div>
            <div class="mb-3">{!! DNS2D::getBarcodeHTML("$productCode", 'DATAMATRIX') !!}</div>
            <div class="mb-3">{!! DNS1D::getBarcodeHTML("$productCode", 'C39') !!}</div>
            <div class="mb-3">{!! DNS1D::getBarcodePNG("$productCode", 'C39+') !!}</div>
            <div class="mb-3">{!! DNS1D::getBarcodeHTML("$productCode", 'C39E') !!}</div>
            <div class="mb-3">{!! DNS1D::getBarcodeHTML("$productCode", 'C39E+') !!}</div>
            <div class="mb-3">{!! DNS1D::getBarcodeHTML("$productCode", 'C93') !!}</div>
            <div class="mb-3">{!! DNS1D::getBarcodeHTML("$productCode", 'S25') !!}</div>
            <div class="mb-3">{!! DNS1D::getBarcodeHTML("$productCode", 'S25+') !!}</div>
            <div class="mb-3">{!! DNS1D::getBarcodeHTML("$productCode", 'I25') !!}</div>
            <div class="mb-3">{!! DNS1D::getBarcodeHTML("$productCode", 'I25+') !!}</div>
            <div class="mb-3">{!! DNS1D::getBarcodeHTML("$productCode", 'C128') !!}</div>
            <div class="mb-3">{!! DNS1D::getBarcodeHTML("$productCode", 'C128A') !!}</div>
            <div class="mb-3">{!! DNS1D::getBarcodeHTML("$productCode", 'C128B') !!}</div>
        </div> 
        </div>
    </body>
</html>