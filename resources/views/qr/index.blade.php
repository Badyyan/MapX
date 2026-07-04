@extends('layouts.app')

@section('title', __('QR review campaigns'))

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            @forelse($campaigns as $campaign)
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold">{{ $campaign->name }}</span>
                                <span class="rounded-full px-2 py-0.5 text-xs {{ $campaign->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $campaign->is_active ? __('Active') : __('Paused') }}
                                </span>
                            </div>
                            <div class="text-xs text-slate-500 mt-1">
                                {{ $campaign->branch?->name }} · {{ __('Scale') }} 1–{{ $campaign->rating_scale }}
                                · {{ __('Positive if') }} ≥ {{ $campaign->threshold }}
                            </div>
                            <div class="text-xs mt-1">
                                <a class="text-brand-600 hover:underline" href="{{ $campaign->publicUrl() }}" target="_blank" rel="noopener">{{ $campaign->publicUrl() }}</a>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 text-center text-sm">
                            <div><div class="font-bold">{{ $campaign->scans_count }}</div><div class="text-xs text-slate-500">{{ __('Scans') }}</div></div>
                            <div><div class="font-bold text-emerald-600">{{ $campaign->positive_count }}</div><div class="text-xs text-slate-500">{{ __('Positive') }}</div></div>
                            <div><div class="font-bold text-red-500">{{ $campaign->negative_count }}</div><div class="text-xs text-slate-500">{{ __('Internal') }}</div></div>
                        </div>
                    </div>
                    @if(auth()->user()->hasPermission('qr.manage'))
                        <div class="mt-3 flex gap-2">
                            <a href="{{ route('qr.svg', $campaign) }}" class="rounded-lg bg-brand-50 text-brand-700 border border-brand-200 px-3 py-1.5 text-xs hover:bg-brand-100">⬇ {{ __('Download QR (SVG)') }}</a>
                            <form method="POST" action="{{ route('qr.toggle', $campaign) }}">
                                @csrf
                                <button class="rounded-lg bg-white border border-slate-300 px-3 py-1.5 text-xs hover:bg-slate-50">{{ $campaign->is_active ? __('Pause') : __('Activate') }}</button>
                            </form>
                            <form method="POST" action="{{ route('qr.destroy', $campaign) }}" onsubmit="return confirm('{{ __('Delete this campaign?') }}')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg bg-white border border-red-200 text-red-600 px-3 py-1.5 text-xs hover:bg-red-50">{{ __('Delete') }}</button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-500">
                    {{ __('No QR campaigns yet. Create one and place the QR code at your point of sale.') }}
                </div>
            @endforelse

            {{-- Internal feedback list --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h2 class="font-semibold mb-4">{{ __('Internal feedback (not published)') }}</h2>
                <div class="space-y-3">
                    @forelse($feedback as $item)
                        <div class="rounded-xl border border-slate-200 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="text-sm">
                                    <span class="font-medium">{{ $item->customer_name ?: __('Anonymous') }}</span>
                                    <span class="text-red-500 font-semibold ms-2">{{ $item->rating }}/{{ $item->campaign?->rating_scale }}</span>
                                    <span class="text-xs text-slate-500 ms-2">{{ $item->branch?->name }} · {{ $item->created_at->diffForHumans() }}</span>
                                </div>
                                @if(auth()->user()->hasPermission('qr.manage'))
                                    <form method="POST" action="{{ route('qr.feedback.status', $item) }}">
                                        @csrf @method('PUT')
                                        <select name="status" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-2 py-1 text-xs">
                                            <option value="new" @selected($item->status === 'new')>{{ __('New') }}</option>
                                            <option value="in_progress" @selected($item->status === 'in_progress')>{{ __('In progress') }}</option>
                                            <option value="resolved" @selected($item->status === 'resolved')>{{ __('Resolved') }}</option>
                                        </select>
                                    </form>
                                @endif
                            </div>
                            @if($item->comment)<p class="mt-2 text-sm text-slate-600" dir="auto">{{ $item->comment }}</p>@endif
                            <div class="mt-2 text-xs text-slate-500">
                                @if($item->customer_phone)📞 {{ $item->customer_phone }}@endif
                                @if($item->customer_email) · ✉️ {{ $item->customer_email }}@endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No internal feedback captured yet.') }}</p>
                    @endforelse
                </div>
                <div class="mt-3">{{ $feedback->links() }}</div>
            </div>
        </div>

        {{-- Create campaign --}}
        @if(auth()->user()->hasPermission('qr.manage'))
            <div class="bg-white rounded-2xl border border-slate-200 p-6 h-fit">
                <h2 class="font-semibold mb-4">{{ __('New QR campaign') }}</h2>
                <form method="POST" action="{{ route('qr.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('Name') }} *</label>
                        <input name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="{{ __('Table stickers — main branch') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('Branch') }} *</label>
                        <select name="branch_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ __('Rating scale') }}</label>
                            <select name="rating_scale" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="5">1–5</option>
                                <option value="10">1–10</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ __('Positive threshold') }}</label>
                            <input type="number" name="threshold" value="{{ old('threshold', 4) }}" min="1" max="10" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('Positive redirect URL (optional)') }}</label>
                        <input name="positive_redirect_url" value="{{ old('positive_redirect_url') }}" placeholder="{{ __('Defaults to the branch Google review page') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <button class="w-full rounded-lg bg-brand-600 text-white py-2.5 font-medium hover:bg-brand-700">{{ __('Create campaign') }}</button>
                </form>
            </div>
        @endif
    </div>
@endsection
