<?php

namespace App\Support\Formatting;

use App\Domains\Accounts\Models\CompanySetting;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Morilog\Jalali\Jalalian;

/**
 * Renders a date for a company in the calendar it chose.
 *
 * Dates are always stored and computed in the Gregorian calendar; only what
 * is shown to people changes. A company whose `calendar` setting is `jalali`
 * sees the Solar Hijri (Persian) date, written with the same pattern letters
 * it picked for its date format (Y, m, d, M ...), so `Y/m/d` renders
 * 1405/07/05 and `d M Y` renders 05 مهر 1405. Every other company keeps the
 * Gregorian date in the application's language, exactly as before.
 */
class CompanyDate
{
    public const GREGORIAN = 'gregorian';

    public const JALALI = 'jalali';

    public const CALENDARS = [self::GREGORIAN, self::JALALI];

    public static function calendar(int|string|null $companyId): string
    {
        if ($companyId === null || $companyId === '') {
            return self::GREGORIAN;
        }

        $calendar = CompanySetting::getSetting('calendar', $companyId);

        return $calendar === self::JALALI ? self::JALALI : self::GREGORIAN;
    }

    public static function usesJalali(int|string|null $companyId): bool
    {
        return self::calendar($companyId) === self::JALALI;
    }

    /**
     * The date written with a PHP date pattern in the company's calendar.
     */
    public static function format(CarbonInterface|string|null $date, string $pattern, int|string|null $companyId): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $moment = $date instanceof CarbonInterface ? Carbon::instance($date) : Carbon::parse($date);

        if (self::usesJalali($companyId)) {
            return Jalalian::fromCarbon($moment)->format($pattern);
        }

        return $moment->translatedFormat($pattern);
    }
}
