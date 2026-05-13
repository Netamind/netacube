<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

class MailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /** @var \Illuminate\Mail\MailManager $mailManager */
        $mailManager = $this->app->make('mail.manager');

        $mailManager->extend('smtp', function () {
            $config = config('mail.mailers.smtp');

            $transport = new EsmtpTransport(
                $config['host'],
                (int) $config['port'],
                $config['encryption'] === 'ssl',
            );

            $transport->setUsername($config['username']);
            $transport->setPassword($config['password']);

            /** @var SocketStream $stream */
            $stream = $transport->getStream();
            $stream->setStreamOptions([
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ]);

            return $transport;
        });
    }
}