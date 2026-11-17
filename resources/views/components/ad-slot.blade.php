@props(['placement'])
@php($slotId = config('pisicata.adsense.'.$placement))
@if (config('pisicata.indexable') && config('pisicata.adsense.enabled') && preg_match('/^ca-pub-\d{16}$/D', (string) config('pisicata.adsense.client')) && preg_match('/^\d{5,20}$/D', (string) $slotId))
    <aside class="ad-slot ad-{{ $placement }}" aria-label="Publicitate" data-ad-slot="{{ $slotId }}" data-ad-placement="{{ $placement }}">
        <span>Publicitate</span>
        <div class="ad-space"></div>
    </aside>
@endif
