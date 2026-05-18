{{-- Full Menu --}}
@if(!empty($site['menu_categories']))
<section class="py-16" id="full-menu">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-4 text-gray-900">Our Menu</h2>
        <p class="text-gray-600 text-center mb-8 max-w-2xl mx-auto">
            @if($orderingEnabled)
            Click items to add to your order
            @else
            Something delicious for everyone
            @endif
        </p>

        {{-- Dietary Legend — only show if the menu actually uses dietary tags --}}
        @php
            $hasDietaryItems = false;
            foreach ($site['menu_categories'] ?? [] as $cat) {
                foreach ($cat['items'] ?? [] as $item) {
                    if (!empty($item['dietary'])) { $hasDietaryItems = true; break 2; }
                }
            }
        @endphp
        @if($hasDietaryItems)
        <div class="flex flex-wrap justify-center gap-4 mb-8 text-sm">
            <span class="inline-flex items-center gap-1 text-gray-600">
                <span class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center text-xs">🌱</span> Vegetarian
            </span>
            <span class="inline-flex items-center gap-1 text-gray-600">
                <span class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center text-xs">🌿</span> Vegan
            </span>
            <span class="inline-flex items-center gap-1 text-gray-600">
                <span class="w-5 h-5 rounded-full bg-amber-100 flex items-center justify-center text-xs">GF</span> Gluten Free
            </span>
            <span class="inline-flex items-center gap-1 text-gray-600">
                <span class="w-5 h-5 rounded-full bg-purple-100 flex items-center justify-center text-xs">K</span> Keto
            </span>
            <span class="inline-flex items-center gap-1 text-gray-600">
                <span class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center text-xs">🌶️</span> Spicy
            </span>
        </div>
        @endif

        @foreach($site['menu_categories'] as $categoryKey => $category)
        <div class="mb-12">
            <div class="border-b-2 border-brand mb-6 pb-2">
                <h3 class="text-2xl font-bold text-gray-900">{{ $category['name'] }}</h3>
                @if(!empty($category['description']))
                <p class="text-sm text-gray-600 italic mt-1">{{ $category['description'] }}</p>
                @endif
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($category['items'] as $itemIndex => $item)
                @php
                    $itemId = $item['id'] ?? ($categoryKey . '_' . $itemIndex);
                    $itemPrice = floatval(preg_replace('/[^0-9.]/', '', $item['price']));
                    $hasBadges = !empty($item['badges']);
                    $hasDietary = !empty($item['dietary']);
                @endphp
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition group {{ $orderingEnabled ? 'cursor-pointer' : '' }}"
                     @if($orderingEnabled)
                     @click="addItem({ id: '{{ $itemId }}', name: '{{ addslashes($item['name']) }}', price: {{ $itemPrice }} })"
                     @endif>
                    {{-- Item Image --}}
                    @if(!empty($item['image']))
                    <div class="relative h-48 overflow-hidden">
                        <img src="{{ $item['image'] }}"
                             alt="{{ $item['alt_text'] ?? $item['name'] }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                             loading="lazy">
                        {{-- Badges --}}
                        @if($hasBadges)
                        <div class="absolute top-2 left-2 flex flex-wrap gap-1">
                            @foreach($item['badges'] as $badge)
                            @php
                                $colorClasses = [
                                    'red' => 'bg-red-500 text-white',
                                    'amber' => 'bg-amber-500 text-white',
                                    'green' => 'bg-green-500 text-white',
                                    'purple' => 'bg-purple-500 text-white',
                                    'blue' => 'bg-blue-500 text-white',
                                ];
                                $badgeColor = $colorClasses[$badge['color'] ?? 'red'] ?? 'bg-red-500 text-white';
                                $badgeLabel = $badge['label'] ?? 'Special';
                            @endphp
                            <span class="px-2 py-0.5 text-xs font-bold rounded-full {{ $badgeColor }}">
                                {{ $badgeLabel }}
                            </span>
                            @endforeach
                        </div>
                        @endif
                        {{-- Add to Cart Overlay --}}
                        @if($orderingEnabled)
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition flex items-center justify-center opacity-0 group-hover:opacity-100">
                            <span class="bg-white text-gray-900 px-4 py-2 rounded-full font-semibold text-sm shadow-lg transform scale-90 group-hover:scale-100 transition">
                                + Add to Order
                            </span>
                        </div>
                        @endif
                    </div>
                    @endif

                    <div class="p-4">
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="font-bold text-gray-900 text-lg">{{ $item['name'] }}</h4>
                            <span class="text-brand font-bold text-lg whitespace-nowrap ml-2">{{ $item['price'] }}</span>
                        </div>

                        @if(!empty($item['description']))
                        <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ $item['description'] }}</p>
                        @endif

                        @if(!empty($item['note']))
                        <p class="text-xs text-gray-500 italic mb-2">{{ $item['note'] }}</p>
                        @endif

                        {{-- Dietary Tags --}}
                        @if($hasDietary)
                        <div class="flex flex-wrap gap-1 mt-2">
                            @foreach($item['dietary'] as $diet)
                            @php
                                $dietaryIcons = [
                                    'vegetarian' => ['icon' => '🌱', 'bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => 'Vegetarian'],
                                    'vegan' => ['icon' => '🌿', 'bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => 'Vegan'],
                                    'gluten_free' => ['icon' => 'GF', 'bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => 'Gluten Free'],
                                    'dairy_free' => ['icon' => 'DF', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Dairy Free'],
                                    'nut_free' => ['icon' => 'NF', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'label' => 'Nut Free'],
                                    'spicy' => ['icon' => '🌶️', 'bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Spicy'],
                                    'keto' => ['icon' => 'K', 'bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'label' => 'Keto'],
                                    'low_carb' => ['icon' => 'LC', 'bg' => 'bg-indigo-100', 'text' => 'text-indigo-700', 'label' => 'Low Carb'],
                                    'halal' => ['icon' => 'H', 'bg' => 'bg-teal-100', 'text' => 'text-teal-700', 'label' => 'Halal'],
                                ];
                                $info = $dietaryIcons[$diet] ?? ['icon' => '?', 'bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => ucfirst(str_replace('_', ' ', $diet))];
                            @endphp
                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs {{ $info['bg'] }} {{ $info['text'] }}" title="{{ $info['label'] }}">
                                <span>{{ $info['icon'] }}</span>
                            </span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif