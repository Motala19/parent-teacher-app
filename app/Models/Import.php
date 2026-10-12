<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    protected $fillable = [
        'school_id', 'user_id', 'original_name', 'path', 'status',
        'summary', 'issues', 'new_parent_ids', 'error', 'completed_at',
    ];

    protected $casts = [
        'summary'        => 'array',
        'issues'         => 'array',
        'new_parent_ids' => 'array',
        'completed_at'   => 'datetime',
    ];

    public function statusLabel(): string
    {
        return match ($this->status) {
            'previewed' => 'Waiting for confirmation',
            'running'   => 'Importing...',
            'completed' => 'Completed',
            'failed'    => 'Failed',
            default     => ucfirst($this->status),
        };
    }
}