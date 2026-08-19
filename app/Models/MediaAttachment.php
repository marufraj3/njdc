<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaAttachment extends Model
{
    use HasLocalizedContent;
    protected $guarded = [];
    public function media(): BelongsTo { return $this->belongsTo(MediaFile::class, 'media_file_id'); }
    public function attachable(): MorphTo { return $this->morphTo(); }
}
