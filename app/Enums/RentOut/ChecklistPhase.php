<?php

namespace App\Enums\RentOut;

enum ChecklistPhase: string
{
    case MoveIn = 'move_in';
    case MoveOut = 'move_out';

    public function label(): string
    {
        return match ($this) {
            self::MoveIn => 'Move-In',
            self::MoveOut => 'Move-Out',
        };
    }

    /** The checklist-line column holding this phase's status. */
    public function statusColumn(): string
    {
        return $this->value.'_status';
    }

    /** The checklist-line column holding this phase's comment. */
    public function commentColumn(): string
    {
        return $this->value.'_comment';
    }

    /** The checklist-line column holding this phase's photo (`image_path` predates the split). */
    public function imageColumn(): string
    {
        return $this === self::MoveIn ? 'image_path' : 'move_out_image_path';
    }

    /** The rent-out column recording when this hand-over actually happened. */
    public function actualDateColumn(): string
    {
        return 'actual_'.$this->value.'_date';
    }

    /** The rent-out column holding this hand-over's general remarks. */
    public function remarksColumn(): string
    {
        return $this->value.'_remarks';
    }
}
