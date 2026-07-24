<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Contact\StoreContactMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\ContactRequest;
use App\Mail\ContactMessageMail;
use App\Settings\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function store(ContactRequest $request, StoreContactMessage $storeContactMessage): RedirectResponse
    {
        $validated = $request->validated();
        $message = $storeContactMessage([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'] ?? null,
            'message' => $validated['message'],
        ]);
        $recipient = app(SiteSettings::class)->contact_notification_email ?? config('mail.from.address');

        Mail::to($recipient)->queue(new ContactMessageMail($message));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pesan Anda berhasil dikirim.']);

        return back();
    }
}
