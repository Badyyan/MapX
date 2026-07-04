@extends('layouts.app')

@section('title', __('Verification assistance'))

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Branch statuses --}}
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 font-semibold">{{ __('Verification status per branch') }}</div>
                <div class="divide-y divide-slate-100">
                    @foreach($branches as $branch)
                        <div class="px-6 py-3 flex items-center justify-between">
                            <span class="text-sm font-medium">{{ $branch->name }}</span>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ match($branch->verification_status) {
                                    'verified' => 'bg-emerald-100 text-emerald-700',
                                    'pending', 'in_review' => 'bg-amber-100 text-amber-700',
                                    'failed' => 'bg-red-100 text-red-700',
                                    default => 'bg-slate-100 text-slate-600',
                                } }}">
                                {{ __(ucfirst(str_replace('_', ' ', $branch->verification_status))) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Requests & message threads --}}
            @foreach($requests as $request)
                <div class="bg-white rounded-2xl border border-slate-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-semibold">{{ $request->branch?->name }}</span>
                            <span class="text-xs text-slate-500 ms-2">{{ $request->created_at->format('M d, Y') }}</span>
                        </div>
                        <span class="rounded-full bg-amber-100 text-amber-700 px-2.5 py-0.5 text-xs font-medium">{{ __(ucfirst(str_replace('_', ' ', $request->status))) }}</span>
                    </div>
                    @if($request->notes)<p class="mt-2 text-sm text-slate-600">{{ $request->notes }}</p>@endif
                    @if($request->documents)
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($request->documents as $document)
                                <a href="{{ asset('storage/'.$document['path']) }}" target="_blank" rel="noopener" class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-1.5 text-xs hover:bg-slate-100">📎 {{ $document['name'] }}</a>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-4 space-y-2">
                        @foreach($request->messages as $message)
                            <div class="rounded-xl p-3 text-sm {{ $message->is_support ? 'bg-brand-50 border border-brand-100' : 'bg-slate-50 border border-slate-200' }}">
                                <div class="text-xs text-slate-500 mb-1">{{ $message->is_support ? __('Support team') : $message->user?->name }} · {{ $message->created_at->diffForHumans() }}</div>
                                {{ $message->message }}
                            </div>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('verification.message', $request) }}" class="mt-3 flex gap-2">
                        @csrf
                        <input name="message" required placeholder="{{ __('Message support…') }}" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <button class="rounded-lg bg-brand-600 text-white px-4 py-2 text-sm font-medium hover:bg-brand-700">{{ __('Send') }}</button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="space-y-6">
            {{-- New request --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h2 class="font-semibold mb-4">{{ __('Request verification help') }}</h2>
                <form method="POST" action="{{ route('verification.store') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <select name="branch_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <textarea name="notes" rows="3" placeholder="{{ __('Describe your situation…') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">{{ __('Documents (PDF/JPG/PNG, max 5)') }}</label>
                        <input type="file" name="documents[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="text-sm">
                    </div>
                    <button class="w-full rounded-lg bg-brand-600 text-white py-2 text-sm font-medium hover:bg-brand-700">{{ __('Submit request') }}</button>
                </form>
            </div>

            {{-- Checklist --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 text-sm">
                <h2 class="font-semibold mb-3">{{ __('Verification checklist') }}</h2>
                <ul class="space-y-2 text-slate-600 list-disc ms-4">
                    <li>{{ __('Business name matches your official registration (CR).') }}</li>
                    <li>{{ __('Address matches the national address record.') }}</li>
                    <li>{{ __('Phone number is reachable at the location.') }}</li>
                    <li>{{ __('Signage photos: storefront with visible branding.') }}</li>
                    <li>{{ __('For Google: be ready for a postcard, phone or video verification.') }}</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
