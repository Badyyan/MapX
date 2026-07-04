@extends('layouts.app')

@section('title', __('New post'))

@section('content')
    <form method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data" class="max-w-3xl space-y-6" id="post-form">
        @csrf

        <div class="card p-6 space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="section-title flex items-center gap-2"><x-icon name="file-text" class="size-4 text-slate-400" /> {{ __('Content') }}</h2>
                <div class="flex items-center gap-2">
                    <input id="ai-topic" placeholder="{{ __('Topic, e.g. Ramadan offer') }}" class="input !w-56 !py-2" aria-label="{{ __('Topic, e.g. Ramadan offer') }}">
                    <button type="button" onclick="generateDraft()" class="btn btn-ai btn-sm !py-2">
                        <x-icon name="sparkles" class="size-3.5" /> {{ __('AI draft') }}
                    </button>
                </div>
            </div>
            <div>
                <label for="title" class="label">{{ __('Title (optional)') }}</label>
                <input id="title" name="title" value="{{ old('title') }}" class="input">
            </div>
            <div>
                <label for="post-content" class="label">{{ __('Post text') }} <span class="text-red-500">*</span></label>
                <textarea name="content" id="post-content" rows="6" required class="input resize-y">{{ old('content') }}</textarea>
            </div>
            <input type="hidden" name="ai_generated" id="ai-generated" value="0">
            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="label">{{ __('Image (required for Instagram)') }}</label>
                    <input type="file" name="image" accept="image/*" class="text-sm text-slate-500">
                </div>
                <div>
                    <label for="cta_url" class="label">{{ __('Call-to-action URL (optional)') }}</label>
                    <input id="cta_url" name="cta_url" value="{{ old('cta_url') }}" placeholder="https://…" class="input" dir="ltr">
                </div>
            </div>
        </div>

        <div class="card p-6 space-y-5">
            <h2 class="section-title flex items-center gap-2"><x-icon name="send" class="size-4 text-slate-400" /> {{ __('Destinations') }}</h2>
            <div>
                <span class="label">{{ __('Platforms') }} <span class="text-red-500">*</span></span>
                <div class="flex flex-wrap gap-2.5">
                    @foreach($platforms as $key => $platform)
                        <label class="flex items-center gap-2 rounded-lg ring-1 ring-inset ring-slate-200 px-3.5 py-2.5 text-sm cursor-pointer transition-all hover:ring-slate-300 has-checked:ring-2 has-checked:ring-brand-500 has-checked:bg-brand-50/50">
                            <input type="checkbox" name="platforms[]" value="{{ $key }}" @checked(in_array($key, old('platforms', []), true)) class="checkbox">
                            {{ $platform['name'] }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <span class="label">{{ __('Branches') }} <span class="text-red-500">*</span></span>
                <div class="flex flex-wrap gap-2.5 max-h-44 overflow-y-auto">
                    @foreach($branches as $branch)
                        <label class="flex items-center gap-2 rounded-lg ring-1 ring-inset ring-slate-200 px-3.5 py-2.5 text-sm cursor-pointer transition-all hover:ring-slate-300 has-checked:ring-2 has-checked:ring-brand-500 has-checked:bg-brand-50/50">
                            <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}" @checked(in_array($branch->id, old('branch_ids', []), false)) class="checkbox">
                            {{ $branch->name }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card p-6 space-y-5">
            <h2 class="section-title flex items-center gap-2"><x-icon name="clock" class="size-4 text-slate-400" /> {{ __('Timing') }}</h2>
            <div class="flex flex-wrap items-center gap-5">
                <label class="flex items-center gap-2 text-sm text-slate-700"><input type="radio" name="action" value="publish" checked class="checkbox !rounded-full"> {{ __('Publish now') }}</label>
                <label class="flex items-center gap-2 text-sm text-slate-700"><input type="radio" name="action" value="schedule" class="checkbox !rounded-full"> {{ __('Schedule') }}</label>
                <label class="flex items-center gap-2 text-sm text-slate-700"><input type="radio" name="action" value="draft" class="checkbox !rounded-full"> {{ __('Save as draft') }}</label>
                <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" class="input !w-auto !py-2" aria-label="{{ __('Schedule') }}">
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button class="btn btn-primary px-6"><x-icon name="check" class="size-4" /> {{ __('Save post') }}</button>
            <a href="{{ route('posts.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
        </div>
    </form>

    @push('scripts')
    <script>
        async function generateDraft() {
            const topic = document.getElementById('ai-topic').value.trim();
            if (! topic) { alert('{{ __('Enter a topic first.') }}'); return; }
            const response = await fetch('{{ route('posts.draft') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ topic }),
            });
            const data = await response.json();
            document.getElementById('post-content').value = data.draft;
            document.getElementById('ai-generated').value = '1';
        }
    </script>
    @endpush
@endsection
