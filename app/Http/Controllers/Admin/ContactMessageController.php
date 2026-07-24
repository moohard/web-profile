<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ContactStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ContactMessageController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', ContactMessage::class);

        return Inertia::render('admin/contact-messages/index', [
            'messages' => ContactMessage::query()
                ->latest()
                ->get()
                ->map(fn (ContactMessage $message): array => [
                    'id' => $message->id,
                    'name' => $message->name,
                    'email' => $message->email,
                    'phone' => $message->phone,
                    'subject' => $message->subject,
                    'message' => $message->message,
                    'status' => $message->status->value,
                    'created_at' => $message->created_at?->toIso8601String(),
                ])
                ->all(),
            'statuses' => array_map(
                fn (ContactStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                ContactStatus::cases(),
            ),
        ]);
    }

    public function show(ContactMessage $contactMessage): Response
    {
        $this->authorize('view', $contactMessage);

        return Inertia::render('admin/contact-messages/show', [
            'message' => $contactMessage,
        ]);
    }

    public function update(Request $request, ContactMessage $contactMessage): RedirectResponse
    {
        $this->authorize('update', $contactMessage);

        $data = $request->validate([
            'status' => ['required', Rule::enum(ContactStatus::class)],
        ]);
        $contactMessage->update(['status' => $data['status']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Status pesan berhasil diperbarui.']);

        return back();
    }

    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        $this->authorize('delete', $contactMessage);
        $contactMessage->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pesan berhasil dihapus.']);

        return back();
    }
}
