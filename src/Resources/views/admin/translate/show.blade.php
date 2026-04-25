@php use \Illuminate\Support\Str; @endphp
@extends('marble::layouts.app')

@section('sidebar')
<div class="main-box clearfix profile-box-menu">
    <div class="main-box-body clearfix">
        <div class="profile-box-header gray-bg clearfix">
            <h2>Translate</h2>
        </div>
        <div class="profile-box-content clearfix">
            <ul class="menu-items">
                <li>
                    <a href="{{ route('marble.translate.index') }}" class="clearfix">
                        @include('marble::components.famicon', ['name' => 'script_go']) All Items
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection

@section('content')
<h1>
    @include('marble::components.famicon', ['name' => 'script_go'])
    Translate: {{ $item->name() }}
</h1>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

{{-- Trigger translation form --}}
<div class="main-box">
    <header class="main-box-header clearfix">
        <h2>Request Translation</h2>
    </header>
    <div class="main-box-body clearfix">
        <form method="POST" action="{{ route('marble.translate.translate', $item) }}" class="form-inline">
            @csrf
            <div class="form-group">
                <label class="marble-mr-xs">From</label>
                <select name="source_language_id" class="form-control marble-mr-xs">
                    @foreach($languages as $lang)
                    <option value="{{ $lang->id }}"
                        {{ $lang->id === $primaryLang->id ? 'selected' : '' }}>
                        {{ $lang->name ?? strtoupper($lang->code ?? $lang->locale) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="marble-mr-xs">To</label>
                <select name="target_language_id" class="form-control marble-mr-xs">
                    @foreach($otherLangs as $lang)
                    <option value="{{ $lang->id }}">
                        {{ $lang->name ?? strtoupper($lang->code ?? $lang->locale) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="marble-mr-xs">Provider</label>
                <select name="provider" class="form-control marble-mr-xs">
                    <option value="deepl" {{ config('translate.provider') === 'deepl' ? 'selected' : '' }}>DeepL</option>
                    <option value="google" {{ config('translate.provider') === 'google' ? 'selected' : '' }}>Google Translate</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                @include('marble::components.famicon', ['name' => 'script_go']) Translate
            </button>
        </form>
    </div>
</div>

{{-- Pending job review --}}
@if($pendingJob)
<div class="main-box">
    <header class="main-box-header clearfix">
        <h2 class="pull-left">
            @include('marble::components.famicon', ['name' => 'time'])
            Pending Translation
            <small class="text-muted">
                {{ $pendingJob->sourceLanguage->name ?? $pendingJob->source_language_id }}
                →
                {{ $pendingJob->targetLanguage->name ?? $pendingJob->target_language_id }}
                ({{ $pendingJob->provider }})
            </small>
        </h2>
        <div class="pull-right">
            <form method="POST" action="{{ route('marble.translate.apply', $pendingJob) }}" class="pull-left">
                @csrf
                <button type="submit" class="btn btn-success btn-sm marble-mr-xs">
                    @include('marble::components.famicon', ['name' => 'tick']) Apply All
                </button>
            </form>
            <form method="POST" action="{{ route('marble.translate.reject', $pendingJob) }}" class="pull-left"
                  onsubmit="return confirm('Reject and discard this translation?')">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm">
                    @include('marble::components.famicon', ['name' => 'delete']) Reject
                </button>
            </form>
        </div>
        <div class="clearfix"></div>
    </header>
    <div class="main-box-body clearfix">
        <table class="table marble-table-flush">
            <thead>
                <tr>
                    <th class="marble-col-sm">Field</th>
                    <th>
                        Source
                        ({{ $pendingJob->sourceLanguage->name ?? $pendingJob->source_language_id }})
                    </th>
                    <th>
                        Translation
                        ({{ $pendingJob->targetLanguage->name ?? $pendingJob->target_language_id }})
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingJob->translated_fields ?? [] as $identifier => $translatedValue)
                @php
                    $sourceKey   = $pendingJob->source_language_id . '_' . $identifier;
                    $sourceValue = $allValues->get($sourceKey)?->first()?->value ?? '—';
                @endphp
                <tr>
                    <td><code class="marble-text-sm">{{ $identifier }}</code></td>
                    <td class="text-muted marble-text-sm">{{ Str::limit(strip_tags($sourceValue), 200) }}</td>
                    <td>{{ Str::limit(strip_tags($translatedValue), 200) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Current values comparison --}}
<div class="main-box">
    <header class="main-box-header clearfix">
        <h2>Current Field Values</h2>
    </header>
    <div class="main-box-body clearfix">
        @if($primaryValues->isEmpty())
            <p class="text-muted text-center marble-mt-sm marble-mb-sm">
                No translatable field values found in the primary language.
            </p>
        @else
        <table class="table marble-table-flush">
            <thead>
                <tr>
                    <th class="marble-col-sm">Field</th>
                    <th>
                        {{ $primaryLang->name ?? strtoupper($primaryLang->code ?? $primaryLang->locale) }}
                        (Source)
                    </th>
                    @foreach($otherLangs as $lang)
                    <th>{{ $lang->name ?? strtoupper($lang->code ?? $lang->locale) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($primaryValues as $identifier => $primaryValue)
                <tr>
                    <td><code class="marble-text-sm">{{ $identifier }}</code></td>
                    <td class="marble-text-sm">
                        {{ Str::limit(strip_tags($primaryValue->value ?? ''), 150) }}
                    </td>
                    @foreach($otherLangs as $lang)
                    @php
                        $key       = $lang->id . '_' . $identifier;
                        $langValue = $allValues->get($key)?->first()?->value;
                    @endphp
                    <td class="marble-text-sm {{ $langValue ? '' : 'text-muted' }}">
                        @if($langValue)
                            {{ Str::limit(strip_tags($langValue), 150) }}
                        @else
                            <em>—</em>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
