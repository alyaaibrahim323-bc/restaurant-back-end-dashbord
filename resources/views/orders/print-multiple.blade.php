<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orders Report - {{ now()->format('Y-m-d') }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            direction: rtl;
        }
        .order {
            page-break-after: always;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>تقرير الطلبات</h1>
        <p>تاريخ التقرير: {{ now()->format('Y-m-d') }}</p>
        <p>عدد الطلبات: {{ count($orders) }}</p>
    </div>

    @foreach($orders as $order)
    <div class="order">
        <h2>طلب #{{ $order->id }}</h2>

        <table>
            <tr>
                <th>العميل</th>
                <th>المبلغ الإجمالي</th>
                <th>الحالة</th>
                <th>تاريخ الطلب</th>
            </tr>
            <tr>
                <td>{{ $order->user->name }}</td>
                <td>{{ $order->total }} جنيه</td>
                <td>
                    @switch($order->status)
                        @case('pending') معلق @break
                        @case('processing') قيد المعالجة @break
                        @case('shipped') تم الشحن @break
                        @case('delivered') تم التسليم @break
                        @case('cancelled') ملغي @break
                    @endswitch
                </td>
                <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
            </tr>
        </table>

        @if($order->address)
        <table>
            <tr>
                <th>عنوان الشحن</th>
            </tr>
            <tr>
                <td>{{ $order->address->full_address }}</td>
            </tr>
        </table>
        @endif

        <h3>عناصر الطلب:</h3>
        <table>
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>الكمية</th>
                    <th>السعر</th>
                    <th>المجموع</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->price }} جنيه</td>
                    <td>{{ $item->quantity * $item->price }} جنيه</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="3" style="text-align: left;"><strong>الإجمالي:</strong></td>
                    <td><strong>{{ $order->total }} جنيه</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    @endforeach
</body>
</html>
