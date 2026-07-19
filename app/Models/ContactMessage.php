<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactStatus;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property ?string $phone
 * @property ?string $subject
 * @property string $message
 * @property ContactStatus $status
 */
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContactStatus::class,
        ];
    }
}
