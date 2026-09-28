@extends('layouts.app')

@section('title', __('Payment Methods'))

@section('content')
<p class="text-[10.5px] font-extrabold tracking-[0.16em] text-[#e0793a] mb-1">{{ __('POOL SABONG') }}</p>
<h1 class="text-2xl font-extrabold tracking-tight mb-2">{{ __('Payment Methods') }}</h1>
<p class="text-sm text-[#8a7a70] mb-6">{{ __('Save your GCash and/or Maya account once — deposits will use it automatically, and withdrawals will only ever be sent here.') }}</p>

@if (session('success'))
    <div class="rounded-xl p-4 mb-6 text-sm" style="background:rgba(6,78,59,0.25);border:1px solid rgba(16,185,129,0.4);">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="rounded-xl p-4 mb-6 text-sm" style="background:rgba(120,20,20,0.25);border:1px solid rgba(220,38,38,0.5);">{{ session('error') }}</div>
@endif

@include('player.partials.payment-method-forms')
@endsection
