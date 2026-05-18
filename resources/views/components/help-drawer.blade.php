{{-- Help Drawer — sliding panel for contextual help. Loaded once in the client layout. --}}
<div id="help-drawer-root" x-data="helpDrawer()" x-cloak>

    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/30 z-[90]"
         @click="close()">
    </div>

    {{-- Drawer panel --}}
    <aside x-show="open"
           x-transition:enter="transform transition ease-out duration-300"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transform transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full"
           class="fixed inset-y-0 right-0 w-full sm:w-[500px] bg-white shadow-2xl z-[100] flex flex-col">

        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
            <div>
                <p class="text-xs uppercase tracking-wide text-indigo-600 font-semibold" x-text="article?.category_label || 'Help'"></p>
                <h2 class="text-lg font-semibold text-gray-900 mt-0.5" x-text="article?.title || 'Help'"></h2>
            </div>
            <button @click="close()" class="text-gray-400 hover:text-gray-600 p-1" aria-label="Close">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto px-6 py-5">
            <template x-if="loading">
                <div class="flex items-center justify-center py-16 text-gray-400">
                    <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"></circle><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"></path></svg>
                </div>
            </template>

            <template x-if="error">
                <div class="text-center py-16">
                    <p class="text-sm text-red-600" x-text="error"></p>
                    <a href="{{ route('client.help.index') }}" class="inline-block mt-3 text-sm text-indigo-600 hover:underline">Browse all help articles</a>
                </div>
            </template>

            <template x-if="!loading && !error && article">
                <div>
                    <p x-show="article.summary" class="text-base text-gray-600 mb-4" x-text="article.summary"></p>
                    <div class="prose prose-sm max-w-none" x-html="article.body"></div>
                    <div class="mt-6 pt-4 border-t border-gray-100 text-xs text-gray-400">
                        Last updated <span x-text="formatDate(article.updated_at)"></span>
                    </div>
                </div>
            </template>

            {{-- Default state when opened without a specific article --}}
            <template x-if="!loading && !error && !article">
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-indigo-100 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <h3 class="text-lg font-medium text-gray-900">Need help?</h3>
                    <p class="text-sm text-gray-500 mt-1 mb-4">Browse all guides or search for a topic.</p>
                    <a href="{{ route('client.help.index') }}" class="inline-block px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">Open Help Center</a>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-3 border-t border-gray-200 bg-gray-50 flex-shrink-0">
            <div class="flex items-center justify-between text-xs">
                <a href="{{ route('client.help.index') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">All help articles →</a>
                <span class="text-gray-500">Need more? <a href="tel:+15062714215" class="text-indigo-600 hover:underline">(506) 271-4215</a></span>
            </div>
        </div>
    </aside>
</div>

<style>
#help-drawer-root .prose h2 { font-size: 1.125rem; font-weight: 600; color: #111827; margin-top: 1.25rem; margin-bottom: 0.5rem; }
#help-drawer-root .prose h3 { font-size: 1rem; font-weight: 600; color: #111827; margin-top: 1rem; margin-bottom: 0.5rem; }
#help-drawer-root .prose p { color: #374151; line-height: 1.6; margin-bottom: 0.75rem; }
#help-drawer-root .prose ul, #help-drawer-root .prose ol { padding-left: 1.5rem; margin-bottom: 0.75rem; }
#help-drawer-root .prose ul { list-style-type: disc; }
#help-drawer-root .prose ol { list-style-type: decimal; }
#help-drawer-root .prose li { color: #374151; line-height: 1.6; margin-bottom: 0.25rem; }
#help-drawer-root .prose strong { font-weight: 600; color: #111827; }
#help-drawer-root .prose em { font-style: italic; }
#help-drawer-root .prose a { color: #4f46e5; text-decoration: underline; }
#help-drawer-root .prose code { background: #f3f4f6; padding: 2px 6px; border-radius: 3px; font-family: monospace; font-size: 0.85em; color: #be123c; }
#help-drawer-root .prose blockquote { border-left: 4px solid #c7d2fe; padding-left: 1rem; font-style: italic; color: #4b5563; margin: 1rem 0; }
#help-drawer-root .prose table { width: 100%; font-size: 0.875rem; margin: 1rem 0; border: 1px solid #e5e7eb; }
#help-drawer-root .prose th { background: #f9fafb; padding: 6px 10px; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb; }
#help-drawer-root .prose td { padding: 6px 10px; border-bottom: 1px solid #f3f4f6; }
#help-drawer-root .prose hr { margin: 1.25rem 0; border-color: #e5e7eb; }
</style>

<script>
function helpDrawer() {
    return {
        open: false,
        article: null,
        loading: false,
        error: null,

        init() {
            // Expose a global function for the help icon component to call
            window.openHelpDrawer = (slug) => this.show(slug);

            // Close on Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.open) this.close();
            });
        },

        async show(slug) {
            this.open = true;
            this.error = null;

            if (!slug) {
                this.article = null;
                return;
            }

            // Already loaded this article? Skip the fetch
            if (this.article?.slug === slug) return;

            this.loading = true;
            this.article = null;

            try {
                const resp = await fetch(`/client/help/api/article/${encodeURIComponent(slug)}`, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!resp.ok) throw new Error(resp.status === 404 ? 'Article not found.' : 'Could not load article.');
                this.article = await resp.json();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        close() {
            this.open = false;
        },

        formatDate(iso) {
            if (!iso) return '';
            try { return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); } catch { return ''; }
        },
    };
}
</script>
