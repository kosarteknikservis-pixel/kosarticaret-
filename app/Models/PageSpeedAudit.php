<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageSpeedAudit extends Model
{
    protected $fillable = [
        'page_key',
        'label',
        'url',
        'strategy',
        'performance_score',
        'fcp_ms',
        'lcp_ms',
        'cls',
        'tbt_ms',
        'speed_index_ms',
        'field_lcp_p75_ms',
        'field_cls_p75',
        'field_inp_p75_ms',
        'field_overall_category',
        'opportunities',
        'error_message',
        'measured_at',
    ];

    protected function casts(): array
    {
        return [
            'cls' => 'float',
            'field_cls_p75' => 'float',
            'opportunities' => 'array',
            'measured_at' => 'datetime',
        ];
    }

    public function isMobile(): bool
    {
        return $this->strategy === 'mobile';
    }

    public function scoreTone(): string
    {
        $score = $this->performance_score;
        if ($score === null) {
            return 'neutral';
        }
        if ($score >= 90) {
            return 'good';
        }
        if ($score >= 50) {
            return 'average';
        }

        return 'poor';
    }

    public function fieldTone(): string
    {
        return match ($this->field_overall_category) {
            'FAST' => 'good',
            'AVERAGE' => 'average',
            'SLOW' => 'poor',
            default => 'neutral',
        };
    }
}
