<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ContactStatus;
use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Language;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostTranslation;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DashboardController extends Controller
{
    /**
     * Ringkasan admin: statistik, draft terbaru, dan pesan kontak baru.
     */
    public function index(): Response
    {
        $langId = Language::current()->id;

        $stats = [
            'posts' => Post::count(),
            'pages' => Page::count(),
            'media' => Media::count(),
            'contactNew' => ContactMessage::where('status', ContactStatus::New)->count(),
        ];

        $draftPosts = PostTranslation::where('language_id', $langId)
            ->where('status', PostStatus::Draft)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'title', 'updated_at']);

        $newContactMessages = ContactMessage::where('status', ContactStatus::New)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'name', 'subject', 'created_at']);

        return Inertia::render('admin/dashboard', compact('stats', 'draftPosts', 'newContactMessages'));
    }
}
