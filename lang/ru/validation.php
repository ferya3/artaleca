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
        'array' => 'Количество элементов в поле «:attribute» должно быть от :min до :max.',
        'file' => 'Размер файла в поле «:attribute» должен быть от :min до :max килобайт.',
        'numeric' => 'Значение поля «:attribute» должно быть от :min до :max.',
        'string' => 'Длина поля «:attribute» должна быть от :min до :max символов.',
    ],
    'max' => [
        'array' => 'Количество элементов в поле «:attribute» не должно превышать :max.',
        'file' => 'Размер файла в поле «:attribute» не должен превышать :max килобайт.',
        'numeric' => 'Значение поля «:attribute» не должно превышать :max.',
        'string' => 'Длина поля «:attribute» не должна превышать :max символов.',
    ],
    'min' => [
        'array' => 'Количество элементов в поле «:attribute» должно быть не менее :min.',
        'file' => 'Размер файла в поле «:attribute» должен быть не менее :min килобайт.',
        'numeric' => 'Значение поля «:attribute» должно быть не менее :min.',
        'string' => 'Длина поля «:attribute» должна быть не менее :min символов.',
    ],
    'size' => [
        'array' => 'Поле «:attribute» должно содержать :size элементов.',
        'file' => 'Размер файла в поле «:attribute» должен быть :size килобайт.',
        'numeric' => 'Значение поля «:attribute» должно быть :size.',
        'string' => 'Длина поля «:attribute» должна быть :size символов.',
    ],

    'accepted' => 'Поле «:attribute» должно быть принято.',
    'active_url' => 'Поле «:attribute» должно содержать корректный URL.',
    'after' => 'Поле «:attribute» должно содержать дату после :date.',
    'alpha' => 'Поле «:attribute» должно содержать только буквы.',
    'alpha_dash' => 'Поле «:attribute» должно содержать только буквы, цифры, дефисы и подчёркивания.',
    'alpha_num' => 'Поле «:attribute» должно содержать только буквы и цифры.',
    'array' => 'Поле «:attribute» должно быть массивом.',
    'before' => 'Поле «:attribute» должно содержать дату до :date.',
    'boolean' => 'Поле «:attribute» должно иметь значение «да» или «нет».',
    'confirmed' => 'Подтверждение поля «:attribute» не совпадает.',
    'date' => 'Поле «:attribute» должно содержать корректную дату.',
    'different' => 'Поля «:attribute» и «:other» должны различаться.',
    'digits' => 'Поле «:attribute» должно содержать :digits цифр.',
    'email' => 'Поле «:attribute» должно содержать корректный адрес электронной почты.',
    'exists' => 'Выбранное значение поля «:attribute» некорректно.',
    'file' => 'Поле «:attribute» должно содержать файл.',
    'filled' => 'Поле «:attribute» должно быть заполнено.',
    'image' => 'Поле «:attribute» должно содержать изображение.',
    'in' => 'Выбранное значение поля «:attribute» некорректно.',
    'integer' => 'Поле «:attribute» должно содержать целое число.',
    'mimes' => 'Поле «:attribute» должно содержать файл одного из типов: :values.',
    'numeric' => 'Поле «:attribute» должно содержать число.',
    'present' => 'Поле «:attribute» должно присутствовать.',
    'prohibited' => 'Поле «:attribute» запрещено.',
    'regex' => 'Формат поля «:attribute» некорректен.',
    'required' => 'Поле «:attribute» обязательно для заполнения.',
    'same' => 'Поле «:attribute» должно совпадать с «:other».',
    'string' => 'Поле «:attribute» должно быть строкой.',
    'unique' => 'Такое значение поля «:attribute» уже занято.',
    'uploaded' => 'Не удалось загрузить «:attribute». Возможно, файл больше, чем принимает сервер.',
    'url' => 'Поле «:attribute» должно содержать корректный URL.',

    /* Per-field overrides and friendly field names. */
    'custom' => [],
    'attributes' => [],
];
