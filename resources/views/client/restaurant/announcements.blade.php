@extends('layouts.app')

@section('title', 'Announcements - ' . $site->business_name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                <h1 class="text-3xl font-bold text-gray-900">Announcements</h1>
                <p class="text-gray-600 mt-1">Manage specials, closures, and important notices</p>
            </div>
            <button type="button" onclick="showAddAnnouncementModal()" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Announcement
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

    <!-- Announcement Types Legend -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <h3 class="text-sm font-medium text-gray-700 mb-2">Announcement Types</h3>
        <div class="flex flex-wrap gap-3">
            @foreach(\App\Models\Announcement::TYPES as $key => $label)
            <span class="px-3 py-1 text-sm rounded-full border {{ \App\Models\Announcement::TYPE_COLORS[$key] }}">
                {{ $label }}
            </span>
            @endforeach
        </div>
    </div>

    <!-- Announcements List -->
    <div class="space-y-4">
        @forelse($announcements as $announcement)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-l-4 {{ str_replace('bg-', 'border-', explode(' ', $announcement->type_color_class)[0]) }}">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-2">
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $announcement->type_color_class }}">
                                {{ $announcement->type_label }}
                            </span>
                            @if($announcement->isCurrentlyActive())
                            <span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">Active Now</span>
                            @elseif($announcement->isFuture())
                            <span class="px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">Scheduled</span>
                            @elseif($announcement->isPast())
                            <span class="px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">Ended</span>
                            @elseif(!$announcement->active)
                            <span class="px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">Disabled</span>
                            @endif
                        </div>
                        @if($announcement->image_url)
                        <div class="relative group/annimg w-full max-w-xs mb-2">
                            <img src="{{ $announcement->image_url }}" alt="{{ $announcement->title ?? 'Announcement image' }}" class="w-full h-24 object-cover rounded-lg border">
                            <div class="absolute top-1 left-1 opacity-0 group-hover/annimg:opacity-100 transition-opacity">
                                <x-recrop-button
                                    :url="$announcement->image_url"
                                    :aspect="config('images.aspect_ratios.announcement')"
                                    :post-url="route('client.restaurant.announcements.image', [$site, $announcement])"
                                    field-name="image"
                                    label="Re-crop"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-white bg-indigo-600 rounded hover:bg-indigo-700 shadow" />
                            </div>
                        </div>
                        @endif
                        @if($announcement->title)
                        <p class="text-gray-900 font-semibold">{{ $announcement->title }}</p>
                        @endif
                        @if($announcement->message)
                        <p class="text-gray-900">{{ $announcement->message }}</p>
                        @endif
                        @if($announcement->link_url)
                        <p class="text-sm text-indigo-600 mt-1"><a href="{{ $announcement->link_url }}" target="_blank" rel="noopener" class="hover:underline">{{ $announcement->link_url }}</a></p>
                        @endif
                        @if($announcement->isScheduled())
                        <p class="text-sm text-gray-500 mt-2">
                            @if($announcement->starts_at)
                            Starts: {{ $announcement->starts_at->format('M j, Y g:i A') }}
                            @endif
                            @if($announcement->starts_at && $announcement->ends_at) &bull; @endif
                            @if($announcement->ends_at)
                            Ends: {{ $announcement->ends_at->format('M j, Y g:i A') }}
                            @endif
                        </p>
                        @endif
                    </div>
                    <div class="flex items-center space-x-2 ml-4">
                        <form action="{{ route('client.restaurant.announcements.toggle', [$site, $announcement]) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="p-1 {{ $announcement->active ? 'text-green-600' : 'text-gray-400' }} hover:text-green-700" title="{{ $announcement->active ? 'Disable' : 'Enable' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($announcement->active)
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    @endif
                                </svg>
                            </button>
                        </form>
                        <button type="button" onclick='showEditAnnouncementModal(@json($announcement))' class="p-1 text-gray-500 hover:text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <form action="{{ route('client.restaurant.announcements.destroy', [$site, $announcement]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this announcement?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1 text-red-500 hover:text-red-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">No announcements yet</h3>
            <p class="mt-2 text-gray-500">Create announcements to inform your customers about specials or closures.</p>
            <div class="mt-6">
                <button type="button" onclick="showAddAnnouncementModal()" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Create First Announcement
                </button>
            </div>
        </div>
        @endforelse
    </div>
</div>

<!-- Add Announcement Modal -->
<div id="addAnnouncementModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" onclick="closeModal('addAnnouncementModal')"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Add Announcement</h3>
            <form action="{{ route('client.restaurant.announcements.store', $site) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type *</label>
                        <select name="type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach(\App\Models\Announcement::TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Title (optional)</label>
                        <input type="text" name="title" maxlength="120" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g., Mother's Day Brunch">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Message</label>
                        <textarea name="message" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Enter your announcement message..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Image (optional)</label>
                        <x-image-cropper
                            name="image"
                            :aspect-ratio="config('images.aspect_ratios.announcement')"
                            preview-class="w-full h-20 object-cover rounded-lg border"
                            help-text="JPG, PNG, GIF, or WebP. Cropped to 3:1 wide banner." />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Link URL (optional)</label>
                        <input type="url" name="link_url" maxlength="500" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="https://…">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Starts At (optional)</label>
                            <input type="datetime-local" name="starts_at" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ends At (optional)</label>
                            <input type="datetime-local" name="ends_at" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <p class="text-sm text-gray-500">Provide at least a message, title, or image. Leave dates empty to show indefinitely.</p>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('addAnnouncementModal')" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Add Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div id="editAnnouncementModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" onclick="closeModal('editAnnouncementModal')"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Edit Announcement</h3>
            <form id="editAnnouncementForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type *</label>
                        <select name="type" id="editAnnouncementType" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach(\App\Models\Announcement::TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Title (optional)</label>
                        <input type="text" name="title" id="editAnnouncementTitle" maxlength="120" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Message</label>
                        <textarea name="message" id="editAnnouncementMessage" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Image</label>
                        <img id="editAnnouncementCurrentImage" src="" alt="Current image" class="w-full max-w-xs h-20 object-cover rounded-lg border mb-2 hidden">
                        <label id="editAnnouncementRemoveImageWrap" class="inline-flex items-center mb-2 hidden">
                            <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Remove current image</span>
                        </label>
                        <x-image-cropper
                            name="image"
                            :aspect-ratio="config('images.aspect_ratios.announcement')"
                            preview-class="w-full h-20 object-cover rounded-lg border"
                            help-text="Upload a new image to replace the current one. Cropped to 3:1." />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Link URL (optional)</label>
                        <input type="url" name="link_url" id="editAnnouncementLinkUrl" maxlength="500" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="https://…">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Starts At (optional)</label>
                            <input type="datetime-local" name="starts_at" id="editAnnouncementStartsAt" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ends At (optional)</label>
                            <input type="datetime-local" name="ends_at" id="editAnnouncementEndsAt" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="active" id="editAnnouncementActive" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Active (visible when scheduled)</span>
                        </label>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('editAnnouncementModal')" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const siteId = {{ $site->id }};

function showAddAnnouncementModal() {
    document.getElementById('addAnnouncementModal').classList.remove('hidden');
}

function showEditAnnouncementModal(announcement) {
    document.getElementById('editAnnouncementForm').action = `/client/restaurant/${siteId}/announcements/${announcement.id}`;
    document.getElementById('editAnnouncementType').value = announcement.type;
    document.getElementById('editAnnouncementMessage').value = announcement.message || '';
    document.getElementById('editAnnouncementTitle').value = announcement.title || '';
    document.getElementById('editAnnouncementLinkUrl').value = announcement.link_url || '';
    document.getElementById('editAnnouncementActive').checked = announcement.active;

    const currentImg = document.getElementById('editAnnouncementCurrentImage');
    const removeWrap = document.getElementById('editAnnouncementRemoveImageWrap');
    if (announcement.image_url) {
        currentImg.src = announcement.image_url;
        currentImg.classList.remove('hidden');
        removeWrap.classList.remove('hidden');
        removeWrap.querySelector('input[type="checkbox"]').checked = false;
    } else {
        currentImg.src = '';
        currentImg.classList.add('hidden');
        removeWrap.classList.add('hidden');
    }

    // Format datetime for input
    if (announcement.starts_at) {
        const startsAt = new Date(announcement.starts_at);
        document.getElementById('editAnnouncementStartsAt').value = startsAt.toISOString().slice(0, 16);
    } else {
        document.getElementById('editAnnouncementStartsAt').value = '';
    }

    if (announcement.ends_at) {
        const endsAt = new Date(announcement.ends_at);
        document.getElementById('editAnnouncementEndsAt').value = endsAt.toISOString().slice(0, 16);
    } else {
        document.getElementById('editAnnouncementEndsAt').value = '';
    }

    document.getElementById('editAnnouncementModal').classList.remove('hidden');
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
@endsection
