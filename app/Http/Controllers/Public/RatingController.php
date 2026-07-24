<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Ratings\StoreRating;
use App\Http\Controllers\Controller;
use App\Support\PublicLayoutProps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function store(Request $request, StoreRating $storeRating): RedirectResponse
    {
        $data = $request->validate([
            'comment' => ['nullable', 'string', 'max:5000'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.criterion_id' => ['required', 'integer', 'distinct', 'exists:rating_criteria,id'],
            'scores.*.score' => ['required', 'integer', 'between:1,5'],
        ]);

        $scores = $data['scores'];

        $storeRating(
            hash('sha256', $request->ip().$request->userAgent().config('app.key')),
            $scores,
            $data['comment'] ?? null,
        );

        PublicLayoutProps::flushCache();

        return back()->with('success', 'Terima kasih atas penilaian Anda.');
    }
}
