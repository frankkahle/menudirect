@if($orderingEnabled)
@push('head-scripts')
<script src="/js/cart.js?v={{ filemtime(public_path('js/cart.js')) }}" defer></script>
@endpush
@endif

@if(!empty($site['reservations']['enabled']) && ($site['reservations']['type'] ?? 'email') === 'built_in')
@push('scripts')
<script src="/js/reservation-widget.js?v={{ filemtime(public_path('js/reservation-widget.js')) }}" defer></script>
@endpush
@endif