<?php

use App\Support\Formatting\PersianNumber;

test('amounts are written out in persian words', function (int $number, string $words) {
    expect(PersianNumber::toWords($number))->toBe($words);
})->with([
    [0, 'صفر'],
    [7, 'هفت'],
    [15, 'پانزده'],
    [21, 'بیست و یک'],
    [105, 'صد و پنج'],
    [1000, 'یک هزار'],
    [21500000, 'بیست و یک میلیون و پانصد هزار'],
    [890500000, 'هشتصد و نود میلیون و پانصد هزار'],
    [2000000001, 'دو میلیارد و یک'],
]);

test('money in minor units is written with its currency', function () {
    expect(PersianNumber::amountInWords(89050000000, PersianNumber::currencyName('IRR')))
        ->toBe('هشتصد و نود میلیون و پانصد هزار ریال');
});

test('latin digits become persian digits', function () {
    expect(PersianNumber::digits('1405/06/13 - 890,500,000'))->toBe('۱۴۰۵/۰۶/۱۳ - ۸۹۰,۵۰۰,۰۰۰');
});
