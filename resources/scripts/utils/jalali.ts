/**
 * Writing a date in the Solar Hijri (Persian) calendar with the same PHP
 * pattern letters the company picked for its date format (Y/m/d, d M Y ...),
 * so the browser shows exactly what the server renders for a company whose
 * calendar setting is `jalali`. Dates stay Gregorian everywhere else; this
 * only changes how one is shown. Uses the browser's own Intl calendar data,
 * so no library is needed.
 */

export const JALALI = 'jalali'

const numericParts = new Intl.DateTimeFormat('en-US-u-ca-persian-nu-latn', {
  year: 'numeric',
  month: 'numeric',
  day: 'numeric',
})

const monthNames = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { month: 'long' })

const weekdayNames = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { weekday: 'long' })

function pad(value: number): string {
  return String(value).padStart(2, '0')
}

export interface JalaliDate {
  year: number
  month: number
  day: number
}

export function toJalali(date: Date): JalaliDate {
  const parts = numericParts.formatToParts(date)
  const part = (type: string): number =>
    parseInt(parts.find((p) => p.type === type)?.value ?? '0', 10)

  return { year: part('year'), month: part('month'), day: part('day') }
}

/**
 * Render a date with a PHP-style pattern (the company's carbon_date_format,
 * optionally followed by a time format) in the Persian calendar.
 */
export function formatJalali(date: Date, pattern: string): string {
  if (Number.isNaN(date.getTime())) {
    return ''
  }

  const { year, month, day } = toJalali(date)
  const hours = date.getHours()

  const tokens: Record<string, () => string> = {
    Y: () => String(year),
    y: () => pad(year % 100),
    m: () => pad(month),
    n: () => String(month),
    d: () => pad(day),
    j: () => String(day),
    M: () => monthNames.format(date),
    F: () => monthNames.format(date),
    D: () => weekdayNames.format(date),
    l: () => weekdayNames.format(date),
    H: () => pad(hours),
    G: () => String(hours),
    h: () => pad(hours % 12 || 12),
    g: () => String(hours % 12 || 12),
    i: () => pad(date.getMinutes()),
    s: () => pad(date.getSeconds()),
    A: () => (hours < 12 ? 'AM' : 'PM'),
    a: () => (hours < 12 ? 'am' : 'pm'),
    K: () => (hours < 12 ? 'AM' : 'PM'),
  }

  let out = ''
  for (let i = 0; i < pattern.length; i++) {
    const char = pattern[i]

    if (char === '\\' && i + 1 < pattern.length) {
      out += pattern[++i]
      continue
    }

    out += tokens[char] ? tokens[char]() : char
  }

  return out
}
