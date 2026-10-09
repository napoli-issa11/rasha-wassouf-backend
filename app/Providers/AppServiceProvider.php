<?php

namespace App\Providers;

use App\Services\CloudinaryService;
use Cloudinary\Cloudinary as CloudinarySdk;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Bridge\Mailtrap\Transport\MailtrapApiTransport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Cloudinary singleton with sanitized credentials and local SSL bypass
        $this->app->singleton(CloudinarySdk::class, function () {
            return CloudinaryService::getClient();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Mailtrap demo domain is demomailtrap.co. Automatically normalize demomailtrap.com -> demomailtrap.co
        $fromAddress = (string) config('mail.from.address');
        if (str_ends_with(strtolower($fromAddress), '@demomailtrap.com')) {
            config(['mail.from.address' => str_ireplace('@demomailtrap.com', '@demomailtrap.co', $fromAddress)]);
        }

        // Register Mailtrap API mailer driver
        Mail::extend('mailtrap', function (array $config = []) {
            $apiKey = $config['api_key']
                ?? config('services.mailtrap.api_key')
                ?? env('MAILTRAP_API_KEY');

            $inboxId = $config['inbox_id']
                ?? config('services.mailtrap.inbox_id')
                ?? env('MAILTRAP_INBOX_ID');

            $client = class_exists(HttpClient::class) ? HttpClient::create() : null;

            return new MailtrapApiTransport(
                token: (string) $apiKey,
                client: $client,
                inboxId: !empty($inboxId) ? (int) $inboxId : null,
            );
        });
    }
}
