<?php

namespace MarbleCms\Translate\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Marble\Admin\Models\Item;
use MarbleCms\Translate\Models\TranslationJob;

class ItemTranslated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Item           $item,
        public readonly TranslationJob $job,
    ) {
    }
}
