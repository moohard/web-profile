<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WritingStyleRequest;
use App\Models\WritingStyle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WritingStyleController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('admin.access-system');

        return Inertia::render('admin/writing-styles/index', [
            'writingStyles' => WritingStyle::query()
                ->withExists('contentTypes')
                ->orderBy('name')
                ->get(['id', 'name', 'prompt'])
                ->map(fn (WritingStyle $writingStyle): array => [
                    'id' => $writingStyle->id,
                    'name' => $writingStyle->name,
                    'prompt' => $writingStyle->prompt,
                    'is_in_use' => (bool) $writingStyle->content_types_exists,
                ]),
        ]);
    }

    public function store(WritingStyleRequest $request): RedirectResponse
    {
        WritingStyle::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gaya bahasa berhasil dibuat.']);

        return to_route('admin.writing-styles.index');
    }

    public function update(
        WritingStyleRequest $request,
        WritingStyle $writingStyle,
    ): RedirectResponse {
        $writingStyle->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gaya bahasa berhasil diperbarui.']);

        return to_route('admin.writing-styles.index');
    }

    public function destroy(WritingStyle $writingStyle): RedirectResponse
    {
        Gate::authorize('admin.access-system');

        DB::transaction(function () use ($writingStyle): void {
            $lockedWritingStyle = WritingStyle::query()
                ->lockForUpdate()
                ->findOrFail($writingStyle->id);

            if ($lockedWritingStyle->contentTypes()->exists()) {
                throw ValidationException::withMessages([
                    'writing_style' => 'Gaya bahasa tidak dapat dihapus karena sedang dipakai jenis konten.',
                ]);
            }

            $lockedWritingStyle->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gaya bahasa berhasil dihapus.']);

        return to_route('admin.writing-styles.index');
    }
}
