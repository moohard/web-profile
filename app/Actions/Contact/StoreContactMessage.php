<?php

declare(strict_types=1);

namespace App\Actions\Contact;

use App\Enums\ContactStatus;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\DB;

class StoreContactMessage
{
    /**
     * @param  array{name: string, email: string, phone?: string|null, subject?: string|null, message: string}  $data
     */
    public function __invoke(array $data): ContactMessage
    {
        return DB::transaction(fn (): ContactMessage => ContactMessage::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'status' => ContactStatus::New,
        ]));
    }
}
