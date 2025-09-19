# Osiris Website Tracking System

A Laravel-based application for monitoring and logging website visitor activity. Osiris captures visitor information, session details, and specific events triggered by users, providing comprehensive analytics through a Filament admin interface.

## Features

- **Visitor Tracking**: Identify and track unique website visitors using IP addresses and user agents
- **Session Management**: Monitor individual browsing sessions with start/end times and duration
- **Event Logging**: Record specific user actions such as page views, clicks, and form submissions
- **Attribution Tracking**: Capture UTM parameters, Google Click IDs (gclid), and Facebook Click IDs (fbclid)
- **Admin Dashboard**: Beautiful Filament-based interface for viewing and managing tracked data
- **API Integration**: RESTful API endpoints for receiving tracking data from frontend applications
- **GDPR Compliant**: Designed with privacy regulations in mind

## Requirements

- PHP 8.2 or higher
- Laravel Framework 12.0 or higher
- Filament 4.0 or higher
- Jenssegers Agent 2.6 or higher
- Laravel Sanctum 4.0 or higher

## Installation

1. Clone or download the Osiris tracking application:
   ```bash
   git clone https://your-repository-url/osiris.git
   ```

2. Install dependencies via composer:
   ```bash
   composer install
   ```

3. Configure your environment variables in `.env`:
   ```bash
   # Generate application key
   php artisan key:generate
   
   # Configure database settings
   # DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
   
   # Set your CRM API token
   CRM_API_TOKEN=your-secret-token
   
   # Optionally restrict domains (set to true to enable domain restriction)
   RESTRICT_DOMAIN=false
   # If RESTRICT_DOMAIN=true, also set ALLOWED_DOMAINS in config/osiris.php
   ```

4. Run the migrations:
   ```bash
   php artisan migrate
   ```

5. Start the Laravel development server:
   ```bash
   php artisan serve
   ```

## Integration

### Manual Implementation

Send data to the API endpoint:

```javascript
// Send tracking data using XMLHttpRequest
var xhr = new XMLHttpRequest();
xhr.open('POST', 'https://your-domain.com/api/events', true);
xhr.setRequestHeader('Content-Type', 'application/json');
xhr.setRequestHeader('X-CRM-TOKEN', 'your-api-token');

var payload = {
  event: 'page_view', // Required
  url: window.location.href,
  referrer: document.referrer,
  visitor_uuid: 'unique-visitor-id', // Optional, will be generated if not provided
  session_uuid: 'session-id', // Optional, will be generated if not provided
  query_strings: { // UTM parameters and click IDs
    utm_source: 'google',
    utm_medium: 'cpc',
    gclid: 'google-click-id'
  },
  data: { // Additional event data
    value: 99.99,
    currency: 'USD'
  }
};

xhr.send(JSON.stringify(payload));
```


## API Documentation

### Endpoint

`POST /api/events`

### Headers

- `X-CRM-TOKEN`: Your configured CRM API token (required)

### Parameters

- `event` (required): Event type (page_view, click, form_submit, etc.)
- `url`, `referrer`: Page tracking data
- `visitor_uuid`, `session_uuid`: Existing identifiers (optional)
- `visitor_name`, `visitor_email`, `visitor_phone`: Visitor contact information (optional)
- `query_strings`: Object containing URL parameters for attribution tracking (optional)
- `data`: Additional event metadata (optional)

### Query String Attribution Parameters

These parameters are automatically extracted from the `query_strings` object:

- `gclid`: Google Click ID
- `fbclid`: Facebook Click ID
- `utm_source`: UTM source parameter
- `utm_medium`: UTM medium parameter
- `utm_campaign`: UTM campaign parameter
- `utm_content`: UTM content parameter
- `utm_term`: UTM term parameter

### Response

The API returns visitor and session UUIDs for client-side storage:

```json
{
  "success": true,
  "event_id": 123,
  "visitor_uuid": "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx",
  "session_uuid": "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx"
}
```

## Configuration

The system can be configured through `config/osiris.php`:

- `crm-api-token`: The token required for API authentication (set via CRM_API_TOKEN env variable)
- `restrict_domain`: Boolean to enable domain restriction (set via RESTRICT_DOMAIN env variable)
- `allowed-domains`: Array of allowed domains when restriction is enabled (empty by default)

Example configuration:
```php
return [
    'crm-api-token' => env('CRM_API_TOKEN', 'your-secret-token'),
    'restrict_domain' => env('RESTRICT_DOMAIN', false),
    'allowed-domains' => [
        'http://example.com',
        'https://example.com'
    ],
];
```

## Admin Interface

Access the Filament admin panel to view analytics data:

1. **Events**: View, create, edit, and delete event records
2. **Visitors**: Manage visitor information and related events
3. **Tracking Sessions**: Monitor browsing sessions and associated activities

The panel is configured in `app/Providers/Filament/AdminPanelProvider.php`.

## Development

### Factories and Seeders

For testing and development, use the provided factories:
- `EventFactory.php`
- `VisitorFactory.php` 
- `TrackingSessionFactory.php`

And seeders:
- `EventSeeder.php`
- `VisitorSeeder.php`
- `DatabaseSeeder.php`

### Testing

Run tests with PestPHP:

```bash
./vendor/bin/pest
```

## Best Practices

1. Ensure GDPR compliance for visitor data
2. Index frequently queried database fields (IP addresses, timestamps)
3. Use queue workers for high-volume event logging to prevent performance issues
4. Secure your CRM API token and never expose it in client-side code
5. Regularly backup your tracking data

## License

The Osiris Tracker is open-source software licensed under the MIT license.