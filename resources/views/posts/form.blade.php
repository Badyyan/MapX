@extends('layouts.app')

@section('title', __('New post'))

@section('content')
    <form method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data" class="max-w-3xl space-y-6" id="post-form">
        @csrf

        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">{{ __('Content') }}</h2>
                <div class="flex items-center gap-2">
                    <input id="ai-topic" placeholder="{{ __('Topic, e.g. Ramadan offer') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm w-56">
                    <button type="button" onclick="generateDraft()" class="rounded-lg bg-violet-50 text-violet-700 border border-violet-200 px-3 py-1.5 text-sm hover:bg-violet-100">✨ {{ __('AI draft') }}</button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Title (optional)') }}</label>
                <input name="title" value="{{ old('title') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Post text') }} *</label>
                <textarea name="content" id="post-content" rows="6" required class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('content') }}</textarea>
            </div>
            <input type="hidden" name="ai_generated" id="ai-generated" value="0">
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Image (required for Instagram)') }}</label>
                    <input type="file" name="image" accept="image/*" class="text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('Call-to-action URL (optional)') }}</label>
                    <input name="cta_url" value="{{ old('cta_url') }}" placeholder="https://…" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h2 class="font-semibold">{{ __('Destinations') }}</h2>
            <div>
                <label class="block text-sm font-medium mb-2">{{ __('Platforms') }} *</label>
                <div class="flex flex-wrap gap-3">
                    @foreach($platforms as $key => $platform)
                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm cursor-pointer has-checked:border-brand-500 has-checked:bg-brand-50">
                            <input type="checkbox" name="platforms[]" value="{{ $key }}" @checked(in_array($key, old('platforms', []), true)) class="rounded border-slate-300">
                            {{ $platform['name'] }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">{{ __('Branches') }} *</label>
                <div class="flex flex-wrap gap-3 max-h-40 overflow-y-auto">
                    @foreach($branches as $branch)
                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm cursor-pointer has-checked:border-brand-500 has-checked:bg-brand-50">
                            <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}" @checked(in_array($branch->id, old('branch_ids', []), false)) class="rounded border-slate-300">
                            {{ $branch->name }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h2 class="font-semibold">{{ __('Timing') }}</h2>
            <div class="flex flex-wrap items-center gap-4">
                <label class="flex items-center gap-2 text-sm"><input type="radio" name="action" value="publish" checked> {{ __('Publish now') }}</label>
                <label class="flex items-center gap-2 text-sm"><input type="radio" name="action" value="schedule"> {{ __('Schedule') }}</label>
                <label class="flex items-center gap-2 text-sm"><input type="radio" name="action" value="draft"> {{ __('Save as draft') }}</label>
                <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button class="rounded-lg bg-brand-600 text-white px-6 py-2.5 font-medium hover:bg-brand-700">{{ __('Save post') }}</button>
            <a href="{{ route('posts.index') }}" class="text-sm text-slate-500 hover:underline">{{ __('Cancel') }}</a>
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
