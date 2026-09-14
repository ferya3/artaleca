<?php

return [
    'name' => 'نام و نام خانوادگی',
    'company' => 'نام شرکت',
    'email' => 'پست الکترونیک',
    'phone' => 'شماره تماس',
    'phone_quote_hint' => 'حتماً یک شماره فعال وارد کنید؛ واحد فروش برای اعلام قیمت با همین شماره تماس می‌گیرد.',
    'country' => 'کشور',
    'subject' => 'موضوع',
    'message' => 'پیام',
    'product' => 'محصول مورد نظر',
    'quantity' => 'حجم مورد نیاز',
    'quantity_placeholder' => 'مثلاً ۵۰۰ متر مکعب',
    'delivery_terms' => 'شرایط تحویل',
    'delivery_terms_hint' => 'اگر مطمئن نیستید خالی بگذارید؛ واحد فروش در تماس مشخص می‌کند.',

    /*
     * The shipped list, shown until an editor writes their own in
     * Panel → Settings → Delivery terms. The code is what gets stored on the
     * enquiry; this sentence is only ever read by the buyer, who has no reason
     * to know what "FOB" means.
     */
    'delivery_terms_options' => [
        'EXW' => 'تحویل درب کارخانه',
        'FOB' => 'تحویل روی کشتی، بندر مبدأ',
        'CFR' => 'حمل تا بندر مقصد، بدون بیمه',
        'CIF' => 'حمل و بیمه تا بندر مقصد',
        'DAP' => 'تحویل در محل شما',
    ],
    'consent' => 'موافقت با سیاست حریم خصوصی',
    'consent_label' => 'با ذخیره و پردازش اطلاعات واردشده برای پاسخ به این درخواست موافقم.',
    'consent_required' => 'برای ارسال فرم، موافقت با سیاست حریم خصوصی الزامی است.',

    'contact_title' => 'ارسال پیام',
    'contact_intro' => 'برای پرسش‌های فنی، درخواست بازدید از کارخانه یا همکاری تجاری، فرم زیر را تکمیل کنید.',
    'contact_success' => 'پیام شما ثبت شد. کارشناسان ما در اولین فرصت کاری پاسخ می‌دهند.',

    'quote_title' => 'درخواست استعلام قیمت',
    'quote_intro' => 'قیمت لیکا به گرید، حجم سفارش و مقصد تحویل بستگی دارد و نرخ ثابتی ندارد؛ هرچه مشخصات فنی و حجم دقیق‌تر باشد، پیشنهاد فروش سریع‌تر و دقیق‌تر آماده می‌شود.',
    'quote_success' => 'درخواست استعلام شما ثبت شد. واحد فروش ظرف یک روز کاری با شما تماس می‌گیرد.',

    'spam_detected' => 'ارسال فرم تأیید نشد.',
    'expired' => 'اعتبار فرم به پایان رسیده است. لطفاً صفحه را تازه‌سازی کنید و دوباره تلاش کنید.',
    'has_errors' => 'لطفاً خطاهای زیر را برطرف کنید:',
    'select_placeholder' => '— انتخاب کنید —',
    'territory' => 'منطقه‌ی درخواستی',
    'territory_placeholder' => 'مثلاً استان اصفهان — شهرستان کاشان',
    'activity' => 'زمینه‌ی فعالیت فعلی',
    'activity_placeholder' => 'مثلاً فروش مصالح ساختمانی',
    'experience_years' => 'سابقه‌ی فعالیت (سال)',
    'warehouse_m2' => 'فضای انبار (متر مربع)',
    'monthly_volume' => 'برداشت ماهانه‌ی تخمینی',
    'monthly_volume_placeholder' => 'مثلاً ۳۰۰ متر مکعب',
];
