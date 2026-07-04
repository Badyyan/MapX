@extends('layouts.app')

@section('title', __('QR review campaigns'))

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            @forelse($campaigns as $campaign)
                <div class="card card-hover p-5">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-start gap-3.5 min-w-0">
                            <span class="grid place-items-center size-10 shrink-0 rounded-xl {{ $campaign->is_active ? 'bg-brand-50 text-brand-600' : 'bg-slate-100 text-slate-400' }}">
                                <x-icon name="qr-code" class="size-5" />
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-slate-900">{{ $campaign->name }}</span>
                                    <span class="{{ $campaign->is_active ? 'badge badge-success' : 'badge badge-neutral' }}">{{ $campaign->is_active ? __('Active') : __('Paused') }}</span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">
                                    {{ $campaign->branch?->name }} · {{ __('Scale') }} 1–{{ $campaign->rating_scale }}
                                    · {{ __('Positive if') }} ≥ {{ $campaign->threshold }}
                                </p>
                                <a class="mt-1 inline-flex items-center gap-1 text-xs text-brand-600 hover:underline" href="{{ $campaign->publicUrl() }}" target="_blank" rel="noopener">
                                    <x-icon name="link" class="size-3" /> {{ $campaign->publicUrl() }}
                                </a>
                            </div>
                        </div>
                        <div class="flex items-center gap-5 text-center text-sm">
                            <div><p class="font-bold text-slate-900 tabular-nums">{{ $campaign->scans_count }}</p><p class="text-xs text-slate-400">{{ __('Scans') }}</p></div>
                            <div><p class="font-bold text-emerald-600 tabular-nums">{{ $campaign->positive_count }}</p><p class="text-xs text-slate-400">{{ __('Positive') }}</p></div>
                            <div><p class="font-bold text-rose-500 tabular-nums">{{ $campaign->negative_count }}</p><p class="text-xs text-slate-400">{{ __('Internal') }}</p></div>
                        </div>
                    </div>
                    @if(auth()->user()->hasPermission('qr.manage'))
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('qr.svg', $campaign) }}" class="btn btn-secondary btn-sm !text-brand-700"><x-icon name="download" class="size-3" /> {{ __('Download QR (SVG)') }}</a>
                            <form method="POST" action="{{ route('qr.toggle', $campaign) }}">
                                @csrf
                                <button class="btn btn-secondary btn-sm">{{ $campaign->is_active ? __('Pause') : __('Activate') }}</button>
                            </form>
                            <form method="POST" action="{{ route('qr.destroy', $campaign) }}" onsubmit="return confirm('{{ __('Delete this campaign?') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm"><x-icon name="trash" class="size-3" /> {{ __('Delete') }}</button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="card border-2 border-dashed !ring-0 !shadow-none border-slate-200 p-14 text-center">
                    <x-icon name="qr-code" class="size-10 text-slate-300 mx-auto mb-3" />
                    <p class="text-sm text-slate-500">{{ __('No QR campaigns yet. Create one and place the QR code at your point of sale.') }}</p>
                </div>
            @endforelse

            {{-- Internal feedback list --}}
            <div class="card p-6">
                <h2 class="section-title mb-4">{{ __('Internal feedback (not published)') }}</h2>
                <div class="space-y-3">
                    @forelse($feedback as $item)
                        <div class="rounded-xl ring-1 ring-slate-900/[0.06] p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="font-medium text-slate-900">{{ $item->customer_name ?: __('Anonymous') }}</span>
                                    <span class="badge badge-danger tabular-nums">{{ $item->rating }}/{{ $item->campaign?->rating_scale }}</span>
                                    <span class="text-xs text-slate-400">{{ $item->branch?->name }} · {{ $item->created_at->diffForHumans() }}</span>
                                </div>
                                @if(auth()->user()->hasPermission('qr.manage'))
                                    <form method="POST" action="{{ route('qr.feedback.status', $item) }}">
                                        @csrf @method('PUT')
                                        <select name="status" onchange="this.form.submit()" class="input !w-auto !py-1.5 !px-2.5 !text-xs" aria-label="{{ __('Status') }}">
                                            <option value="new" @selected($item->status === 'new')>{{ __('New') }}</option>
                                            <option value="in_progress" @selected($item->status === 'in_progress')>{{ __('In progress') }}</option>
                                            <option value="resolved" @selected($item->status === 'resolved')>{{ __('Resolved') }}</option>
                                        </select>
                                    </form>
                                @endif
                            </div>
                            @if($item->comment)<p class="mt-2.5 text-sm text-slate-600" dir="auto">{{ $item->comment }}</p>@endif
                            <div class="mt-2.5 flex flex-wrap items-center gap-4 text-xs text-slate-400">
                                @if($item->customer_phone)<span class="inline-flex items-center gap-1.5"><x-icon name="phone" class="size-3" /> <span dir="ltr">{{ $item->customer_phone }}</span></span>@endif
                                @if($item->customer_email)<span class="inline-flex items-center gap-1.5"><x-icon name="mail" class="size-3" /> {{ $item->customer_email }}</span>@endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">{{ __('No internal feedback captured yet.') }}</p>
                    @endforelse
                </div>
                <div class="mt-4">{{ $feedback->links() }}</div>
            </div>
        </div>

        {{-- Create campaign --}}
        @if(auth()->user()->hasPermission('qr.manage'))
            <div class="card p-6 h-fit lg:sticky lg:top-24">
                <h2 class="section-title mb-5">{{ __('New QR campaign') }}</h2>
                <form method="POST" action="{{ route('qr.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="qr-name" class="label">{{ __('Name') }} <span class="text-red-500">*</span></label>
                        <input id="qr-name" name="name" value="{{ old('name') }}" required class="input" placeholder="{{ __('Table stickers — main branch') }}">
                    </div>
                    <div>
                        <label for="qr-branch" class="label">{{ __('Branch') }} <span class="text-red-500">*</span></label>
                        <select id="qr-branch" name="branch_id" required class="input">
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="qr-scale" class="label">{{ __('Rating scale') }}</label>
                            <select id="qr-scale" name="rating_scale" class="input">
                                <option value="5">1–5</option>
                                <option value="10">1–10</option>
                            </select>
                        </div>
                        <div>
                            <label for="qr-threshold" class="label">{{ __('Positive threshold') }}</label>
                            <input id="qr-threshold" type="number" name="threshold" value="{{ old('threshold', 4) }}" min="1" max="10" class="input">
                        </div>
                    </div>
                    <div>
                        <label for="qr-redirect" class="label">{{ __('Positive redirect URL (optional)') }}</label>
                        <input id="qr-redirect" name="positive_redirect_url" value="{{ old('positive_redirect_url') }}" placeholder="{{ __('Defaults to the branch Google review page') }}" class="input" dir="ltr">
                    </div>
                    <button class="btn btn-primary w-full"><x-icon name="plus" class="size-4" /> {{ __('Create campaign') }}</button>
                </form>
            </div>
        @endif
    </div>
@endsection
