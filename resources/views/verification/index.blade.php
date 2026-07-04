@extends('layouts.app')

@section('title', __('Verification assistance'))

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Branch statuses --}}
            <div class="table-wrap">
                <div class="px-6 py-4 border-b border-slate-100 section-title">{{ __('Verification status per branch') }}</div>
                <div class="divide-y divide-slate-100">
                    @foreach($branches as $branch)
                        <div class="px-6 py-3.5 flex items-center justify-between hover:bg-slate-50/60 transition-colors">
                            <span class="flex items-center gap-3 text-sm font-medium text-slate-800">
                                <x-icon name="store" class="size-4 text-slate-300" />
                                {{ $branch->name }}
                            </span>
                            <span class="{{ match($branch->verification_status) {
                                'verified' => 'badge badge-success',
                                'pending', 'in_review' => 'badge badge-warning',
                                'failed' => 'badge badge-danger',
                                default => 'badge badge-neutral',
                            } }}">
                                {{ __(ucfirst(str_replace('_', ' ', $branch->verification_status))) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Requests & message threads --}}
            @foreach($requests as $request)
                <div class="card p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="font-semibold text-slate-900">{{ $request->branch?->name }}</span>
                            <span class="text-xs text-slate-400">{{ $request->created_at->format('M d, Y') }}</span>
                        </div>
                        <span class="badge badge-warning">{{ __(ucfirst(str_replace('_', ' ', $request->status))) }}</span>
                    </div>
                    @if($request->notes)<p class="mt-2.5 text-sm text-slate-600">{{ $request->notes }}</p>@endif
                    @if($request->documents)
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($request->documents as $document)
                                <a href="{{ asset('storage/'.$document['path']) }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                                    <x-icon name="paperclip" class="size-3" /> {{ $document['name'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-4 space-y-2.5">
                        @foreach($request->messages as $message)
                            <div class="rounded-xl p-3.5 text-sm {{ $message->is_support ? 'bg-brand-50/60 ring-1 ring-inset ring-brand-600/10' : 'bg-slate-50 ring-1 ring-inset ring-slate-900/[0.04]' }}">
                                <p class="flex items-center gap-1.5 text-xs text-slate-400 mb-1.5">
                                    @if($message->is_support)<x-icon name="heart-handshake" class="size-3.5 text-brand-500" />@endif
                                    {{ $message->is_support ? __('Support team') : $message->user?->name }} · {{ $message->created_at->diffForHumans() }}
                                </p>
                                <p class="text-slate-700">{{ $message->message }}</p>
                            </div>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('verification.message', $request) }}" class="mt-4 flex gap-2">
                        @csrf
                        <input name="message" required placeholder="{{ __('Message support…') }}" class="input flex-1" aria-label="{{ __('Message support…') }}">
                        <button class="btn btn-primary"><x-icon name="send" class="size-4" /> {{ __('Send') }}</button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="space-y-6">
            {{-- New request --}}
            <div class="card p-6">
                <h2 class="section-title mb-5">{{ __('Request verification help') }}</h2>
                <form method="POST" action="{{ route('verification.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <select name="branch_id" required class="input" aria-label="{{ __('Branch') }}">
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <textarea name="notes" rows="3" placeholder="{{ __('Describe your situation…') }}" class="input resize-y" aria-label="{{ __('Describe your situation…') }}"></textarea>
                    <div>
                        <label class="label !text-xs !text-slate-400">{{ __('Documents (PDF/JPG/PNG, max 5)') }}</label>
                        <input type="file" name="documents[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="text-sm text-slate-500">
                    </div>
                    <button class="btn btn-primary w-full"><x-icon name="badge-check" class="size-4" /> {{ __('Submit request') }}</button>
                </form>
            </div>

            {{-- Checklist --}}
            <div class="card p-6 text-sm">
                <h2 class="section-title mb-4">{{ __('Verification checklist') }}</h2>
                <ul class="space-y-2.5 text-slate-600">
                    @foreach([
                        __('Business name matches your official registration (CR).'),
                        __('Address matches the national address record.'),
                        __('Phone number is reachable at the location.'),
                        __('Signage photos: storefront with visible branding.'),
                        __('For Google: be ready for a postcard, phone or video verification.'),
                    ] as $item)
                        <li class="flex items-start gap-2.5">
                            <x-icon name="check-circle" class="size-4 mt-0.5 shrink-0 text-emerald-500" />
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection
