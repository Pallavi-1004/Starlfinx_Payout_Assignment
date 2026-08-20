# Laravel Payout Management API

A small payout management application built for Laravel 8.83.29 and PHP 7.3.9. It provides a transactional payout API and a Blade/jQuery management page.

## Requirements

- PHP 7.3.9 or compatible PHP 7.3 runtime
- Composer
- MySQL 5.7+ or 8+
- Node.js/NPM is not required because the page uses jQuery from a CDN

## Installation

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Create a MySQL database, then configure `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`.

```bash
php artisan migrate --seed
php artisan serve
```

Open `http://127.0.0.1:8000` for the Blade interface.

## API Endpoints

### Create payout

`POST /api/payouts`

```json
{
  "transaction_id": "TXN10001",
  "beneficiary_name": "John Doe",
  "amount": 5000
}
```

Returns HTTP 201 with `success`, `message`, and a `data` payout resource. New payouts always start as `PENDING`.

### List payouts

`GET /api/payouts?transaction_id=TXN100&status=SUCCESS&page=1`

Transaction IDs support partial matching. Results are paginated at ten records per page and include Laravel `links` and `meta` fields.

### Update status

`PATCH /api/payouts/{id}/status`

```json
{ "status": "SUCCESS" }
```

Only `PENDING` payouts may transition to `SUCCESS` or `FAILED`. Completed payouts cannot be changed.

## Balance Logic

Seeding creates one balance record with `100000.00`. Payout creation obtains that record with `lockForUpdate()` inside `DB::transaction()`, checks the available amount, deducts the requested amount, and creates the payout before committing. A failure rolls back both operations. The row lock serializes competing payout requests, so the balance cannot be overspent.

The database also enforces a unique index on `transaction_id`. PHP 7.3 does not support native backed enums, so `App\\Enums\\PayoutStatus` is a PHP-7.3-compatible value class containing the same `PENDING`, `SUCCESS`, and `FAILED` constants.

## Testing

Tests use an isolated in-memory SQLite database configured in `phpunit.xml`:

```bash
php artisan test
```

The feature suite covers creation, balance deduction, duplicate IDs, validation, insufficient funds, status transitions, search, filtering, and pagination.

## Architecture

```text
Route
  -> Form Request
  -> Controller
  -> PayoutService
  -> Eloquent models
  -> Database
```

The Blade page at `/` uses jQuery AJAX for listing, searching, creating payouts, and updating pending statuses. It sends Laravel's CSRF token on AJAX requests; the API routes remain stateless and are validated through Form Requests.

## Error Responses

Validation and business errors use HTTP 422 with `success: false` and a message. Missing payouts return HTTP 404. Unexpected exceptions are logged server-side and return a generic HTTP 500 response without internal details.
