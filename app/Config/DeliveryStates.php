<?php
namespace App\Config;

/**
 * Delivery States Configuration
 * 
 * Defines the state machine for volunteer deliveries.
 * Every transition is explicitly defined with:
 * - from: valid previous states
 * - to: allowed next states
 * - actor: who can trigger this transition
 * - requires_gps: whether GPS coordinates are needed
 * - auto_trigger: whether this can be triggered automatically
 */
class DeliveryStates {
    
    // All possible delivery statuses
    const ASSIGNED   = 'assigned';
    const ACCEPTED   = 'accepted';
    const PICKED_UP  = 'picked_up';
    const IN_TRANSIT = 'in_transit';
    const DELIVERED  = 'delivered';
    const CANCELLED  = 'cancelled';

    // Terminal states (no further transitions allowed)
    const TERMINAL_STATES = [self::DELIVERED, self::CANCELLED];

    // Active/in-progress states
    const ACTIVE_STATES = [self::ACCEPTED, self::PICKED_UP, self::IN_TRANSIT];

    /**
     * Valid transitions matrix.
     * Format: from_state => [to_state => ['actor' => [...], 'requires_gps' => bool, 'auto_trigger' => bool]]
     */
    public static function getTransitions(): array {
        return [
            self::ASSIGNED => [
                self::ACCEPTED => [
                    'actor' => ['volunteer', 'admin', 'system'],
                    'requires_gps' => false,
                    'auto_trigger' => true,
                    'notes_required' => false,
                ],
                self::CANCELLED => [
                    'actor' => ['volunteer', 'admin', 'system'],
                    'requires_gps' => false,
                    'auto_trigger' => false,
                    'notes_required' => true,
                ],
            ],
            self::ACCEPTED => [
                self::PICKED_UP => [
                    'actor' => ['volunteer', 'system'],
                    'requires_gps' => true,
                    'auto_trigger' => true,  // geofence can auto-trigger
                    'notes_required' => false,
                ],
                self::CANCELLED => [
                    'actor' => ['volunteer', 'admin'],
                    'requires_gps' => false,
                    'auto_trigger' => false,
                    'notes_required' => true,
                ],
            ],
            self::PICKED_UP => [
                self::IN_TRANSIT => [
                    'actor' => ['volunteer', 'system'],
                    'requires_gps' => true,
                    'auto_trigger' => true,
                    'notes_required' => false,
                ],
                self::DELIVERED => [
                    'actor' => ['volunteer', 'system'],
                    'requires_gps' => true,
                    'auto_trigger' => true,
                    'notes_required' => false,
                ],
                self::CANCELLED => [
                    'actor' => ['admin'],
                    'requires_gps' => false,
                    'auto_trigger' => false,
                    'notes_required' => true,
                ],
            ],
            self::IN_TRANSIT => [
                self::DELIVERED => [
                    'actor' => ['volunteer', 'system', 'consumer'],
                    'requires_gps' => true,
                    'auto_trigger' => true,
                    'notes_required' => false,
                ],
                self::CANCELLED => [
                    'actor' => ['admin'],
                    'requires_gps' => false,
                    'auto_trigger' => false,
                    'notes_required' => true,
                ],
            ],
        ];
    }

    /**
     * Check if a transition is valid.
     */
    public static function isValidTransition(string $from, string $to, string $actor): bool {
        $transitions = self::getTransitions();
        if (!isset($transitions[$from][$to])) {
            return false;
        }
        return in_array($actor, $transitions[$from][$to]['actor'], true);
    }

    /**
     * Get transition config for a given from->to pair.
     */
    public static function getTransitionConfig(string $from, string $to): ?array {
        return self::getTransitions()[$from][$to] ?? null;
    }

    /**
     * Get all statuses.
     */
    public static function getAllStatuses(): array {
        return [self::ASSIGNED, self::ACCEPTED, self::PICKED_UP, self::IN_TRANSIT, self::DELIVERED, self::CANCELLED];
    }

    /**
     * Check if a status is terminal (no further transitions).
     */
    public static function isTerminal(string $status): bool {
        return in_array($status, self::TERMINAL_STATES, true);
    }

    /**
     * Check if a status is in-progress.
     */
    public static function isActive(string $status): bool {
        return in_array($status, self::ACTIVE_STATES, true);
    }

    /**
     * Get display label for a status.
     */
    public static function getLabel(string $status): string {
        $labels = [
            self::ASSIGNED => 'Looking for Volunteer',
            self::ACCEPTED => 'Volunteer Assigned',
            self::PICKED_UP => 'Food Picked Up',
            self::IN_TRANSIT => 'On the Way',
            self::DELIVERED => 'Delivered ✓',
            self::CANCELLED => 'Cancelled ✗',
        ];
        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
}
