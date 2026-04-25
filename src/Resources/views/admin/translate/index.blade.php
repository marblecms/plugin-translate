@extends('marble::layouts.app')

@section('sidebar')
<div class="main-box clearfix profile-box-menu">
    <div class="main-box-body clearfix">
        <div class="profile-box-header gray-bg clearfix">
            <h2>Translate</h2>
        </div>
        <div class="profile-box-content clearfix">
            <ul class="menu-items">
                <li class="active">
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
<h1>@include('marble::components.famicon', ['name' => 'script_go']) Translate</h1>

@if(!empty($message))
<div class="alert alert-info">{{ $message }}</div>
@endif

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="main-box">
    <header class="main-box-header clearfix">
        <h2 class="pull-left">Published Items with Translatable Content</h2>
        <div class="clearfix"></div>
    </header>
    <div class="main-box-body clearfix">
        @if($items->isEmpty())
            <p class="text-muted text-center marble-mt-sm marble-mb-sm">
                No items with translatable content found.
            </p>
        @else
        <table class="table table-hover marble-table-flush">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Blueprint</th>
                    @foreach($otherLangs as $lang)
                    <th class="text-center marble-col-sm">{{ strtoupper($lang->code ?? $lang->locale) }}</th>
                    @endforeach
                    <th class="marble-col-xs"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->name() }}</strong>
                        @if($item->path)
                        <br><small class="text-muted">{{ $item->path }}</small>
                        @endif
                    </td>
                    <td class="text-muted marble-text-sm">{{ $item->blueprint?->name ?? '—' }}</td>
                    @foreach($otherLangs as $lang)
                    @php
                        $hasTranslation = $item->itemValues
                            ->where('language_id', $lang->id)
                            ->filter(fn($v) => $v->blueprintField?->translatable && !empty($v->value))
                            ->isNotEmpty();
                    @endphp
                    <td class="text-center">
                        @if($hasTranslation)
                            <span class="label label-success">
                                @include('marble::components.famicon', ['name' => 'tick'])
                            </span>
                        @else
                            <span class="label label-warning">Missing</span>
                        @endif
                    </td>
                    @endforeach
                    <td>
                        <a href="{{ route('marble.translate.show', $item) }}" class="btn btn-info btn-xs">
                            @include('marble::components.famicon', ['name' => 'script_go'])
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="marble-mt-sm">
            {{ $items->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
