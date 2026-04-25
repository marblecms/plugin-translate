<?php

namespace MarbleCms\Translate\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Marble\Admin\Facades\Marble;
use Marble\Admin\Models\Item;
use Marble\Admin\Models\ItemValue;
use Marble\Admin\Models\Language;
use MarbleCms\Translate\Events\ItemTranslated;
use MarbleCms\Translate\Models\TranslationJob;
use MarbleCms\Translate\Services\TranslationService;

class TranslateController extends Controller
{
    public function index()
    {
        $languages   = Language::orderBy('id')->get();
        $primaryLang = $languages->first();
        $otherLangs  = $languages->slice(1);

        if (!$primaryLang || $otherLangs->isEmpty()) {
            return view('translate::admin.translate.index', [
                'items'       => collect(),
                'primaryLang' => $primaryLang,
                'otherLangs'  => $otherLangs,
                'message'     => 'At least two languages are required to use the translation tool.',
            ]);
        }

        // Find items that have values in the primary language but are missing values
        // in at least one secondary language for translatable fields
        $items = Item::with(['blueprint', 'itemValues.blueprintField'])
            ->where('status', 'published')
            ->whereHas('itemValues', function ($q) use ($primaryLang) {
                $q->where('language_id', $primaryLang->id)
                  ->whereHas('blueprintField', fn($q2) => $q2->where('translatable', true));
            })
            ->latest()
            ->paginate(40);

        return view('translate::admin.translate.index', compact('items', 'primaryLang', 'otherLangs'));
    }

    public function show(Item $item)
    {
        $languages   = Language::orderBy('id')->get();
        $primaryLang = $languages->first();
        $otherLangs  = $languages->slice(1);

        // Load translatable fields for each language
        $allValues = ItemValue::with('blueprintField')
            ->where('item_id', $item->id)
            ->whereHas('blueprintField', fn($q) => $q->where('translatable', true))
            ->get()
            ->groupBy(fn($v) => $v->language_id . '_' . $v->blueprintField->identifier);

        // Pending translation job for this item
        $pendingJob = TranslationJob::where('item_id', $item->id)
            ->where('status', 'pending')
            ->with(['sourceLanguage', 'targetLanguage'])
            ->latest()
            ->first();

        // Get all translatable field identifiers from the primary language
        $primaryValues = ItemValue::with('blueprintField')
            ->where('item_id', $item->id)
            ->where('language_id', $primaryLang->id)
            ->whereHas('blueprintField', fn($q) => $q->where('translatable', true))
            ->get()
            ->keyBy(fn($v) => $v->blueprintField->identifier);

        return view('translate::admin.translate.show', compact(
            'item', 'languages', 'primaryLang', 'otherLangs',
            'primaryValues', 'allValues', 'pendingJob'
        ));
    }

    public function translate(Request $request, Item $item, TranslationService $service)
    {
        $request->validate([
            'source_language_id' => 'required|exists:languages,id',
            'target_language_id' => 'required|exists:languages,id|different:source_language_id',
            'provider'           => 'nullable|in:deepl,google',
        ]);

        $provider = $request->input('provider', config('translate.provider', 'deepl'));

        try {
            $translated = $service->translate(
                $item,
                (int) $request->source_language_id,
                (int) $request->target_language_id,
                $provider,
            );
        } catch (\RuntimeException $e) {
            return redirect()->route('marble.translate.show', $item)
                ->with('error', 'Translation failed: ' . $e->getMessage());
        }

        // Store as a pending job for review
        TranslationJob::create([
            'item_id'            => $item->id,
            'source_language_id' => $request->source_language_id,
            'target_language_id' => $request->target_language_id,
            'provider'           => $provider,
            'status'             => 'pending',
            'translated_fields'  => $translated,
        ]);

        return redirect()->route('marble.translate.show', $item)
            ->with('success', 'Translation complete. Review and apply below.');
    }

    public function apply(TranslationJob $job)
    {
        $item      = $job->item;
        $langId    = $job->target_language_id;
        $fields    = $job->translated_fields ?? [];

        foreach ($fields as $identifier => $value) {
            // Find the blueprint field
            $blueprintField = \Marble\Admin\Models\BlueprintField::where('identifier', $identifier)
                ->whereHas('blueprint', fn($q) => $q->where('id', $item->blueprint_id))
                ->first();

            if (!$blueprintField) {
                continue;
            }

            ItemValue::updateOrCreate(
                [
                    'item_id'            => $item->id,
                    'blueprint_field_id' => $blueprintField->id,
                    'language_id'        => $langId,
                ],
                ['value' => $value]
            );
        }

        $job->update(['status' => 'applied', 'applied_at' => now()]);

        ItemTranslated::dispatch($item, $job);

        return redirect()->route('marble.translate.show', $item)
            ->with('success', 'Translation applied successfully.');
    }

    public function reject(TranslationJob $job)
    {
        $job->update(['status' => 'rejected']);

        return redirect()->route('marble.translate.show', $job->item)
            ->with('success', 'Translation job rejected.');
    }
}
