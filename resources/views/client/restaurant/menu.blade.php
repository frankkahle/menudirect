@extends('layouts.app')

@section('title', 'Menu Editor - ' . $site->business_name)

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('client.restaurant.show', $site) }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to {{ $site->business_name }}
        </a>

        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-2">
                    Menu Editor
                    <x-help-icon article="menu-overview" tooltip="How the menu editor works" size="md" />
                </h1>
                <p class="text-gray-600 mt-1">Drag to reorder, click prices to edit, use the icons for quick actions.</p>
            </div>
            <button type="button" onclick="showAddCategoryModal()" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Category
            </button>
        </div>
    </div>

    @if(session('status'))
    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700">{{ session('status') }}</p>
            </div>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-red-800">There were errors with your submission:</p>
                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    {{-- Search & filter bar --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6 flex flex-col sm:flex-row gap-3 sm:items-center" x-data="{ q: '', filter: 'all' }">
        <div class="relative flex-1">
            <input type="search" x-model="q" @input="window.menuFilter && menuFilter(q, filter)"
                   placeholder="Search menu items..." autocomplete="off"
                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <div class="flex gap-1 bg-gray-100 rounded-md p-1 text-xs font-medium">
            <button type="button" @click="filter = 'all'; window.menuFilter && menuFilter(q, filter)"
                    :class="filter === 'all' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'"
                    class="px-3 py-1.5 rounded transition">All</button>
            <button type="button" @click="filter = 'active'; window.menuFilter && menuFilter(q, filter)"
                    :class="filter === 'active' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'"
                    class="px-3 py-1.5 rounded transition">Active</button>
            <button type="button" @click="filter = 'hidden'; window.menuFilter && menuFilter(q, filter)"
                    :class="filter === 'hidden' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'"
                    class="px-3 py-1.5 rounded transition">Hidden</button>
            <button type="button" @click="filter = 'featured'; window.menuFilter && menuFilter(q, filter)"
                    :class="filter === 'featured' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'"
                    class="px-3 py-1.5 rounded transition">Featured</button>
            <button type="button" @click="filter = 'no-photo'; window.menuFilter && menuFilter(q, filter)"
                    :class="filter === 'no-photo' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'"
                    class="px-3 py-1.5 rounded transition" title="Items without photos">No Photo</button>
        </div>
    </div>

    <!-- Categories List -->
    <div id="categories-container" class="space-y-6">
        @forelse($categories as $category)
        <div class="bg-white rounded-lg shadow" data-category-id="{{ $category->id }}">
            <!-- Category Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <div class="flex items-center">
                    <button type="button" class="drag-handle cursor-move text-gray-400 hover:text-gray-600 mr-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $category->name }}</h2>
                        @if($category->description)
                        <p class="text-sm text-gray-500">{{ $category->description }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500">{{ $category->items->count() }} items</span>
                    <button type="button" onclick="showAddItemModal({{ $category->id }}, '{{ $category->name }}')" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                        + Add Item
                    </button>
                    <button type="button" onclick="showEditCategoryModal({{ $category->id }}, '{{ addslashes($category->name) }}', '{{ addslashes($category->description ?? '') }}')" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <form action="{{ route('client.restaurant.categories.destroy', [$site, $category]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this category and all its items?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Items List -->
            <div class="divide-y divide-gray-100" data-category-items="{{ $category->id }}">
                @forelse($category->items as $item)
                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 group/row {{ !$item->active ? 'opacity-60' : '' }}"
                     data-item-id="{{ $item->id }}"
                     data-item-name="{{ strtolower($item->name) }}"
                     data-item-active="{{ $item->active ? '1' : '0' }}"
                     data-item-featured="{{ $item->featured ? '1' : '0' }}"
                     data-item-has-photo="{{ $item->image_url ? '1' : '0' }}">
                    <div class="flex items-center flex-1 min-w-0">
                        <button type="button" class="item-drag-handle cursor-move text-gray-400 hover:text-gray-600 mr-3 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                            </svg>
                        </button>
                        @if($item->image_url)
                        <div class="relative group/thumb mr-4 flex-shrink-0">
                            <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="w-12 h-12 rounded object-cover">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/thumb:opacity-100 rounded flex items-center justify-center transition">
                                <x-recrop-button
                                    :url="$item->image_url"
                                    :aspect="config('images.aspect_ratios.menu_item')"
                                    :post-url="route('client.restaurant.items.image', [$site, $item])"
                                    field-name="image"
                                    label="Crop"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-medium text-gray-900 bg-white rounded hover:bg-gray-100" />
                            </div>
                        </div>
                        @else
                        <div class="w-12 h-12 rounded bg-gray-100 mr-4 flex-shrink-0 flex items-center justify-center text-gray-300" title="No photo">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                {{-- Inline-editable name --}}
                                <h3 class="font-medium text-gray-900 px-1.5 py-0.5 rounded cursor-text hover:bg-yellow-50 -ml-1.5"
                                    contenteditable="true"
                                    data-inline-edit="name"
                                    data-item-id="{{ $item->id }}"
                                    spellcheck="false"
                                    title="Click to edit">{{ $item->name }}</h3>
                                @if($item->featured)
                                <span class="badge-featured px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Featured</span>
                                @endif
                                @if(!$item->active)
                                <span class="badge-hidden px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">Hidden</span>
                                @endif
                            </div>
                            @if($item->description)
                            <p class="text-sm text-gray-500 mt-1">{{ Str::limit($item->description, 80) }}</p>
                            @endif
                            @if($item->hasDietaryInfo())
                            <div class="flex flex-wrap gap-1 mt-1">
                                @foreach($item->getDietaryLabels() as $label)
                                <span class="px-1.5 py-0.5 text-xs bg-green-50 text-green-700 rounded">{{ $label }}</span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center space-x-3 flex-shrink-0">
                        {{-- Inline-editable price --}}
                        <span class="font-semibold text-gray-900 px-1.5 py-0.5 rounded cursor-text hover:bg-yellow-50 tabular-nums"
                              contenteditable="true"
                              data-inline-edit="price"
                              data-item-id="{{ $item->id }}"
                              data-raw-price="{{ $item->price }}"
                              spellcheck="false"
                              title="Click to edit price">{{ $item->formatted_price }}</span>
                        @if($item->price_note)
                        <span class="text-sm text-gray-500">{{ $item->price_note }}</span>
                        @endif
                        <div class="flex items-center space-x-1">
                            {{-- Active / hidden toggle --}}
                            <button type="button" data-toggle-active data-item-id="{{ $item->id }}"
                                    class="p-1.5 rounded hover:bg-gray-100 {{ $item->active ? 'text-emerald-500' : 'text-gray-400' }}"
                                    title="{{ $item->active ? 'Visible — click to hide' : 'Hidden — click to show' }}">
                                @if($item->active)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                @endif
                            </button>
                            {{-- Featured star --}}
                            <button type="button" data-toggle-featured data-item-id="{{ $item->id }}"
                                    class="p-1.5 rounded hover:bg-gray-100 {{ $item->featured ? 'text-yellow-500' : 'text-gray-400' }}"
                                    title="{{ $item->featured ? 'Featured — click to unfeature' : 'Click to feature' }}">
                                <svg class="w-5 h-5" fill="{{ $item->featured ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            </button>
                            {{-- Duplicate --}}
                            <button type="button" data-duplicate-item data-item-id="{{ $item->id }}"
                                    class="p-1.5 rounded hover:bg-gray-100 text-gray-400 hover:text-indigo-600"
                                    title="Duplicate this item">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                            {{-- Edit (full modal) --}}
                            <button type="button" onclick="showEditItemModal({{ json_encode($item) }})"
                                    class="p-1.5 rounded hover:bg-gray-100 text-gray-400 hover:text-gray-700"
                                    title="Edit details (description, dietary, photo)">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            {{-- Delete --}}
                            <form action="{{ route('client.restaurant.items.destroy', [$site, $item]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this item? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 rounded hover:bg-red-50 text-gray-400 hover:text-red-600" title="Delete">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    <p>No items in this category yet.</p>
                    <button type="button" onclick="showAddItemModal({{ $category->id }}, '{{ $category->name }}')" class="mt-2 text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                        Add your first item
                    </button>
                </div>
                @endforelse
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">No menu categories yet</h3>
            <p class="mt-2 text-gray-500">Get started by creating your first category.</p>
            <div class="mt-6">
                <button type="button" onclick="showAddCategoryModal()" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add First Category
                </button>
            </div>
        </div>
        @endforelse
    </div>
</div>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" onclick="closeModal('addCategoryModal')"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Add Category</h3>
            <form action="{{ route('client.restaurant.categories.store', $site) }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name *</label>
                        <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Appetizers">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Start your meal with these delicious options"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('addCategoryModal')" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editCategoryModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" onclick="closeModal('editCategoryModal')"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Edit Category</h3>
            <form id="editCategoryForm" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name *</label>
                        <input type="text" name="name" id="editCategoryName" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="editCategoryDescription" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('editCategoryModal')" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div id="addItemModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" onclick="closeModal('addItemModal')"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Add Item to <span id="addItemCategoryName"></span></h3>
            <form id="addItemForm" method="POST">
                @csrf
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Name *</label>
                            <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Caesar Salad">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Price *</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">$</span>
                                </div>
                                <input type="number" name="price" step="0.01" min="0" required class="pl-7 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="12.99">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Price Note</label>
                            <input type="text" name="price_note" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="per person">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Fresh romaine lettuce with house-made dressing..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Dietary Info</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach(\App\Models\MenuItem::DIETARY_OPTIONS as $key => $label)
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="dietary_info[]" value="{{ $key }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="featured" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Featured item (shows in highlights section)</span>
                        </label>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('addItemModal')" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div id="editItemModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" onclick="closeModal('editItemModal')"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Edit Item</h3>
            <form id="editItemForm" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Name *</label>
                            <input type="text" name="name" id="editItemName" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Price *</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">$</span>
                                </div>
                                <input type="number" name="price" id="editItemPrice" step="0.01" min="0" required class="pl-7 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Price Note</label>
                            <input type="text" name="price_note" id="editItemPriceNote" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="editItemDescription" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Dietary Info</label>
                        <div class="flex flex-wrap gap-2" id="editItemDietaryInfo">
                            @foreach(\App\Models\MenuItem::DIETARY_OPTIONS as $key => $label)
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="dietary_info[]" value="{{ $key }}" class="dietary-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="featured" id="editItemFeatured" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Featured</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="active" id="editItemActive" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Active (visible on site)</span>
                        </label>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('editItemModal')" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Save Changes</button>
                </div>
            </form>

            {{-- Image Upload (separate form since edit form uses PUT) --}}
            <div class="mt-4 pt-4 border-t border-gray-200">
                <label class="block text-sm font-medium text-gray-700 mb-2">Item Photo</label>
                <div id="editItemImagePreview" class="mb-3 hidden">
                    <img id="editItemImageImg" src="" alt="" class="w-24 h-24 rounded-lg object-cover mb-2">
                    <button type="button" id="editItemRecropBtn" class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span>Re-crop existing</span>
                    </button>
                </div>
                <form id="editItemImageForm" method="POST" enctype="multipart/form-data" class="flex items-center gap-3">
                    @csrf
                    <x-image-cropper
                        name="image"
                        :aspect-ratio="config('images.aspect_ratios.menu_item')"
                        :auto-submit="true"
                        preview-class="h-24 w-24 rounded-lg object-cover border hidden"
                        help-text="JPG, PNG, GIF, or WebP. Max 5MB. Auto-cropped to a square. Or re-crop the existing photo without re-uploading." />
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const siteId = {{ $site->id }};

function showAddCategoryModal() {
    document.getElementById('addCategoryModal').classList.remove('hidden');
}

function showEditCategoryModal(categoryId, name, description) {
    document.getElementById('editCategoryForm').action = `/client/restaurant/${siteId}/categories/${categoryId}`;
    document.getElementById('editCategoryName').value = name;
    document.getElementById('editCategoryDescription').value = description;
    document.getElementById('editCategoryModal').classList.remove('hidden');
}

function showAddItemModal(categoryId, categoryName) {
    document.getElementById('addItemForm').action = `/client/restaurant/${siteId}/categories/${categoryId}/items`;
    document.getElementById('addItemCategoryName').textContent = categoryName;
    document.getElementById('addItemModal').classList.remove('hidden');
}

function showEditItemModal(item) {
    document.getElementById('editItemForm').action = `/client/restaurant/${siteId}/items/${item.id}`;
    document.getElementById('editItemName').value = item.name;
    document.getElementById('editItemPrice').value = item.price;
    document.getElementById('editItemPriceNote').value = item.price_note || '';
    document.getElementById('editItemDescription').value = item.description || '';
    document.getElementById('editItemFeatured').checked = item.featured;
    document.getElementById('editItemActive').checked = item.active;

    // Set dietary info checkboxes
    document.querySelectorAll('#editItemDietaryInfo .dietary-checkbox').forEach(cb => {
        cb.checked = item.dietary_info && item.dietary_info.includes(cb.value);
    });

    // Image upload form action
    document.getElementById('editItemImageForm').action = `/client/restaurant/${siteId}/items/${item.id}/image`;

    // Show current image if exists
    var preview = document.getElementById('editItemImagePreview');
    var img = document.getElementById('editItemImageImg');
    var recropBtn = document.getElementById('editItemRecropBtn');
    if (item.image_url) {
        img.src = item.image_url;
        img.alt = item.name;
        preview.classList.remove('hidden');
        recropBtn.onclick = function () {
            window.recropImageFromUrl({
                url: item.image_url,
                aspect: {{ config('images.aspect_ratios.menu_item') }},
                postUrl: `/client/restaurant/${siteId}/items/${item.id}/image`,
                fieldName: 'image',
                method: 'POST',
            });
        };
    } else {
        preview.classList.add('hidden');
        recropBtn.onclick = null;
    }

    document.getElementById('editItemModal').classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('[id$="Modal"]').forEach(modal => {
            modal.classList.add('hidden');
        });
    }
});
</script>

{{-- SortableJS for drag-and-drop reordering of categories and items --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"
        integrity="sha384-BSxuMLxX+FCbTdYec3TbXlnMGEEM2QXTFdtDaveen71o+jswm2J36+xFqp8k4VHM"
        crossorigin="anonymous"></script>
<script>
(function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    // Reorder categories
    const categoriesContainer = document.getElementById('categories-container');
    if (categoriesContainer) {
        new Sortable(categoriesContainer, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'opacity-50',
            onEnd: function(evt) {
                const categoryIds = Array.from(categoriesContainer.querySelectorAll('[data-category-id]'))
                    .map(el => parseInt(el.dataset.categoryId));

                fetch(`/client/restaurant/{{ $site->id }}/categories/reorder`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ categories: categoryIds }),
                }).then(r => {
                    if (!r.ok) {
                        console.error('Reorder failed', r.status);
                        alert('Failed to save new order. Please refresh and try again.');
                    }
                });
            }
        });
    }

    // Reorder items within a category AND drag items between categories.
    // All category item lists share the same `group` so they're cross-droppable.
    function persistCategoryOrder(itemList) {
        const categoryId = itemList.dataset.categoryItems;
        const itemIds = Array.from(itemList.querySelectorAll('[data-item-id]'))
            .map(el => parseInt(el.dataset.itemId));

        return fetch(`/client/restaurant/{{ $site->id }}/categories/${categoryId}/items/reorder`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ items: itemIds }),
        });
    }

    document.querySelectorAll('[data-category-items]').forEach(function(itemList) {
        new Sortable(itemList, {
            animation: 150,
            handle: '.item-drag-handle',
            ghostClass: 'opacity-50',
            // Shared group lets items be dragged BETWEEN categories
            group: 'menu-items',

            // Fired on the destination list when item is added from another category
            onAdd: function(evt) {
                const itemEl = evt.item;
                const itemId = parseInt(itemEl.dataset.itemId);
                const newCategoryId = itemList.dataset.categoryItems;
                const oldCategoryId = evt.from.dataset.categoryItems;

                // Update the item's category server-side, then resync both lists
                fetch(`/client/restaurant/{{ $site->id }}/items/${itemId}/move`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ category_id: newCategoryId }),
                }).then(function(r) {
                    if (!r.ok) {
                        alert('Could not move item. Please refresh and try again.');
                        // Put it back
                        evt.from.insertBefore(itemEl, evt.from.children[evt.oldIndex]);
                        return;
                    }
                    // Update sort orders in both source and destination lists
                    return Promise.all([
                        persistCategoryOrder(itemList),
                        evt.from ? persistCategoryOrder(evt.from) : Promise.resolve(),
                    ]);
                });
            },

            // Fired on the source list when reordered within the same list (no category change)
            onUpdate: function() {
                persistCategoryOrder(itemList).then(function(r) {
                    if (!r.ok) alert('Failed to save new order. Please refresh and try again.');
                });
            },
        });
    });
    // ============================================================
    // Inline edit (click name or price to edit, save on Enter/blur)
    // ============================================================
    function inlineUpdate(itemId, field, value, el) {
        const original = el.dataset.original ?? el.textContent;
        return fetch(`/client/restaurant/{{ $site->id }}/items/${itemId}/inline-update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ field, value }),
        }).then(async (r) => {
            if (!r.ok) {
                const err = await r.json().catch(() => ({}));
                el.textContent = original;
                alert(err.error || 'Could not save. Please try again.');
                return;
            }
            const data = await r.json();
            if (field === 'price') {
                el.textContent = data.formatted_price;
                el.dataset.rawPrice = data.price;
            } else if (field === 'name') {
                el.textContent = data.name;
                const row = el.closest('[data-item-id]');
                if (row) row.dataset.itemName = String(data.name).toLowerCase();
            }
            el.classList.add('bg-emerald-50');
            setTimeout(() => el.classList.remove('bg-emerald-50'), 600);
        });
    }

    document.querySelectorAll('[data-inline-edit]').forEach((el) => {
        el.addEventListener('focus', () => { el.dataset.original = el.textContent; });
        el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); el.blur(); }
            if (e.key === 'Escape') { el.textContent = el.dataset.original; el.blur(); }
        });
        el.addEventListener('blur', () => {
            const newVal = el.textContent.trim();
            const orig = (el.dataset.original ?? '').trim();
            if (newVal === orig || newVal === '') {
                el.textContent = orig;
                return;
            }
            const itemId = el.dataset.itemId;
            const field = el.dataset.inlineEdit;
            inlineUpdate(itemId, field, newVal, el);
        });
    });

    // ============================================================
    // Quick toggle: active (eye icon) — reload after toggle for clean state
    // ============================================================
    document.querySelectorAll('[data-toggle-active]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const itemId = btn.dataset.itemId;
            btn.disabled = true;
            const r = await fetch(`/client/restaurant/{{ $site->id }}/items/${itemId}/active`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
            btn.disabled = false;
            if (!r.ok) return alert('Failed to update visibility.');
            window.location.reload();
        });
    });

    // ============================================================
    // Quick toggle: featured (star icon) — reload after toggle for clean state
    // ============================================================
    document.querySelectorAll('[data-toggle-featured]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const itemId = btn.dataset.itemId;
            btn.disabled = true;
            const r = await fetch(`/client/restaurant/{{ $site->id }}/items/${itemId}/featured`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
            btn.disabled = false;
            if (!r.ok) return alert('Failed to update featured status.');
            window.location.reload();
        });
    });

    // ============================================================
    // Duplicate item — POST and reload to show new item
    // ============================================================
    document.querySelectorAll('[data-duplicate-item]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Duplicate this item?')) return;
            const itemId = btn.dataset.itemId;
            btn.disabled = true;
            const r = await fetch(`/client/restaurant/{{ $site->id }}/items/${itemId}/duplicate`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
            btn.disabled = false;
            if (!r.ok) return alert('Failed to duplicate item.');
            window.location.reload();
        });
    });

    // ============================================================
    // Search & filter — hide rows that don't match
    // ============================================================
    window.menuFilter = function (query, filter) {
        query = (query || '').trim().toLowerCase();
        document.querySelectorAll('[data-item-id]').forEach((row) => {
            if (!row.hasAttribute('data-item-name')) return;
            const matchesQuery = !query || row.dataset.itemName.includes(query);
            let matchesFilter = true;
            if (filter === 'active') matchesFilter = row.dataset.itemActive === '1';
            else if (filter === 'hidden') matchesFilter = row.dataset.itemActive === '0';
            else if (filter === 'featured') matchesFilter = row.dataset.itemFeatured === '1';
            else if (filter === 'no-photo') matchesFilter = row.dataset.itemHasPhoto === '0';
            row.style.display = (matchesQuery && matchesFilter) ? '' : 'none';
        });
        document.querySelectorAll('[data-category-id]').forEach((cat) => {
            const visibleItems = cat.querySelectorAll('[data-item-name]:not([style*="display: none"])');
            cat.style.display = visibleItems.length === 0 && (query || filter !== 'all') ? 'none' : '';
        });
    };
})();
</script>
@endsection
