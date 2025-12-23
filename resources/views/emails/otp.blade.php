<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>كود التحقق</title>
</head>
<body>
    <h2>كود التحقق لتسجيل الدخول</h2>
    <p>استخدم الكود التالي لتسجيل الدخول إلى حسابك:</p>

    <div style="
        background-color: #f0f0f0;
        padding: 15px;
        font-size: 24px;
        letter-spacing: 5px;
        text-align: center;
        margin: 20px 0;
        font-weight: bold;
    ">
        {{ $otp }}
    </div>

    <p>هذا الكود صالح لمدة 10 دقائق فقط.</p>
    <p>إذا لم تطلب هذا الكود، يرجى تجاهل هذه الرسالة.</p>
</body>
</html>
