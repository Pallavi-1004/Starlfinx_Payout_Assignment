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

When a payout is marked as `FAILED`, its full amount is automatically returned to the balance. The payout status update and balance refund are committed atomically.

### Get current balance

`GET /api/balance`

Returns the current balance:

```json
{
  "success": true,
  "data": { "balance": "100000.00" }
}
```

### Add balance

`POST /api/balance/add`

```json
{ "amount": 5000 }
```

The amount must be numeric and greater than zero. The balance is updated inside a transaction with a row lock.

## Balance Logic

Seeding creates one balance record with `100000.00`. Payout creation obtains that record with `lockForUpdate()` inside `DB::transaction()`, checks the available amount, deducts the requested amount, and creates the payout before committing. A failure rolls back both operations. The row lock serializes competing payout requests, so the balance cannot be overspent.

If a pending payout is marked as `FAILED`, the payout amount is credited back to the balance before the status change is committed. Marking a payout as `SUCCESS` does not change the balance because the amount was already deducted when the payout was created.

The database also enforces a unique index on `transaction_id`. PHP 7.3 does not support native backed enums, so `App\\Enums\\PayoutStatus` is a PHP-7.3-compatible value class containing the same `PENDING`, `SUCCESS`, and `FAILED` constants.

## Testing

Tests use an isolated in-memory SQLite database configured in `phpunit.xml`:

```bash
php artisan test
```

The feature suite covers creation, balance deduction and addition, failed-payout refunds, duplicate IDs, validation, insufficient funds, status transitions, search, filtering, and pagination.

## Architecture

```text
Route
  -> Form Request
  -> Controller
  -> PayoutService
  -> Eloquent models
  -> Database
```

The Blade page at `/` uses jQuery AJAX for listing, searching, creating payouts, updating pending statuses, and reading or adding balance. The current balance refreshes every five seconds and after balance-changing actions. The balance form opens from the plus button, closes after a successful top-up, and alerts dismiss automatically after 1.5 seconds. It sends Laravel's CSRF token on AJAX requests; the API routes remain stateless and are validated through Form Requests.

## Error Responses

Validation and business errors use HTTP 422 with `success: false` and a message. Missing payouts return HTTP 404. Unexpected exceptions are logged server-side and return a generic HTTP 500 response without internal details.
