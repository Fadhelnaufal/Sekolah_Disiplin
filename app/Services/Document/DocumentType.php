<?php

namespace App\Services\Document;

class DocumentType
{
    public const PARENT_NOTIFICATION = 'parent_notification';

    public const STUDENT_AGREEMENT = 'student_agreement';

    public const PARENT_CALL = 'parent_call';

    public const DISCIPLINE_HISTORY = 'discipline_history';

    // Legacy aliases
    public const NOTIFICATION = 'notification';

    public const STATEMENT = 'statement';

    public const CALL_1 = 'call_1';

    public const CALL_2 = 'call_2';

    public const CALL_3 = 'call_3';

    public const COUNSELING = 'counseling';

    public const OTHER = 'other';

    /**
     * Map any alias or legacy type to the canonical document type.
     */
    public static function normalize(string $type): string
    {
        return match ($type) {
            self::NOTIFICATION => self::PARENT_NOTIFICATION,
            self::STATEMENT => self::STUDENT_AGREEMENT,
            self::CALL_1, self::CALL_2, self::CALL_3, 'call_general' => self::PARENT_CALL,
            'history', 'recap' => self::DISCIPLINE_HISTORY,
            default => $type,
        };
    }

    /**
     * Get all supported primary types.
     */
    public static function all(): array
    {
        return [
            self::PARENT_NOTIFICATION,
            self::STUDENT_AGREEMENT,
            self::PARENT_CALL,
            self::DISCIPLINE_HISTORY,
        ];
    }
}
