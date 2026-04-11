<?php

namespace Swidly\Core\Commands;

use Swidly\Core\DB;
use Swidly\Core\Model;

class AdminCommand extends AbstractCommand 
{
    public function execute(): void 
    {
        $name = $this->options['name'] ?? '';
        $args = $this->options['args'] ?? [];

        match ($name) {
            'make' => $this->makeAdmin(),
            'remove' => $this->removeAdmin(),
            'list' => $this->listAdmins(),
            'grant-owner' => $this->grantOwnerSubscription(),
            'revoke-owner' => $this->revokeOwnerSubscription(),
            default => $this->showHelp(),
        };
    }

    /**
     * Grant owner subscription manually (no payment)
     */
    private function grantOwnerSubscription(): void
    {
        $args = $this->options['args'] ?? [];
        $email = trim((string) ($args[0] ?? ''));
        $requestedPlaceId = trim((string) ($args[1] ?? ''));

        if ($email === '') {
            formatPrintLn(['red'], "Error: Email is required.");
            formatPrintLn(['white'], "Usage: php bin/console admin:grant-owner user@example.com [place_id]");
            return;
        }

        try {
            $user = DB::query(
                "SELECT id, email, name FROM gem_users WHERE email = ? LIMIT 1",
                [$email]
            )->fetch();

            if (!$user) {
                formatPrintLn(['red'], "✗ Error: User with email '{$email}' not found.");
                return;
            }

            $userId = (string) ($user['id'] ?? '');
            $userName = (string) ($user['name'] ?? $email);

            $placeId = $this->resolveEligibleOwnerPlaceId($userId, $requestedPlaceId);
            if ($placeId === null) {
                formatPrintLn(['red'], "✗ Error: No eligible owner place found for this user.");
                formatPrintLn(['white'], "Provide a place ID as the second argument or ensure they own an approved, verified place.");
                return;
            }

            $expiresAt = (new \DateTime('+10 years'))->format('Y-m-d H:i:s');
            $nextBillingDate = (new \DateTime('+1 month'))->format('Y-m-d H:i:s');

            DB::query(
                "UPDATE gem_subscriptions
                 SET status = 'cancelled', cancelled_at = NOW()
                 WHERE user_id = ? AND place_id = ? AND status = 'active'",
                [$userId, $placeId]
            );

            $existingSubscription = DB::query(
                "SELECT id FROM gem_subscriptions WHERE user_id = ? AND place_id = ? ORDER BY started_at DESC LIMIT 1",
                [$userId, $placeId]
            )->fetch();

            if ($existingSubscription) {
                $existingId = (string) ($existingSubscription['id'] ?? '');
                DB::query(
                    "UPDATE gem_subscriptions
                     SET status = 'active', plan_type = 'business_owner', amount_cents = 0, billing_cycle = 'manual', next_billing_date = ?, cancelled_at = NULL
                     WHERE id = ?",
                    [$nextBillingDate, $existingId]
                );
            } else {
                DB::query(
                    "INSERT INTO gem_subscriptions (id, user_id, place_id, status, plan_type, amount_cents, billing_cycle, next_billing_date, started_at)
                     VALUES (?, ?, ?, 'active', 'business_owner', 0, 'manual', ?, NOW())",
                    [Model::generateUuid(), $userId, $placeId, $nextBillingDate]
                );
            }

            DB::query(
                "UPDATE gem_users
                 SET subscription_plan = 'business_owner', subscription_status = 'active', subscription_expires_at = ?, is_owner = 1
                 WHERE id = ?",
                [$expiresAt, $userId]
            );

            formatPrintLn(['green', 'bold'], "✓ Owner subscription granted successfully!");
            formatPrintLn(['white'], "User: {$userName} ({$email})");
            formatPrintLn(['white'], "Place ID: {$placeId}");
            formatPrintLn(['white'], "Plan: business_owner (manual comped, no payment required)");
            formatPrintLn(['white'], "Expires At: {$expiresAt}");
        } catch (\Exception $e) {
            formatPrintLn(['red'], "✗ Error: " . $e->getMessage());
        }
    }

    private function resolveEligibleOwnerPlaceId(string $userId, string $requestedPlaceId): ?string
    {
        if ($requestedPlaceId !== '') {
            $place = DB::query(
                "SELECT id FROM gem_places WHERE id = ? AND owner_id = ? AND verified = 1 AND approval_status = 'approved' LIMIT 1",
                [$requestedPlaceId, $userId]
            )->fetch();

            if ($place) {
                return (string) ($place['id'] ?? null);
            }

            return null;
        }

        $place = DB::query(
            "SELECT id FROM gem_places WHERE owner_id = ? AND verified = 1 AND approval_status = 'approved' ORDER BY updated_at DESC LIMIT 1",
            [$userId]
        )->fetch();

        if (!$place) {
            return null;
        }

        return (string) ($place['id'] ?? null);
    }

    /**
     * Revoke owner subscription manually
     */
    private function revokeOwnerSubscription(): void
    {
        $args = $this->options['args'] ?? [];
        $email = trim((string) ($args[0] ?? ''));
        $requestedPlaceId = trim((string) ($args[1] ?? ''));

        if ($email === '') {
            formatPrintLn(['red'], "Error: Email is required.");
            formatPrintLn(['white'], "Usage: php bin/console admin:revoke-owner user@example.com [place_id]");
            return;
        }

        try {
            $user = DB::query(
                "SELECT id, email, name FROM gem_users WHERE email = ? LIMIT 1",
                [$email]
            )->fetch();

            if (!$user) {
                formatPrintLn(['red'], "✗ Error: User with email '{$email}' not found.");
                return;
            }

            $userId = (string) ($user['id'] ?? '');
            $userName = (string) ($user['name'] ?? $email);

            if ($requestedPlaceId !== '') {
                DB::query(
                    "UPDATE gem_subscriptions SET status = 'cancelled', cancelled_at = NOW() WHERE user_id = ? AND place_id = ? AND status = 'active'",
                    [$userId, $requestedPlaceId]
                );

                $activeCount = DB::query(
                    "SELECT COUNT(*) AS total FROM gem_subscriptions WHERE user_id = ? AND status = 'active'",
                    [$userId]
                )->fetch();
            } else {
                DB::query(
                    "UPDATE gem_subscriptions SET status = 'cancelled', cancelled_at = NOW() WHERE user_id = ? AND status = 'active'",
                    [$userId]
                );

                $activeCount = ['total' => 0];
            }

            $remainingActive = (int) ($activeCount['total'] ?? 0);
            if ($remainingActive === 0) {
                DB::query(
                    "UPDATE gem_users
                     SET subscription_plan = 'free', subscription_status = 'inactive', subscription_expires_at = NULL
                     WHERE id = ?",
                    [$userId]
                );
            }

            formatPrintLn(['yellow', 'bold'], "✓ Owner subscription access revoked.");
            formatPrintLn(['white'], "User: {$userName} ({$email})");
            if ($requestedPlaceId !== '') {
                formatPrintLn(['white'], "Place ID: {$requestedPlaceId}");
            } else {
                formatPrintLn(['white'], "Scope: all active owner subscriptions for this user");
            }
            formatPrintLn(['white'], "User subscription status: " . ($remainingActive === 0 ? 'inactive/free' : 'still active on other place(s)'));
        } catch (\Exception $e) {
            formatPrintLn(['red'], "✗ Error: " . $e->getMessage());
        }
    }

    /**
     * Make a user admin
     */
    private function makeAdmin(): void 
    {
        $args = $this->options['args'] ?? [];
        $email = $args[0] ?? '';

        if (empty($email)) {
            formatPrintLn(['red'], "Error: Email is required.");
            formatPrintLn(['white'], "Usage: php bin/console admin:make user@example.com");
            return;
        }

        try {
            // Try gem_users table
            $result = DB::query("SELECT id, email, name FROM gem_users WHERE email = ?", [$email])->fetch();
            
            if ($result) {
                DB::query("UPDATE gem_users SET is_admin = 1 WHERE email = ?", [$email]);
                formatPrintLn(['green', 'bold'], "✓ Successfully made {$result['name']} ({$email}) an admin!");
                formatPrintLn(['white'], "They can now access the admin panel at /admin");
                return;
            }

            formatPrintLn(['red'], "✗ Error: User with email '{$email}' not found.");
            
        } catch (\Exception $e) {
            formatPrintLn(['red'], "✗ Error: " . $e->getMessage());
        }
    }

    /**
     * Remove admin privileges from a user
     */
    private function removeAdmin(): void 
    {
        $args = $this->options['args'] ?? [];
        $email = $args[1] ?? '';

        if (empty($email)) {
            formatPrintLn(['red'], "Error: Email is required.");
            formatPrintLn(['white'], "Usage: php bin/console admin:remove user@example.com");
            return;
        }

        try {
            // Try users table first
            $result = DB::query("SELECT id, email, firstName, lastName FROM users WHERE email = ?", [$email])->fetch();
            
            if ($result) {
                DB::query("UPDATE users SET is_admin = 0 WHERE email = ?", [$email]);
                formatPrintLn(['yellow', 'bold'], "✓ Removed admin privileges from {$result['firstName']} {$result['lastName']} ({$email})");
                return;
            }

            // Try gem_users table
            $result = DB::query("SELECT id, email, name FROM gem_users WHERE email = ?", [$email])->fetch();
            
            if ($result) {
                DB::query("UPDATE gem_users SET is_admin = 0 WHERE email = ?", [$email]);
                formatPrintLn(['yellow', 'bold'], "✓ Removed admin privileges from {$result['name']} ({$email})");
                return;
            }

            formatPrintLn(['red'], "✗ Error: User with email '{$email}' not found.");
            
        } catch (\Exception $e) {
            formatPrintLn(['red'], "✗ Error: " . $e->getMessage());
        }
    }

    /**
     * List all admin users
     */
    private function listAdmins(): void 
    {
        try {
            formatPrintLn(['green', 'bold'], "Admin Users:");
            formatPrintLn(['white'], "");

            // List from gem_users table
            $gemAdmins = DB::query("SELECT id, email, name, created_at FROM gem_users WHERE is_admin = 1")->fetchAll();
            
            if (!empty($gemAdmins)) {
                formatPrintLn(['cyan'], "From gem_users table:");
                foreach ($gemAdmins as $admin) {
                    $created = date('Y-m-d', strtotime($admin['created_at'] ?? 'now'));
                    formatPrintLn(['white'], "  • {$admin['name']} ({$admin['email']}) - Joined: {$created}");
                }
            }

            if (empty($admins) && empty($gemAdmins)) {
                formatPrintLn(['yellow'], "No admin users found.");
                formatPrintLn(['white'], "Use 'php bin/console admin:make user@example.com' to create one.");
            }

        } catch (\Exception $e) {
            formatPrintLn(['red'], "✗ Error: " . $e->getMessage());
        }
    }

    /**
     * Show help information
     */
    private function showHelp(): void 
    {
        formatPrintLn(['green', 'bold'], "Admin User Management Commands:");
        formatPrintLn(['white'], "");
        formatPrintLn(['cyan'], "php bin/console admin:make <email>");
        formatPrintLn(['white'], "  Make a user an admin");
        formatPrintLn(['white'], "");
        formatPrintLn(['cyan'], "php bin/console admin:remove <email>");
        formatPrintLn(['white'], "  Remove admin privileges from a user");
        formatPrintLn(['white'], "");
        formatPrintLn(['cyan'], "php bin/console admin:list");
        formatPrintLn(['white'], "  List all admin users");
        formatPrintLn(['white'], "");
        formatPrintLn(['cyan'], "php bin/console admin:grant-owner <email> [place_id]");
        formatPrintLn(['white'], "  Grant a manual owner subscription (no payment) to a user/place");
        formatPrintLn(['white'], "");
        formatPrintLn(['cyan'], "php bin/console admin:revoke-owner <email> [place_id]");
        formatPrintLn(['white'], "  Revoke manual/active owner subscription for one place or all places");
        formatPrintLn(['white'], "");
        formatPrintLn(['yellow'], "Examples:");
        formatPrintLn(['white'], "  php bin/console admin:make john@example.com");
        formatPrintLn(['white'], "  php bin/console admin:remove jane@example.com");
        formatPrintLn(['white'], "  php bin/console admin:list");
        formatPrintLn(['white'], "  php bin/console admin:grant-owner owner@example.com");
        formatPrintLn(['white'], "  php bin/console admin:grant-owner owner@example.com 4f8e8ef0-2e4a-4d8a-8e66-123456789abc");
        formatPrintLn(['white'], "  php bin/console admin:revoke-owner owner@example.com");
        formatPrintLn(['white'], "  php bin/console admin:revoke-owner owner@example.com 4f8e8ef0-2e4a-4d8a-8e66-123456789abc");
    }
}
