# Booking and Trip Details Backup System

This backup system allows you to create, manage, and restore backups of booking and tour data in the Laravel backend.

## Features

- **Booking Backups**: Create full backups of booking data including customer information and tour details
- **Tour Backups**: Create backups of tour information
- **Auto-Backup**: Automatically create backups before updates
- **Restore Functionality**: Restore backups to recreate bookings or tours
- **Filtering & Search**: Filter backups by type, booking ID, tour ID, or date range

## Database Structure

### Tables

1. **tours**: Stores tour information
2. **bookings**: Stores booking information
3. **booking_backups**: Stores backup snapshots of bookings and tours

## API Endpoints

All endpoints are prefixed with `/api/backups`

### Create Booking Backup
```
POST /api/backups/booking
Body: {
    "booking_id": 1,
    "reason": "manual" // optional
}
```

### Create Tour Backup
```
POST /api/backups/tour
Body: {
    "tour_id": 1,
    "reason": "manual" // optional
}
```

### Auto-Backup Before Update
```
POST /api/backups/auto-backup
Body: {
    "booking_id": 1
}
```

### List All Backups
```
GET /api/backups?type=booking&booking_id=1&tour_id=1&date_from=2024-01-01&date_to=2024-12-31&per_page=15
```

### Get Specific Backup
```
GET /api/backups/{id}
```

### Restore Backup
```
POST /api/backups/{id}/restore
```

### Delete Backup
```
DELETE /api/backups/{id}
```

## Usage Examples

### Creating a Backup Programmatically

```php
use App\Models\Booking;

$booking = Booking::find(1);
$backup = $booking->createBackup('manual');
```

### Restoring a Backup

```php
use App\Models\BookingBackup;

$backup = BookingBackup::find(1);
$restoredBooking = $backup->restore();
```

### Auto-Backup on Model Events

You can add automatic backups in your Booking model's `boot` method:

```php
protected static function boot()
{
    parent::boot();

    static::updating(function ($booking) {
        $booking->createBackup('before_update');
    });
}
```

## Migration

Run the migrations to create the necessary tables:

```bash
php artisan migrate
```

## Backup Types

- `booking`: Full booking backup including tour data
- `tour`: Tour information backup only
- `full`: Complete backup of both booking and tour

## Backup Reasons

- `manual`: Manually created backup
- `auto`: Automatically created backup
- `before_update`: Created before an update operation
- `before_delete`: Created before a delete operation

## Notes

- Backups store complete JSON snapshots of booking and tour data
- Backups can be restored to create new bookings/tours
- Once a backup is restored, it's marked with `restored_at` timestamp
- Backups are soft-deletable and can be filtered by various criteria

