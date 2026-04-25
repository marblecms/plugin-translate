<?php

namespace MarbleCms\Translate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Marble\Admin\Models\Item;
use Marble\Admin\Models\Language;

class TranslationJob extends Model
{
    protected $table = 'translation_jobs';

    protected $fillable = [
        'item_id',
        'source_language_id',
        'target_language_id',
        'provider',
        'status',
        'translated_fields',
        'applied_at',
    ];

    protected $casts = [
        'translated_fields' => 'array',
        'applied_at'        => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function sourceLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'source_language_id');
    }

    public function targetLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'target_language_id');
    }
}
