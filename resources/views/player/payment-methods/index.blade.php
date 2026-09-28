@extends('layouts.app')

@section('title', __('Payment Methods'))

@section('content')

<p class="gold-eyebrow">{{ $cms['site_name'] }}</p>
<h1 class="gold-serif" style="font-size:24px;font-weight:600;color:#f4efe4;text-align:center;margin:2px 0 8px">{{ __('Payment Methods') }}</h1>
<p class="text-sm text-[#8a7a70] mb-6 text-center">{{ __('Save your GCash and/or Maya account once — deposits will use it automatically, and withdrawals will only ever be sent here.') }}</p>

@if (session('success'))
    <div class="rounded-xl p-4 mb-6 text-sm" style="background:rgba(6,78,59,0.25);border:1px solid rgba(16,185,129,0.4);">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="rounded-xl p-4 mb-6 text-sm" style="background:rgba(120,20,20,0.25);border:1px solid rgba(220,38,38,0.5);">{{ session('error') }}</div>
@endif

@include('player.partials.payment-method-forms')
@endsection
