@php($seo=$seo??[])
<title>{{ $seo['title']??app(\App\Services\StoreSettings::class)->get('store_name') }}</title>
<meta name="description" content="{{ $seo['description']??app(\App\Services\StoreSettings::class)->get('default_description') }}">
<link rel="canonical" href="{{ $seo['canonical']??url()->current() }}">
<meta name="robots" content="{{ ($seo['noindex']??false)?'noindex, nofollow':'index, follow' }}">
<meta property="og:title" content="{{ $seo['title']??'R&R Care BD' }}"><meta property="og:description" content="{{ $seo['description']??app(\App\Services\StoreSettings::class)->get('default_description') }}"><meta property="og:url" content="{{ $seo['canonical']??url()->current() }}"><meta property="og:type" content="{{ request()->is('journal/*')?'article':'website' }}"><meta property="og:image" content="{{ $seo['image']??asset('images/logo.jpg') }}"><meta name="twitter:card" content="summary_large_image">
@foreach($seo['schema']??[] as $schema)<script type="application/ld+json">{!! json_encode($schema,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>@endforeach
