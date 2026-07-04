@extends('layouts.app')

@section('title', $rule->exists ? __('Edit rule') : __('New auto-reply rule'))

@section('content')
    <form method="POST" action="{{ $rule->exists ? route('auto-rules.update', $rule) : route('auto-rules.store') }}" class="max-w-3xl space-y-6">
        @csrf
        @if($rule->exists) @method('PUT') @endif

        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h2 class="font-semibold">{{ __('Conditions') }}</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1">{{ __('Rule name') }} *</label>
                    <input name="name" value="{{ old('name', $rule->name) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Min rating') }}</label>
                    <select name="min_rating" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        @foreach(range(1, 5) as $stars)<option value="{{ $stars }}" @selected(old('min_rating', $rule->min_rating) == $stars)>{{ $stars }} ★</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Max rating') }}</label>
                    <select name="max_rating" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        @foreach(range(1, 5) as $stars)<option value="{{ $stars }}" @selected(old('max_rating', $rule->max_rating) == $stars)>{{ $stars }} ★</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Sentiment') }}</label>
                    <select name="sentiment" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">{{ __('Any') }}</option>
                        @foreach(['positive', 'neutral', 'negative'] as $sentiment)
                            <option value="{{ $sentiment }}" @selected(old('sentiment', $rule->sentiment) === $sentiment)>{{ __(ucfirst($sentiment)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Language') }}</label>
                    <select name="language" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">{{ __('Any') }}</option>
                        <option value="en" @selected(old('language', $rule->language) === 'en')>English</option>
                        <option value="ar" @selected(old('language', $rule->language) === 'ar')>العربية</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1">{{ __('Keywords (comma separated, optional)') }}</label>
                    <input name="keywords" value="{{ old('keywords', implode(', ', $rule->keywords ?? [])) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="delivery, service">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-2">{{ __('Platforms') }}</label>
                    <div class="flex flex-wrap gap-3">
                        @foreach(config('mapx.platforms') as $key => $platform)
                            @if(in_array('reviews', $platform['capabilities'], true))
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="platforms[]" value="{{ $key }}"
                                           @checked(in_array($key, old('platforms', $rule->platforms ?? []), true))
                                           class="rounded border-slate-300">
                                    {{ $platform['name'] }}
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h2 class="font-semibold">{{ __('Behaviour') }}</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Delay (minutes)') }}</label>
                    <input type="number" name="delay_minutes" min="0" max="10080" value="{{ old('delay_minutes', $rule->delay_minutes ?? 0) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Priority (higher wins)') }}</label>
                    <input type="number" name="priority" min="0" max="100" value="{{ old('priority', $rule->priority ?? 0) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="require_approval" value="0">
                    <input type="checkbox" name="require_approval" value="1" @checked(old('require_approval', $rule->require_approval ?? true)) class="rounded border-slate-300">
                    {{ __('Require approval before sending (recommended)') }}
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $rule->is_active ?? true)) class="rounded border-slate-300">
                    {{ __('Rule is active') }}
                </label>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h2 class="font-semibold">{{ __('Reply templates') }}</h2>
            <p class="text-xs text-slate-500">{{ __('One is picked at random per review. Placeholders: {name}, {rating}, {branch}') }}</p>
            @php($templates = old('templates', $rule->templates ?? ['', '', '']))
            @foreach(array_pad(array_values($templates), 3, '') as $index => $template)
                <textarea name="templates[]" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                          placeholder="{{ __('Thank you {name}! We appreciate your feedback about {branch}.') }}">{{ $template }}</textarea>
            @endforeach
        </div>

        <div class="flex items-center gap-3">
            <button class="rounded-lg bg-brand-600 text-white px-6 py-2.5 font-medium hover:bg-brand-700">
                {{ $rule->exists ? __('Save changes') : __('Create rule') }}
            </button>
            <a href="{{ route('auto-rules.index') }}" class="text-sm text-slate-500 hover:underline">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
