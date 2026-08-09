<?php

/*
 * Validation messages.
 *
 * Without this file Laravel has nothing to resolve the keys against and
 * renders them raw — a visitor was being shown `validation.uploaded` and
 * `validation.max.string` instead of a sentence.
 */

return [
    'between' => [
        'array' => ':attribute باید بین :min و :max مورد باشد.',
        'file' => 'حجم :attribute باید بین :min و :max کیلوبایت باشد.',
        'numeric' => ':attribute باید بین :min و :max باشد.',
        'string' => ':attribute باید بین :min و :max کاراکتر باشد.',
    ],
    'max' => [
        'array' => ':attribute نباید بیشتر از :max مورد باشد.',
        'file' => 'حجم :attribute نباید بیشتر از :max کیلوبایت باشد.',
        'numeric' => ':attribute نباید بزرگ‌تر از :max باشد.',
        'string' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
    ],
    'min' => [
        'array' => ':attribute نباید کمتر از :min مورد باشد.',
        'file' => 'حجم :attribute نباید کمتر از :min کیلوبایت باشد.',
        'numeric' => ':attribute نباید کوچک‌تر از :min باشد.',
        'string' => ':attribute نباید کمتر از :min کاراکتر باشد.',
    ],
    'size' => [
        'array' => ':attribute باید شامل :size مورد باشد.',
        'file' => 'حجم :attribute باید :size کیلوبایت باشد.',
        'numeric' => ':attribute باید برابر :size باشد.',
        'string' => ':attribute باید :size کاراکتر باشد.',
    ],

    'accepted' => 'پذیرفتن :attribute الزامی است.',
    'active_url' => ':attribute یک نشانی معتبر نیست.',
    'after' => ':attribute باید تاریخی بعد از :date باشد.',
    'alpha' => ':attribute باید فقط شامل حروف باشد.',
    'alpha_dash' => ':attribute باید فقط شامل حروف، عدد، خط تیره و زیرخط باشد.',
    'alpha_num' => ':attribute باید فقط شامل حروف و عدد باشد.',
    'array' => ':attribute باید یک آرایه باشد.',
    'before' => ':attribute باید تاریخی قبل از :date باشد.',
    'boolean' => ':attribute باید درست یا نادرست باشد.',
    'confirmed' => 'تکرار :attribute مطابقت ندارد.',
    'date' => ':attribute یک تاریخ معتبر نیست.',
    'different' => ':attribute و :other باید متفاوت باشند.',
    'digits' => ':attribute باید :digits رقم باشد.',
    'email' => ':attribute باید یک نشانی ایمیل معتبر باشد.',
    'exists' => ':attribute انتخاب‌شده معتبر نیست.',
    'file' => ':attribute باید یک فایل باشد.',
    'filled' => 'وارد کردن :attribute الزامی است.',
    'image' => ':attribute باید یک تصویر باشد.',
    'in' => ':attribute انتخاب‌شده معتبر نیست.',
    'integer' => ':attribute باید یک عدد صحیح باشد.',
    'mimes' => ':attribute باید فایلی از نوع :values باشد.',
    'numeric' => ':attribute باید یک عدد باشد.',
    'present' => ':attribute باید موجود باشد.',
    'prohibited' => 'وارد کردن :attribute مجاز نیست.',
    'regex' => 'قالب :attribute معتبر نیست.',
    'required' => 'وارد کردن :attribute الزامی است.',
    'same' => ':attribute باید با :other یکسان باشد.',
    'string' => ':attribute باید یک رشته متنی باشد.',
    'unique' => ':attribute قبلاً ثبت شده است.',
    'uploaded' => 'آپلود :attribute ناموفق بود. حجم فایل ممکن است از حد مجاز سرور بیشتر باشد.',
    'url' => ':attribute باید یک نشانی معتبر باشد.',

    /* Per-field overrides and friendly field names. */
    'custom' => [],
    'attributes' => [],
];
