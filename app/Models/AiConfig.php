<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiTask;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property AiTask $task
 * @property ?string $base_url
 * @property ?string $api_key
 * @property ?string $model
 * @property ?string $system_prompt
 * @property bool $enabled
 */
class AiConfig extends Model
{
    protected $fillable = [
        'task',
        'base_url',
        'api_key',
        'model',
        'system_prompt',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'task' => AiTask::class,
            'api_key' => 'encrypted',
            'enabled' => 'boolean',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeFor(Builder $query, AiTask $task): Builder
    {
        return $query->where('task', $task->value);
    }

    public static function resolve(AiTask $task): ?self
    {
        return static::query()->for($task)->where('enabled', true)->first();
    }
}
