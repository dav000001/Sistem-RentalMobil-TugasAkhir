<?php

namespace App\Enums;

enum VendorStatus: string
{
    case Pending = 'pending';
    case NeedsRevision = 'needs_revision';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Menunggu Review',
            self::NeedsRevision => 'Perlu Revisi',
            self::Approved => 'Aktif',
            self::Rejected => 'Ditolak',
            self::Suspended => 'Dibekukan',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending => 'yellow',
            self::NeedsRevision => 'orange',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Suspended => 'gray',
        };
    }

    public function isOperational(): bool
    {
        return $this === self::Approved;
    }

    public function transitionsTo(): array
    {
        return match($this) {
            self::Pending => [self::Approved, self::Rejected, self::NeedsRevision],
            self::NeedsRevision => [self::Approved, self::Rejected, self::Pending],
            self::Approved => [self::Suspended],
            self::Rejected => [self::Pending],
            self::Suspended => [self::Approved],
        };
    }
}
