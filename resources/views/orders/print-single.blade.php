<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
    </style>
</head>
<body>
    <h1>فاتورة</h1>
    <p>رقم الأوردر: {{ $order->id ?? '---' }}</p>
    <p>اسم العميل: {{ $order->user_name ?? '---' }}</p>
</body>
</html>
