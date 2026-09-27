<?php

namespace App\Support\Formatting;

/**
 * Numbers for Persian documents: Persian digits, and an amount written out
 * in words for the "مبلغ به حروف" line of an invoice.
 */
class PersianNumber
{
    private const DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    private const ONES = ['', 'یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه'];

    private const TEENS = ['ده', 'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده'];

    private const TENS = ['', '', 'بیست', 'سی', 'چهل', 'پنجاه', 'شصت', 'هفتاد', 'هشتاد', 'نود'];

    private const HUNDREDS = ['', 'صد', 'دویست', 'سیصد', 'چهارصد', 'پانصد', 'ششصد', 'هفتصد', 'هشتصد', 'نهصد'];

    private const SCALES = ['', 'هزار', 'میلیون', 'میلیارد', 'هزار میلیارد'];

    /**
     * Latin digits replaced by Persian ones; everything else is kept.
     */
    public static function digits(string|int|float $value): string
    {
        return strtr((string) $value, array_combine(range(0, 9), self::DIGITS));
    }

    /**
     * A whole number written out in Persian words, e.g. 890500000 as
     * "هشتصد و نود میلیون و پانصد هزار".
     */
    public static function toWords(int $number): string
    {
        if ($number === 0) {
            return 'صفر';
        }

        if ($number < 0) {
            return 'منفی '.self::toWords(-$number);
        }

        $groups = [];
        $scale = 0;

        while ($number > 0) {
            $group = $number % 1000;

            if ($group > 0) {
                $words = self::belowThousand($group);
                $groups[] = $scale > 0 ? $words.' '.self::SCALES[$scale] : $words;
            }

            $number = intdiv($number, 1000);
            $scale++;
        }

        return implode(' و ', array_reverse($groups));
    }

    /**
     * A money amount stored in minor units (1/100) written out in words with
     * its currency name, dropping the fraction the way printed invoices do.
     */
    public static function amountInWords(int $minorUnits, string $currencyName): string
    {
        $whole = intdiv(abs($minorUnits), 100);

        return trim(self::toWords($whole).' '.$currencyName);
    }

    /**
     * The name printed after an amount for a currency code.
     */
    public static function currencyName(?string $code, ?string $fallback = null): string
    {
        return match (strtoupper((string) $code)) {
            'IRR' => 'ریال',
            'IRT' => 'تومان',
            'AED' => 'درهم',
            'USD' => 'دلار',
            'EUR' => 'یورو',
            default => (string) ($fallback ?? $code),
        };
    }

    private static function belowThousand(int $number): string
    {
        $parts = [];

        if ($number >= 100) {
            $parts[] = self::HUNDREDS[intdiv($number, 100)];
            $number %= 100;
        }

        if ($number >= 20) {
            $parts[] = self::TENS[intdiv($number, 10)];
            $number %= 10;
        } elseif ($number >= 10) {
            $parts[] = self::TEENS[$number - 10];
            $number = 0;
        }

        if ($number > 0) {
            $parts[] = self::ONES[$number];
        }

        return implode(' و ', $parts);
    }
}
