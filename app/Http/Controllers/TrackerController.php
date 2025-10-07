<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TrackerController extends Controller
{
    public function serve()
    {
        $apiUrl = config('app.url') . '/api/track';
        // Or use a custom config: config('services.tracking.api_url')

        $debug = config('app.debug') ? 'true' : 'false';

        // Read your tracker.js file
        $trackerJs = file_get_contents(public_path('js/tracker.js'));

        // Replace the placeholder with actual ENV value
        $trackerJs = str_replace(
            'var API_ENDPOINT = window.APP_URL;',
            "var API_ENDPOINT = '{$apiUrl}';",
            $trackerJs
        );

        // Optionally replace DEBUG value
        $trackerJs = str_replace(
            'var DEBUG = false;',
            "var DEBUG = {$debug};",
            $trackerJs
        );

        return response($trackerJs)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'public, max-age=3600'); // Cache for 1 hour
    }
}