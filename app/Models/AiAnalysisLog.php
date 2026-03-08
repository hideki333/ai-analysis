<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAnalysisLog extends Model
{
    protected $table = 'ai_analysis_log';
    public $timestamps = false;

    protected $fillable = [
        'image_path',
        'success',
        'message',
        'class',
        'confidence',
        'request_timestamp',
        'response_timestamp',
    ];

    protected $casts = [
        'success'            => 'boolean',
        'class'              => 'integer',
        'confidence'         => 'decimal:4',
        'request_timestamp'  => 'datetime:Y-m-d H:i:s.u',
        'response_timestamp' => 'datetime:Y-m-d H:i:s.u',
    ];
}
