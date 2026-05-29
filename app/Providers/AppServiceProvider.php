<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Windows PHP often ships without a CA bundle; point cURL/OpenSSL at
        // the bundled Mozilla roots so outbound HTTPS (Cloudinary, Mailgun, …)
        // succeeds in local dev.
        if (PHP_OS_FAMILY === 'Windows') {
            $caBundle = storage_path('app/cacert.pem');
            if (is_file($caBundle)) {
                putenv('CURL_CA_BUNDLE=' . $caBundle);
                putenv('SSL_CERT_FILE=' . $caBundle);
                $_ENV['CURL_CA_BUNDLE'] = $caBundle;
                $_ENV['SSL_CERT_FILE'] = $caBundle;
            }
        }
    }
}
