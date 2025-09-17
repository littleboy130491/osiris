<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Event;
use Illuminate\Support\Str;

echo "=== Domain Filter Debug ===\n\n";

// Check total events
$totalEvents = Event::count();
echo "Total Events: $totalEvents\n";

// Check events with URLs
$eventsWithUrls = Event::whereNotNull('url')->count();
echo "Events with URLs: $eventsWithUrls\n\n";

// Get sample URLs and test domain extraction
$sampleUrls = Event::whereNotNull('url')->limit(10)->pluck('url');

echo "Sample URLs and extracted domains:\n";
foreach ($sampleUrls as $url) {
    $host = parse_url($url, PHP_URL_HOST);
    $domain = $host ? Str::replaceFirst('www.', '', $host) : null;
    echo "URL: $url\n";
    echo "Host: " . ($host ?: 'NULL') . "\n";
    echo "Domain: " . ($domain ?: 'NULL') . "\n";
    echo "---\n";
}

// Test the actual options query
echo "\nTesting domain options query:\n";
$domains = Event::query()
    ->whereNotNull('url')
    ->where('url', '!=', '')
    ->pluck('url')
    ->map(function ($url) {
        $host = parse_url($url, PHP_URL_HOST);
        if ($host) {
            return Str::replaceFirst('www.', '', $host);
        }
        return null;
    })
    ->filter()
    ->unique()
    ->values()
    ->mapWithKeys(fn ($d) => [$d => $d])
    ->toArray();

echo "Found domains: " . implode(', ', array_keys($domains)) . "\n";
echo "Domain count: " . count($domains) . "\n";

// Test filtering
if (!empty($domains)) {
    $testDomain = array_keys($domains)[0];
    echo "\nTesting filter with domain: $testDomain\n";
    
    $filteredCount = Event::where(function ($q) use ($testDomain) {
        $q->where('url', 'like', '%://' . $testDomain . '%')
          ->orWhere('url', 'like', '%://www.' . $testDomain . '%');
    })->count();
    
    echo "Filtered events count: $filteredCount\n";
}