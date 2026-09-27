# نصب نرم‌افزار فروش میراث روی سرور

این پوشه همه‌چیز را با یک دستور بالا می‌آورد:

- **app**: همین نسخهٔ InvoiceShelf با تغییرات میراث
- **database**: پایگاه دادهٔ MariaDB
- **pdf**: موتور Gotenberg، که حروف فارسی را در PDF درست به هم می‌چسباند
- **caddy**: گواهی HTTPS را خودکار می‌گیرد

## پیش‌نیازها

- یک سرور لینوکس (Ubuntu 22.04 یا 24.04) با حداقل ۲ گیگ رم و ۲۰ گیگ دیسک
- یک دامنه یا زیردامنه (مثلاً `sales.example.com`) که رکورد A آن به IP سرور اشاره کند
- Docker و افزونهٔ Docker Compose

> **سرور در ایران:** دسترسی به Docker Hub و npm از ایران ممکن است بسته باشد. در این صورت یک mirror داخلی
> (مثلاً mirror آروان) را در `/etc/docker/daemon.json` زیر `registry-mirrors` بگذارید و Docker را ری‌استارت کنید.

## نصب

```bash
git clone https://github.com/alineku/InvoiceShelf.git
cd InvoiceShelf/deploy/miras
cp .env.example .env
nano .env                      # DOMAIN، APP_KEY و دو رمز پایگاه داده را پر کنید
docker compose up -d --build
```

برای ساختن APP_KEY این دستور را یک بار اجرا کنید و خروجی را در `.env` بگذارید:

```bash
docker run --rm php:8.4-cli php -r 'echo "base64:".base64_encode(random_bytes(32)), PHP_EOL;'
```

ساخت بار اول چند دقیقه طول می‌کشد. بعد `https://DOMAIN` را باز کنید و مراحل نصب را جلو بروید.
اطلاعات پایگاه داده در این مراحل از قبل پر شده است.

## تنظیمات بعد از نصب

1. **تنظیمات ← ترجیحات**: زبان را «فارسی» و تقویم را «شمسی» کنید.
2. در صفحهٔ ساخت فاکتور، دکمهٔ انتخاب قالب را بزنید، `invoice-miras` را انتخاب کنید و تیک «پیش‌فرض» را بزنید.
3. واحد پول (ریال یا درهم) را در همان ترجیحات انتخاب کنید.

## به‌روزرسانی

```bash
cd InvoiceShelf && git pull
cd deploy/miras && docker compose up -d --build
```

## پشتیبان‌گیری

همهٔ داده‌ها در volumeهای Docker هستند (`mysql`، `storage`، `modules`). یک پشتیبان سادهٔ پایگاه داده:

```bash
docker compose exec database sh -c 'mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" invoiceshelf' > backup-$(date +%F).sql
```

فایل `.env` را هم جای امن نگه دارید. بدون APP_KEY، اطلاعات رمزشده قابل خواندن نیست.
