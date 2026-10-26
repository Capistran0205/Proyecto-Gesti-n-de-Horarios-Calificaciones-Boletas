<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        ResetPassword::toMailUsing(function ($usuario, string $token) {
            $url = route('password.reset', ['token' => $token, 'usuario' => $usuario->usuario]);

            // En desarrollo, el enlace queda en storage\logs\laravel.log para probar sin SMTP
            if (app()->environment('local')) {
                logger("Enlace de recuperación para {$usuario->usuario}: {$url}");
            }

            return (new MailMessage)
                ->subject('Recupera tu contraseña')
                ->view('emails.recuperar', ['usuario' => $usuario->usuario, 'url' => $url]);
        });
    }
}