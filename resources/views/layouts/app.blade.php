@php($settings=app(\App\Services\StoreSettings::class))
<!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
@include('components.seo',['seo'=>$seo??[]])
<link rel="icon" href="{{ $settings->assetUrl('logo') }}">
@vite(['resources/css/app.css','resources/js/app.js']) @livewireStyles
<style>:root{--forest:{{ preg_match('/^#[0-9a-f]{6}$/i',$settings->get('primary_color'))?$settings->get('primary_color'):'#193328' }};--peach:{{ preg_match('/^#[0-9a-f]{6}$/i',$settings->get('accent_color'))?$settings->get('accent_color'):'#E8BBA7' }}}</style>
</head><body>
<a class="sr-only" href="#main-content">Skip to content</a>
<div class="announcement">A LITTLE SELF-CARE, DELIVERED TO YOUR DOOR &nbsp; · &nbsp; CASH ON DELIVERY</div>
<header class="header"><div class="wrap"><div class="header-main">
<a href="{{ route('home') }}" class="brand" aria-label="R&R Care BD home"><img src="{{ $settings->assetUrl('logo') }}" width="60" height="60" alt="R&R Care BD logo"><div><strong>R&R</strong><small>CARE BD</small></div></a>
<form class="search" action="{{ route('shop') }}"><label class="sr-only" for="site-search">Search products</label><input id="site-search" name="q" type="search" placeholder="Find your next everyday essential…" value="{{ request('q') }}"><button aria-label="Search">⌕</button></form>
<div class="header-tools"><a class="optional" href="{{ route('journal') }}">The care journal</a><a href="{{ route('cart') }}">Your bag <livewire:cart-count /></a></div>
</div><nav class="nav" aria-label="Main navigation"><div class="nav-links"><a href="{{ route('shop') }}">Shop all</a>@foreach(\App\Models\Category::orderBy('name')->limit(3)->get() as $navCategory)<a href="{{ route('category',$navCategory->slug) }}">{{ $navCategory->name }}</a>@endforeach<a class="deal" href="{{ route('shop',['sale'=>1]) }}">Special offers ↗</a><a href="{{ route('journal') }}">Journal</a></div><span class="nav-note muted">Thoughtful beauty. Everyday care.</span></nav></div></header>
<main id="main-content" class="wrap">{{ $slot }}</main>
<footer class="footer"><div class="wrap"><div class="footer-grid"><div><a class="brand" href="{{ route('home') }}"><img src="{{ $settings->assetUrl('logo') }}" alt="" width="60" height="60" loading="lazy"><div><strong>R&R</strong><small>CARE BD</small></div></a><p>A little care goes a long way. Explore beauty essentials and build a routine that feels right for you.</p></div><div><h4>Explore</h4><a href="{{ route('shop') }}">All products</a><a href="{{ route('shop',['sale'=>1]) }}">Discount products</a><a href="{{ route('journal') }}">The care journal</a></div><div><h4>Get in touch</h4><p>{{ $settings->get('contact_address') }}</p>@if($settings->get('contact_email'))<a href="mailto:{{ $settings->get('contact_email') }}">{{ $settings->get('contact_email') }}</a>@endif @if($settings->get('contact_phone'))<a href="tel:{{ $settings->get('contact_phone') }}">{{ $settings->get('contact_phone') }}</a>@endif @foreach(['facebook_url'=>'Facebook','instagram_url'=>'Instagram'] as $key=>$label) @if($settings->get($key))<a href="{{ $settings->get($key) }}" rel="noopener noreferrer">{{ $label }} ↗</a>@endif @endforeach</div></div><div class="footer-bottom"><span>© {{ date('Y') }} {{ $settings->get('store_name') }}.</span><span>Made for your everyday. &nbsp; Cash on delivery · Bangladesh</span></div></div></footer>
<div x-data="{message:'',visible:false,timer:null}" x-on:toast.window="message=$event.detail.message;visible=true;clearTimeout(timer);timer=setTimeout(()=>visible=false,3000)" x-show="visible" x-cloak class="toast" role="status" x-text="message"></div>
@livewireScripts
</body></html>


