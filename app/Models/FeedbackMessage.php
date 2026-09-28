<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A "Contact Us" submission from the public site, shown in the admin
 * Feedback inbox. Mail delivery is best-effort; this row is the record.
 */
class FeedbackMessage extends Model
{
    protected $fillable = [
        'name',
        'email',
        'message',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
