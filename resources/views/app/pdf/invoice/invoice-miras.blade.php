@php
    use App\Support\Formatting\PersianNumber;

    // A Persian, right-to-left sales invoice ("صورتحساب فروش کالا و خدمات")
    // laid out like the paper invoice the business already uses. Written for
    // the Gotenberg (Chromium) driver: dompdf cannot join Persian letters.
    $currency = $invoice->customer->currency;
    $precision = (int) ($currency->precision ?? 0);
    $money = fn ($minor) => PersianNumber::digits(number_format(abs((int) $minor) / 100, $precision, '.', ','));
    $fa = fn ($value) => PersianNumber::digits((string) $value);
    $currencyName = PersianNumber::currencyName($currency->code ?? null, $currency->name ?? null);

    $lineDiscounts = (int) $invoice->items->sum('discount_val');
    $totalQuantity = $invoice->items->sum('quantity');
    $customer = $invoice->customer;
    $billing = $customer?->billingAddress;
    $phone = $billing?->phone ?: $customer?->phone;
    $addressLine = collect([$billing?->address_street_1, $billing?->address_street_2, $billing?->city, $billing?->state])
        ->filter()->implode('، ');
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>صورتحساب فروش - {{ $invoice->invoice_number }}</title>
    @include('app.pdf.partials.fonts')
    <style type="text/css">
        @page { size: A4; margin: 12mm 10mm; }
        * { box-sizing: border-box; }
        body { direction: rtl; text-align: right; font-size: 11px; color: #111; margin: 0; }
        table { border-collapse: collapse; width: 100%; }
        .box { border: 1px solid #333; border-radius: 6px; }
        .header td { vertical-align: top; padding: 8px 10px; }
        .title { text-align: center; font-size: 15px; font-weight: bold; line-height: 1.9; }
        .meta { width: 30%; font-size: 11px; line-height: 1.9; }
        .meta b { display: inline-block; min-width: 72px; }
        .logo { max-height: 48px; }
        .buyer { margin-top: 6px; }
        .buyer .side { width: 26px; background: #555; color: #fff; text-align: center; font-weight: bold; border-radius: 0 6px 6px 0; }
        .buyer .side span { writing-mode: vertical-rl; transform: rotate(180deg); display: inline-block; }
        .buyer td.body { padding: 8px 12px; line-height: 2; }
        .buyer .name { background: #eee; border-radius: 10px; padding: 1px 12px; display: inline-block; min-width: 55%; font-weight: bold; }
        .items { margin-top: 6px; }
        .items th { background: #eee; border: 1px solid #333; padding: 5px 3px; font-size: 10px; text-align: center; }
        .items td { border: 1px solid #333; padding: 6px 4px; text-align: center; }
        .items td.desc { text-align: right; padding-right: 8px; }
        .items .sum td { background: #f4f4f4; font-weight: bold; }
        .totals td { border: 1px solid #333; padding: 6px 8px; }
        .totals .label { font-weight: bold; width: 18%; }
        .totals .value { text-align: left; width: 16%; direction: ltr; }
        .totals .net td { background: #eee; font-weight: bold; }
        .num { direction: ltr; unicode-bidi: isolate; }
        span.num { display: inline-block; }
        .notes { vertical-align: top; }
        .sign td { height: 60px; vertical-align: bottom; text-align: center; font-weight: bold; border: none; }
        .words { margin-top: 0; }
    </style>
</head>
<body>
    <table class="box header">
        <tr>
            <td class="meta">
                <div><b>شماره سریال :</b> <span class="num">{{ $invoice->invoice_number }}</span></div>
                <div><b>تاریخ :</b> <span class="num">{{ $fa($invoice->formattedInvoiceDate) }}</span></div>
                @unless ($invoice->isCreditNote())
                    <div><b>سررسید :</b> <span class="num">{{ $fa($invoice->formattedDueDate) }}</span></div>
                @endunless
            </td>
            <td class="title">
                {{ $invoice->isCreditNote() ? 'برگشت از فروش' : 'صورتحساب فروش کالا و خدمات' }}<br>
                {{ $invoice->company->name }}
            </td>
            <td style="width: 30%; text-align: left;">
                @if ($logo)
                    <img class="logo" src="{{ \App\Platform\Pdf\Rendering\ImageUtils::toBase64Src($logo) }}" alt="">
                @endif
            </td>
        </tr>
    </table>

    <table class="box buyer">
        <tr>
            <td class="side"><span>خریدار</span></td>
            <td class="body">
                <div>مشخصات خریدار : <span class="name">{{ $customer?->name }}</span></div>
                <div>تلفن : <bdo dir="ltr">{{ $fa($phone ?? '') }}</bdo></div>
                <div>آدرس : {{ $addressLine }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 4%">ردیف</th>
                <th style="width: 8%">کد کالا</th>
                <th>شرح کالا / خدمات</th>
                <th style="width: 7%">تعداد / مقدار</th>
                <th style="width: 7%">واحد کالا</th>
                <th style="width: 12%">مبلغ واحد</th>
                @if ($invoice->discount_per_item === 'YES')
                    <th style="width: 6%">درصد تخفیف</th>
                    <th style="width: 10%">مبلغ تخفیف</th>
                @endif
                <th style="width: 14%">مبلغ کل</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $index => $item)
                <tr>
                    <td>{{ $fa($index + 1) }}</td>
                    <td class="num">{{ $fa($item->item?->sku ?? '') }}</td>
                    <td class="desc">
                        {{ $item->name }}
                        @if ($item->description)
                            <br><span style="color:#555; font-size: 9px;">{{ $item->description }}</span>
                        @endif
                    </td>
                    <td class="num">{{ $fa(rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.')) }}</td>
                    <td>{{ $item->unit_name }}</td>
                    <td class="num">{{ $money($item->price) }}</td>
                    @if ($invoice->discount_per_item === 'YES')
                        <td class="num">{{ $item->discount_type === 'percentage' ? $fa($item->discount) : $fa(0) }}</td>
                        <td class="num">{{ $money($item->discount_val) }}</td>
                    @endif
                    <td class="num">{{ $money($item->total) }}</td>
                </tr>
            @endforeach
            <tr class="sum">
                <td colspan="3" style="text-align: right;">جمع مقداری :</td>
                <td class="num">{{ $fa(rtrim(rtrim(number_format($totalQuantity, 2, '.', ''), '0'), '.')) }}</td>
                <td colspan="{{ $invoice->discount_per_item === 'YES' ? 3 : 1 }}"></td>
                <td>جمع کل :</td>
                <td class="num">{{ $money($invoice->sub_total) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="notes" rowspan="4">
                توضیحات فاکتور :
                @if ($notes)
                    <div style="margin-top: 4px;">{!! $notes !!}</div>
                @endif
                <table class="sign"><tr><td>مهر و امضاء فروشنده</td><td>مهر و امضاء خریدار</td></tr></table>
            </td>
            <td class="label">تخفیف کالایی</td>
            <td class="value">{{ $money($lineDiscounts) }}</td>
        </tr>
        <tr>
            <td class="label">مالیات ارزش افزوده</td>
            <td class="value">{{ $money($invoice->tax) }}</td>
        </tr>
        <tr>
            <td class="label">تخفیف نقدی</td>
            <td class="value">{{ $money($invoice->discount_per_item === 'YES' ? 0 : $invoice->discount_val) }}</td>
        </tr>
        <tr>
            <td class="label">پرداخت‌شده</td>
            <td class="value">{{ $money((int) $invoice->total - (int) $invoice->due_amount) }}</td>
        </tr>
        <tr class="net">
            <td class="words"><b>مبلغ به حروف :</b> {{ PersianNumber::amountInWords((int) $invoice->total, $currencyName) }}</td>
            <td class="label">خالص قابل پرداخت ({{ $currencyName }})</td>
            <td class="value">{{ $money($invoice->total) }}</td>
        </tr>
    </table>
</body>
</html>
