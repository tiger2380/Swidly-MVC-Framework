<?php

declare(strict_types=1);

namespace Swidly\Core\Commands;

use Swidly\Core\DB;
use Swidly\Core\Services\MailerService;

class BookingCommand extends AbstractCommand
{
    public function execute(): void
    {
        $method = $this->options['name'] ?? '';

        match ($method) {
            'send-reminders' => $this->sendReminders(),
            'stats' => $this->showStats(),
            default => throw new \InvalidArgumentException("Unknown booking command: {$method}"),
        };
    }

    /**
     * Send reminder emails for confirmed appointments happening within 24 hours
     * Usage: php bin/console booking:send-reminders
     */
    private function sendReminders(): void
    {
        \formatPrintLn(['cyan', 'bold'], "Checking for upcoming appointments...");

        $appointments = DB::query(
            "SELECT * FROM gem_appointments 
             WHERE status = 'confirmed' 
             AND reminder_sent = 0 
             AND appointment_date IS NOT NULL
             AND appointment_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)",
            []
        )->fetchAll(\PDO::FETCH_OBJ);

        if (empty($appointments)) {
            \formatPrintLn(['yellow'], "No upcoming appointments need reminders.");
            return;
        }

        $mailer = new MailerService();
        $sent = 0;

        foreach ($appointments as $apt) {
            $name = htmlspecialchars($apt->first_name, ENT_QUOTES, 'UTF-8');
            $service = htmlspecialchars($apt->service, ENT_QUOTES, 'UTF-8');
            $formattedDate = date('l, F j, Y \a\t g:i A', strtotime($apt->appointment_date));

            $html = "
            <div style='font-family: DM Sans, Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: #0d1f3c; padding: 32px; text-align: center;'>
                    <h1 style='color: #c9a84c; margin: 0; font-family: Cormorant Garamond, serif;'>JBS Tax LLC</h1>
                    <p style='color: #fff; margin: 4px 0 0; font-size: 12px; letter-spacing: 2px; text-transform: uppercase;'>Janeliz Business Solutions</p>
                </div>
                <div style='padding: 32px; background: #fff;'>
                    <h2 style='color: #0d1f3c; margin-top: 0;'>⏰ Appointment Reminder</h2>
                    <p style='color: #555; line-height: 1.6;'>Hi {$name}, this is a friendly reminder about your upcoming appointment:</p>
                    <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; margin: 24px 0; border-radius: 0 4px 4px 0;'>
                        <p style='margin: 0 0 8px; color: #0d1f3c; font-weight: 600; font-size: 16px;'>📅 {$formattedDate}</p>
                        <p style='margin: 0; color: #555;'>Service: {$service}</p>
                    </div>
                    <p style='color: #555; line-height: 1.6;'><strong>Location:</strong> 3431 W Frye Road, Unit 4, Chandler, AZ 85226</p>
                    <p style='color: #555; line-height: 1.6;'>Please bring any relevant documents. If you need to reschedule, call <strong>480-930-8892</strong>.</p>
                </div>
                <div style='background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #999;'>
                    <p style='margin: 0;'>© " . date('Y') . " Janeliz Business Solutions LLC</p>
                </div>
            </div>";

            $success = $mailer->send(
                $apt->email,
                "Reminder: Appointment on {$formattedDate}",
                $html,
                "Hi {$name}, reminder for your {$service} appointment on {$formattedDate}."
            );

            if ($success) {
                DB::query("UPDATE gem_appointments SET reminder_sent = 1 WHERE id = ?", [$apt->id]);
                $sent++;
                \formatPrintLn(['green'], "  ✓ Reminder sent to {$apt->email}");
            } else {
                \formatPrintLn(['red'], "  ✗ Failed to send to {$apt->email}");
            }
        }

        \formatPrintLn(['green', 'bold'], "Done. Sent {$sent}/" . count($appointments) . " reminders.");
    }

    /**
     * Show appointment statistics
     * Usage: php bin/console booking:stats
     */
    private function showStats(): void
    {
        $stats = DB::query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM gem_appointments")->fetch(\PDO::FETCH_OBJ);

        \formatPrintLn(['cyan', 'bold'], "Appointment Statistics:");
        \formatPrintLn(['white'], "  Total:     {$stats->total}");
        \formatPrintLn(['yellow'], "  Pending:   {$stats->pending}");
        \formatPrintLn(['green'], "  Confirmed: {$stats->confirmed}");
        \formatPrintLn(['cyan'], "  Completed: {$stats->completed}");
        \formatPrintLn(['red'], "  Cancelled: {$stats->cancelled}");
    }
}
