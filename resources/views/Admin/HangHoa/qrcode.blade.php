<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>QRCDOE PRINT</title>
    <style type="text/css" media="screen">
        .container {
            max-width: 270px;

        }
        .qrcode-item {
            max-width: 100px;
            float: left;
            border: 1px solid #ccc;
            padding: 10px;
            margin-right: 10px;
            margin-top: 10px;
        }
        .qrcode-title {
            text-align: center;
            font-size: 11px;
            padding-top:2px;
        }
    </style>
</head>
<body>
<div class="container">
    @for($i=1;$i<=$so_luong; $i++)
    @php
        //$data = env('QRCODE_URL') . 'hang-hoa/'.$hh['ma'];
        $data = $hh['ma_vach'];
    @endphp
    <div class="qrcode-item">
        <div class="qrcode-image">{!! QrCode::size(100)->generate($data); !!}</div>
        <div class="qrcode-title">{{ $hh['ma_vach'] }}</div>
    </div>
    @endfor
</div>
</body>
</html>
