<?php

declare(strict_types=1);

namespace Swidly\Core\Services;

use Textlocal;
use TextlocalException;

class SmsService
{
    public function send(string $phoneNumber, string $message): bool
    {
        $apiKey = (string) (config('sms::textlocal_api_key') ?? '');
        $sender = (string) (config('sms::sender') ?? 'GEMGDE');

        if ($apiKey === '' || $phoneNumber === '') {
            error_log('SmsService error: missing Textlocal API key or phone number.');
            return false;
        }

        try {
            $textlocal = new Textlocal(false, false, $apiKey);
            $numbers = [$phoneNumber];
            $response = $textlocal->sendSms($numbers, $message, $sender);

            if (is_array($response) && ($response['status'] ?? '') === 'success') {
                return true;
            }

            error_log('SmsService error: Textlocal send failed.');
            return false;
        } catch (TextlocalException $e) {
            error_log('SmsService error: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            error_log('SmsService error: ' . $e->getMessage());
            return false;
        }
    }
}
