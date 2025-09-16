<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        // some possible event types
        $eventTypes = ['page_view', 'click', 'generate_lead'];

        // random query strings
        $utmSources = ['google', 'facebook', 'newsletter', 'organic'];
        $utmMediums = ['cpc', 'email', 'organic', 'referral'];
        $utmCampaigns = ['spring_sale', 'black_friday', 'launch', 'promo'];

        // pick random attribution or sometimes null
        $gclid = $this->faker->boolean(30) ? Str::random(20) : null;
        $fbclid = $this->faker->boolean(30) ? Str::random(20) : null;
        $utm_source = $this->faker->boolean(50) ? $this->faker->randomElement($utmSources) : null;
        $utm_medium = $this->faker->boolean(50) ? $this->faker->randomElement($utmMediums) : null;
        $utm_campaign = $this->faker->boolean(50) ? $this->faker->randomElement($utmCampaigns) : null;

        // build query_strings JSON from what’s set
        $queryStrings = [];
        if ($gclid) {
            $queryStrings['gclid'] = $gclid;
        }
        if ($fbclid) {
            $queryStrings['fbclid'] = $fbclid;
        }
        if ($utm_source) {
            $queryStrings['utm_source'] = $utm_source;
        }
        if ($utm_medium) {
            $queryStrings['utm_medium'] = $utm_medium;
        }
        if ($utm_campaign) {
            $queryStrings['utm_campaign'] = $utm_campaign;
        }

        // generate some fake visitor metadata
        $userAgent = $this->faker->userAgent();
        // parse device/browser/os could use some package, but for dummy, just split or store raw
        // IP
        $ip = $this->faker->ipv4();

        return [
            'user_id' => (string) Str::uuid(),
            'event_name' => $this->faker->randomElement($eventTypes),
            'url' => $this->faker->url(),
            'referrer' => $this->faker->boolean(40) ? $this->faker->url() : null,
            'gclid' => $gclid,
            'fbclid' => $fbclid,
            'utm_source' => $utm_source,
            'utm_medium' => $utm_medium,
            'utm_campaign' => $utm_campaign,
            'query_strings' => $queryStrings,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device' => $this->faker->randomElement(['Desktop', 'Mobile', 'Tablet']),
            'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera']),
            'os' => $this->faker->randomElement(['Windows', 'macOS', 'Linux', 'Android', 'iOS']),
            'meta' => [
                'button_text' => $this->faker->words(2, true),
                'random_property' => $this->faker->word(),
            ],
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => now(),
        ];
    }
}
