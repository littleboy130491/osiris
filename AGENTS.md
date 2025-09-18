# AGENTS

This file documents the agents or related components in the tracking system.

## Overview

The tracking system is a Laravel-based application designed to monitor and log website visitor activity. It captures visitor information, session details, and specific events triggered by users. The system utilizes Filament for the admin interface to manage and view tracked data. Key components include models for structured data storage, API endpoints for real-time event logging, factories and seeders for development/testing, and migration files for database schema.

The system does not implement traditional "tracking agents" (e.g., JavaScript snippets or bots) but focuses on backend data collection and management. Visitor identification relies on IP addresses, user agents, and timestamps, assuming integration with frontend tracking scripts that send data to the API.

## Package Versions

This project uses the following key package versions (as defined in [composer.json](composer.json)):

- PHP: ^8.2
- Laravel Framework: ^12.0
- Filament: ^4.0
- Jenssegers Agent: ^2.6 (used for parsing user agent information)
- Laravel Sanctum: ^4.0 (for API authentication)
- Laravel Tinker: ^2.10.1

Dev dependencies include:
- FakerPHP/Faker: ^1.23
- Laravel Pint: ^1.24
- PestPHP/Pest: ^4.1
- And others for testing and development.

For exact installed versions, run `composer show` or check `composer.lock`.

## Core Models

### Visitor
- **Purpose**: Represents unique website visitors.
- **Key Attributes**:
  - IP address (unique identifier).
  - User agent (browser/device information).
  - First and last visit timestamps.
  - Location data (if integrated).
- **Relationships**:
  - Has many `TrackingSession` instances.
  - Has many `Event` instances (through sessions).
- **File**: [app/Models/Visitor.php](app/Models/Visitor.php)

### TrackingSession
- **Purpose**: Tracks individual browsing sessions for visitors.
- **Key Attributes**:
  - Session ID.
  - Start and end timestamps.
  - Duration.
  - Visitor foreign key.
- **Relationships**:
  - Belongs to a `Visitor`.
  - Has many `Event` instances.
- **File**: [app/Models/TrackingSession.php](app/Models/TrackingSession.php)

### Event
- **Purpose**: Logs specific user actions or page interactions.
- **Key Attributes**:
  - Event type (e.g., page view, click, form submit).
  - Timestamp.
  - Additional metadata (e.g., page URL, element ID).
  - Foreign keys to `Visitor` and `TrackingSession`.
- **Relationships**:
  - Belongs to a `Visitor`.
  - Belongs to a `TrackingSession`.
- **File**: [app/Models/Event.php](app/Models/Event.php)

## Admin Interface (Filament Resources)

Filament provides CRUD operations and visualization for the tracking data.

- **Events Resource**: Manage events with tables, forms, and infolists.
  - Table: [app/Filament/Resources/Events/Tables/EventsTable.php](app/Filament/Resources/Events/Tables/EventsTable.php)
  - Form: [app/Filament/Resources/Events/Schemas/EventForm.php](app/Filament/Resources/Events/Schemas/EventForm.php)
  - Infolist: [app/Filament/Resources/Events/Schemas/EventInfolist.php](app/Filament/Resources/Events/Schemas/EventInfolist.php)
  - Resource: [app/Filament/Resources/Events/EventResource.php](app/Filament/Resources/Events/EventResource.php)

- **Visitors Resource**: Manage visitor records.
  - Resource: [app/Filament/Resources/Visitors/VisitorResource.php](app/Filament/Resources/Visitors/VisitorResource.php)
  - Schemas and Tables: Similar structure to Events.

- **Tracking Sessions Resource**: Manage session data.
  - Resource: [app/Filament/Resources/TrackingSessions/TrackingSessionResource.php](app/Filament/Resources/TrackingSessions/TrackingSessionResource.php)
  - Schemas and Tables: Similar structure to Events.

## API Layer

- **EventController**: Handles incoming event data via API.
  - Endpoint: `POST /api/events` in [routes/api.php](routes/api.php).
  - Functionality: Creates or updates visitors/sessions and logs events.
- **File**: [app/Http/Controllers/Api/EventController.php](app/Http/Controllers/Api/EventController.php)
### API Endpoint Details

**POST /api/events**
- Receives tracking data from frontend applications
- Automatically creates/finds visitors and sessions
- Supports attribution tracking (UTM, GCLID, FBCLID)
- Returns visitor and session UUIDs for client-side storage

**Request Parameters:**
- `event` (required): Event type (page_view, click, form_submit, etc.)
- `url`, `referrer`: Page tracking data
- `visitor_uuid`, `session_uuid`: Existing identifiers (optional)
- `gclid`, `fbclid`, `utm_*`: Attribution parameters
- `query_strings`, `data`: Additional metadata


## Database Structure

- **Migrations**:
  - [2025_09_16_103456_create_events_table.php](database/migrations/2025_09_16_103456_create_events_table.php): Defines events table.
  - [2025_09_17_025244_create_visitors_table.php](database/migrations/2025_09_17_025244_create_visitors_table.php): Defines visitors table.
  - [2025_09_17_025320_create_tracking_sessions_table.php](database/migrations/2025_09_17_025320_create_tracking_sessions_table.php): Defines tracking sessions table.
  - Standard Laravel tables (users, personal access tokens).

- **Factories** (for testing/seeding):
  - [EventFactory.php](database/factories/EventFactory.php)
  - [VisitorFactory.php](database/factories/VisitorFactory.php)
  - [TrackingSessionFactory.php](database/factories/TrackingSessionFactory.php)

- **Seeders**:
  - [EventSeeder.php](database/seeders/EventSeeder.php): Populates sample events.
  - [VisitorSeeder.php](database/seeders/VisitorSeeder.php): Populates sample visitors.
  - [DatabaseSeeder.php](database/seeders/DatabaseSeeder.php): Orchestrates seeding.

## Usage and Integration

1. **Frontend Integration**: Implement a JavaScript tracking script to send visitor data and events to the `/api/events` endpoint on page loads, clicks, etc.
2. **Admin Access**: Use Filament panel (configured in [app/Providers/Filament/AdminPanelProvider.php](app/Providers/Filament/AdminPanelProvider.php)) to view analytics.
3. **Best Practices**:
   - Ensure GDPR compliance for visitor data.
   - Index frequently queried fields (e.g., IP, timestamps).
   - Use queues for high-volume event logging to avoid performance issues.

This documentation serves as a reference for developers maintaining or extending the tracking system.