<?php

declare(strict_types=1);

namespace Swidly\Core\Services;

use SendGrid\Mail\Mail;
use SendGrid;

class MailerService
{
    public function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        error_log("Sending email to $to with subject '$subject'");
        $email = new Mail();
        $email->setFrom(config('mail::from_address') ?? 'no-reply@janeliz.app', config('mail::from_name') ?? 'Gem Guide');
        $email->addTo($to);
        $email->setSubject($subject);
        $email->addContent("text/plain", $textBody !== '' ? $textBody : strip_tags($htmlBody));
        $email->addContent("text/html", $htmlBody);
        error_log("API Key: " . (config('mail::send_grid_api_key') ? 'Present' : 'Missing'));
        $sendgrid = new SendGrid(config('mail::send_grid_api_key') ?? '');
        try {
            $response = $sendgrid->send($email);
            error_log("Email sent with status code: " . $response->statusCode() . " and body: " . $response->body() . " and headers: " . json_encode($response->headers()) . " and all: " . json_encode($response));
            return $response->statusCode() >= 200 && $response->statusCode() < 300;
        } catch (\Exception $e) {
            error_log('Mailer error: ' . $e->getMessage());
            return false;
        }
    }
}