@extends('layouts.app')

@section('title', $rule->exists ? __('Edit rule') : __('New auto-reply rule'))

@section('content')
    <form method="POST" action="{{ $rule->exists ? route('auto-rules.update', $rule) : route('auto-rules.store') }}" class="max-w-3xl space-y-6">
        @csrf
        @if($rule->exists) @method('PUT') @endif

        <div class="card p-6 space-y-5">
            <h2 class="section-title flex items-center gap-2"><x-icon name="target" class="size-4 text-slate-400" /> {{ __('Conditions') }}</h2>
            <div class="grid sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label for="rule-name" class="label">{{ __('Rule name') }} <span class="text-red-500">*</span></label>
                    <input id="rule-name" name="name" value="{{ old('name', $rule->name) }}" required class="input">
                </div>
                <div>
                    <label for="min_rating" class="label">{{ __('Min rating') }}</label>
                    <select id="min_rating" name="min_rating" class="input">
                        @foreach(range(1, 5) as $stars)<option value="{{ $stars }}" @selected(old('min_rating', $rule->min_rating) == $stars)>{{ $stars }} ★</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="max_rating" class="label">{{ __('Max rating') }}</label>
                    <select id="max_rating" name="max_rating" class="input">
                        @foreach(range(1, 5) as $stars)<option value="{{ $stars }}" @selected(old('max_rating', $rule->max_rating) == $stars)>{{ $stars }} ★</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="sentiment" class="label">{{ __('Sentiment') }}</label>
                    <select id="sentiment" name="sentiment" class="input">
                        <option value="">{{ __('Any') }}</option>
                        @foreach(['positive', 'neutral', 'negative'] as $sentiment)
                            <option value="{{ $sentiment }}" @selected(old('sentiment', $rule->sentiment) === $sentiment)>{{ __(ucfirst($sentiment)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="language" class="label">{{ __('Language') }}</label>
                    <select id="language" name="language" class="input">
                        <option value="">{{ __('Any') }}</option>
                        <option value="en" @selected(old('language', $rule->language) === 'en')>English</option>
                        <option value="ar" @selected(old('language', $rule->language) === 'ar')>العربية</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="keywords" class="label">{{ __('Keywords (comma separated, optional)') }}</label>
                    <input id="keywords" name="keywords" value="{{ old('keywords', implode(', ', $rule->keywords ?? [])) }}" class="input" placeholder="delivery, service">
                </div>
                <div class="sm:col-span-2">
                    <span class="label">{{ __('Platforms') }}</span>
                    <div class="flex flex-wrap gap-2.5">
                        @foreach(config('mapx.platforms') as $key => $platform)
                            @if(in_array('reviews', $platform['capabilities'], true))
                                <label class="flex items-center gap-2 rounded-lg ring-1 ring-inset ring-slate-200 px-3.5 py-2.5 text-sm cursor-pointer transition-all hover:ring-slate-300 has-checked:ring-2 has-checked:ring-brand-500 has-checked:bg-brand-50/50">
                                    <input type="checkbox" name="platforms[]" value="{{ $key }}"
                                           @checked(in_array($key, old('platforms', $rule->platforms ?? []), true))
                                           class="checkbox">
                                    {{ $platform['name'] }}
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-6 space-y-5">
            <h2 class="section-title flex items-center gap-2"><x-icon name="settings" class="size-4 text-slate-400" /> {{ __('Behaviour') }}</h2>
            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label for="delay_minutes" class="label">{{ __('Delay (minutes)') }}</label>
                    <input id="delay_minutes" type="number" name="delay_minutes" min="0" max="10080" value="{{ old('delay_minutes', $rule->delay_minutes ?? 0) }}" class="input">
                </div>
                <div>
                    <label for="priority" class="label">{{ __('Priority (higher wins)') }}</label>
                    <input id="priority" type="number" name="priority" min="0" max="100" value="{{ old('priority', $rule->priority ?? 0) }}" class="input">
                </div>
                <label class="flex items-center gap-2.5 text-sm text-slate-700">
                    <input type="hidden" name="require_approval" value="0">
                    <input type="checkbox" name="require_approval" value="1" @checked(old('require_approval', $rule->require_approval ?? true)) class="checkbox">
                    {{ __('Require approval before sending (recommended)') }}
                </label>
                <label class="flex items-center gap-2.5 text-sm text-slate-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $rule->is_active ?? true)) class="checkbox">
                    {{ __('Rule is active') }}
                </label>
            </div>
        </div>

        <div class="card p-6 space-y-4">
            <h2 class="section-title flex items-center gap-2"><x-icon name="file-text" class="size-4 text-slate-400" /> {{ __('Reply templates') }}</h2>
            <p class="text-xs text-slate-400">{{ __('One is picked at random per review. Placeholders: {name}, {rating}, {branch}') }}</p>
            @php($templates = old('templates', $rule->templates ?? ['', '', '']))
            @foreach(array_pad(array_values($templates), 3, '') as $index => $template)
                <textarea name="templates[]" rows="2" class="input resize-y"
                          aria-label="{{ __('Reply templates') }} {{ $index + 1 }}"
                          placeholder="{{ __('Thank you {name}! We appreciate your feedback about {branch}.') }}">{{ $template }}</textarea>
            @endforeach
        </div>

        <div class="flex items-center gap-3">
            <button class="btn btn-primary px-6">
                <x-icon name="check" class="size-4" />
                {{ $rule->exists ? __('Save changes') : __('Create rule') }}
            </button>
            <a href="{{ route('auto-rules.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
