<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->nullable()->constrained('visitors')->nullOnDelete()
                ->index();
            $table->foreignId('tracking_session_id')->nullable()->constrained('tracking_sessions')->cascadeOnDelete()
                ->index();
            $table->string('event_name')->index();
            $table->string('url')->nullable();
            $table->string('referrer')->nullable();
            
            // Attribution params (for fast filtering)
            $table->string('gclid')->nullable()->index();
            $table->string('fbclid')->nullable()->index();
            $table->string('utm_source')->nullable()->index();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();

            // Raw query params dump
            $table->json('query_strings')->nullable();

            // Visitor info
            $table->string('ip_address', 45)->nullable(); // IPv6 compatible
            $table->text('user_agent')->nullable();
            $table->string('device')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();

            $table->json('meta')->nullable(); // extra event data

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
