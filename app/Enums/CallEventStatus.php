<?php

namespace App\Enums;

enum CallEventStatus: string
{
    case Started = 'started';
    case Connected = 'connected';
    case Completed = 'completed';
    case Missed = 'missed';
    case Busy = 'busy';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Started => 'Call Started',
            self::Connected => 'Call Connected',
            self::Completed => 'Completed',
            self::Missed => 'Missed Call',
            self::Busy => 'Busy',
            self::Failed => 'Failed',
        };
    }

    public function isConnectedOutcome(): bool
    {
        return in_array($this, [self::Connected, self::Completed], true);
    }

    public static function fromExotel(?string $status, ?string $eventType, ?int $conversationDuration): self
    {
        $eventType = strtolower(trim((string) $eventType));
        $status = strtolower(trim((string) $status));

        if ($eventType === 'answered') {
            return self::Connected;
        }

        return match ($status) {
            'completed' => ($conversationDuration ?? 0) > 0 ? self::Completed : self::Missed,
            'no-answer', 'no_answer' => self::Missed,
            'busy' => self::Busy,
            'failed' => self::Failed,
            'ringing', 'in-progress', 'in_progress', 'queued' => self::Started,
            default => ($conversationDuration ?? 0) > 0 ? self::Completed : self::Started,
        };
    }
}
