<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AiTask;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AiConfigRequest;
use App\Models\AiConfig;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AiConfigController extends Controller
{
    /**
     * Label per-tugas untuk UI (bahasa Indonesia).
     *
     * @var array<string, string>
     */
    private const TASK_LABELS = [
        'Translation' => 'Terjemahan',
        'ContentRefinement' => 'Koreksi Konten',
        'MarkupConform' => 'Penyesuaian Markup',
    ];

    /**
     * Halaman Pengaturan → Konfigurasi AI (satu kartu per task).
     * api_key TIDAK pernah dikirim ke klien — hanya flag has_key.
     */
    public function index(): Response
    {
        $tasks = array_map(function (AiTask $task): array {
            // firstOrNew → selalu instance non-null (baru & belum tersimpan bila
            // task belum dikonfigurasi), dengan enabled default false.
            $config = AiConfig::firstOrNew(['task' => $task], ['enabled' => false]);

            return [
                'task' => $task->value,
                'label' => self::TASK_LABELS[$task->value],
                'base_url' => $config->base_url ?? '',
                'model' => $config->model ?? '',
                'system_prompt' => $config->system_prompt ?? '',
                'enabled' => $config->enabled,
                'has_key' => (string) $config->getRawOriginal('api_key') !== '',
            ];
        }, AiTask::cases());

        return Inertia::render('admin/settings/ai/index', [
            'configs' => $tasks,
        ]);
    }

    /**
     * Simpan konfigurasi satu task. api_key kosong = pertahankan yang lama.
     */
    public function update(AiConfigRequest $request, string $task): RedirectResponse
    {
        $aiTask = AiTask::tryFrom($task) ?? abort(404);

        $data = $request->validated();

        $config = AiConfig::firstOrNew(['task' => $aiTask]);
        $config->base_url = $data['base_url'] ?? null;
        $config->model = $data['model'] ?? null;
        $config->system_prompt = $data['system_prompt'] ?? null;
        $config->enabled = (bool) $data['enabled'];

        if (isset($data['api_key']) && $data['api_key'] !== '') {
            $config->api_key = $data['api_key'];
        }

        $config->save();

        return back()->with('success', 'Konfigurasi AI untuk '.($aiTask->label()).' disimpan.');
    }
}
