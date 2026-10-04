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
        'array' => 'خانەی :attribute دەبێت لە نێوان :min و :max بڕگە هەبێت.',
        'file' => 'خانەی :attribute دەبێت لە نێوان :min و :max کیلۆبایت بێت.',
        'numeric' => 'خانەی :attribute دەبێت لە نێوان :min و :max بێت.',
        'string' => 'خانەی :attribute دەبێت لە نێوان :min و :max پیت بێت.',
    ],
    'max' => [
        'array' => 'خانەی :attribute نابێت زیاتر لە :max بڕگەی هەبێت.',
        'file' => 'خانەی :attribute نابێت گەورەتر لە :max کیلۆبایت بێت.',
        'numeric' => 'خانەی :attribute نابێت زیاتر لە :max بێت.',
        'string' => 'خانەی :attribute نابێت زیاتر لە :max پیت بێت.',
    ],
    'min' => [
        'array' => 'خانەی :attribute دەبێت لانیکەم :min بڕگەی هەبێت.',
        'file' => 'خانەی :attribute دەبێت لانیکەم :min کیلۆبایت بێت.',
        'numeric' => 'خانەی :attribute دەبێت لانیکەم :min بێت.',
        'string' => 'خانەی :attribute دەبێت لانیکەم :min پیت بێت.',
    ],
    'size' => [
        'array' => 'خانەی :attribute دەبێت :size بڕگەی تێدابێت.',
        'file' => 'خانەی :attribute دەبێت :size کیلۆبایت بێت.',
        'numeric' => 'خانەی :attribute دەبێت :size بێت.',
        'string' => 'خانەی :attribute دەبێت :size پیت بێت.',
    ],

    'accepted' => 'خانەی :attribute دەبێت قبوڵ بکرێت.',
    'active_url' => 'خانەی :attribute دەبێت بەستەرێکی دروست بێت.',
    'after' => 'خانەی :attribute دەبێت بەرواری دوای :date بێت.',
    'alpha' => 'خانەی :attribute دەبێت تەنها پیت لەخۆبگرێت.',
    'alpha_dash' => 'خانەی :attribute دەبێت تەنها پیت، ژمارە، داش و ژێرهێڵ لەخۆبگرێت.',
    'alpha_num' => 'خانەی :attribute دەبێت تەنها پیت و ژمارە لەخۆبگرێت.',
    'array' => 'خانەی :attribute دەبێت ڕیزبەند بێت.',
    'before' => 'خانەی :attribute دەبێت بەرواری پێش :date بێت.',
    'boolean' => 'خانەی :attribute دەبێت ڕاست یان هەڵە بێت.',
    'confirmed' => 'دووبارەکردنەوەی خانەی :attribute یەک ناگرێتەوە.',
    'date' => 'خانەی :attribute دەبێت بەروارێکی دروست بێت.',
    'different' => 'خانەی :attribute و :other دەبێت جیاواز بن.',
    'digits' => 'خانەی :attribute دەبێت :digits ژمارە بێت.',
    'email' => 'خانەی :attribute دەبێت ئیمەیلێکی دروست بێت.',
    'exists' => 'ئەو :attribute ـەی هەڵبژێردراوە نادروستە.',
    'file' => 'خانەی :attribute دەبێت فایل بێت.',
    'filled' => 'خانەی :attribute دەبێت بەتاڵ نەبێت.',
    'image' => 'خانەی :attribute دەبێت وێنە بێت.',
    'in' => 'ئەو :attribute ـەی هەڵبژێردراوە نادروستە.',
    'integer' => 'خانەی :attribute دەبێت ژمارەی تەواو بێت.',
    'mimes' => 'خانەی :attribute دەبێت فایلێک بێت لەم جۆرانە: :values.',
    'numeric' => 'خانەی :attribute دەبێت ژمارە بێت.',
    'present' => 'خانەی :attribute دەبێت ئامادە بێت.',
    'prohibited' => 'خانەی :attribute قەدەغەیە.',
    'regex' => 'شێوازی خانەی :attribute نادروستە.',
    'required' => 'خانەی :attribute پێویستە.',
    'same' => 'خانەی :attribute دەبێت لەگەڵ :other یەک بگرێتەوە.',
    'string' => 'خانەی :attribute دەبێت دەق بێت.',
    'unique' => 'ئەم :attribute ـە پێشتر بەکارهاتووە.',
    'uploaded' => 'بارکردنی :attribute سەرکەوتوو نەبوو. لەوانەیە فایلەکە گەورەتر بێت لەوەی سێرڤەر قبوڵی دەکات.',
    'url' => 'خانەی :attribute دەبێت بەستەرێکی دروست بێت.',

    /* Per-field overrides and friendly field names. */
    'custom' => [],
    'attributes' => [],
];
