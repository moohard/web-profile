<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PageMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreviewPageRequest;
use App\Services\Html\Sanitizer;
use Illuminate\Http\JsonResponse;

class PagePreviewController extends Controller
{
    public function __construct(private readonly Sanitizer $sanitizer) {}

    public function __invoke(PreviewPageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $mode = PageMode::from($data['mode']);
        $content = (string) ($data['content'] ?? '');
        $sanitized = $mode === PageMode::Code
            ? $this->sanitizer->cleanCmsPage($content)
            : $this->sanitizer->cleanRichText($content);

        return response()->json([
            'preview' => [
                'mode' => $mode->value,
                'template_key' => $data['template_key'],
                'title' => $data['title'],
                'content' => $sanitized,
            ],
        ]);
    }
}
