<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import debounce from 'lodash/debounce';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'select']);

const searchQuery = ref('');
const searchType = ref('all');
const results = ref([]);
const loading = ref(false);
const popularItems = ref([]);
const activePopularTab = ref('products');

// Type badges colors
const typeColors = {
    product: 'bg-indigo-100 text-indigo-700',
    category: 'bg-green-100 text-green-700',
    blog: 'bg-purple-100 text-purple-700',
    page: 'bg-amber-100 text-amber-700',
};

// Type icons
const typeIcons = {
    product: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
    category: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>',
    blog: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>',
    page: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
};

// Load popular items when modal opens
watch(() => props.show, (show) => {
    if (show) {
        loadPopular(activePopularTab.value);
        searchQuery.value = '';
        results.value = [];
    }
});

// Debounced search
const debouncedSearch = debounce(async () => {
    if (searchQuery.value.length < 2) {
        results.value = [];
        return;
    }

    loading.value = true;
    try {
        const response = await axios.get(route('admin.content-search.search'), {
            params: {
                q: searchQuery.value,
                type: searchType.value,
                limit: 15,
            }
        });
        results.value = response.data.results;
    } catch (error) {
        console.error('Search failed:', error);
    }
    loading.value = false;
}, 300);

watch(searchQuery, () => {
    debouncedSearch();
});

watch(searchType, () => {
    if (searchQuery.value.length >= 2) {
        debouncedSearch();
    }
});

async function loadPopular(type) {
    activePopularTab.value = type;
    loading.value = true;
    try {
        const response = await axios.get(route('admin.content-search.popular'), {
            params: { type, limit: 8 }
        });
        popularItems.value = response.data.results;
    } catch (error) {
        console.error('Failed to load popular items:', error);
    }
    loading.value = false;
}

function selectItem(item) {
    emit('select', item);
    close();
}

function close() {
    emit('close');
}

function insertAsLink(item) {
    emit('select', {
        ...item,
        insertType: 'link',
        html: `<a href="${item.url}" class="text-indigo-600 hover:text-indigo-800 underline">${item.name}</a>`,
    });
    close();
}

// Card templates for different content types
const cardTemplates = {
    product: {
        'card-basic': (item) => `<div class="bg-white rounded-xl shadow-lg overflow-hidden max-w-sm hover:shadow-xl transition">
  ${item.image ? `<img src="${item.image}" alt="${item.name}" class="w-full h-48 object-cover" />` : '<div class="w-full h-48 bg-gray-100 flex items-center justify-center"><svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>'}
  <div class="p-4">
    <h3 class="font-bold text-lg text-gray-900 mb-2">${item.name}</h3>
    ${item.price ? `<p class="text-xl font-bold text-indigo-600 mb-4">${item.price}</p>` : ''}
    <a href="${item.url}" class="inline-flex items-center justify-center w-full px-4 py-2.5 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      View Product
    </a>
  </div>
</div>`,
        'card-horizontal': (item) => `<div class="bg-white rounded-xl shadow-lg overflow-hidden flex max-w-xl hover:shadow-xl transition">
  ${item.image ? `<img src="${item.image}" alt="${item.name}" class="w-48 h-auto object-cover flex-shrink-0" />` : '<div class="w-48 bg-gray-100 flex items-center justify-center flex-shrink-0"><svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>'}
  <div class="p-6 flex flex-col justify-center">
    <h3 class="font-bold text-xl text-gray-900 mb-2">${item.name}</h3>
    ${item.price ? `<p class="text-2xl font-bold text-indigo-600 mb-4">${item.price}</p>` : ''}
    <a href="${item.url}" class="inline-flex items-center px-5 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">View Product</a>
  </div>
</div>`,
        'card-minimal': (item) => `<a href="${item.url}" class="block bg-white rounded-lg border border-gray-200 overflow-hidden hover:border-indigo-300 hover:shadow-md transition group">
  ${item.image ? `<div class="aspect-square overflow-hidden"><img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" /></div>` : '<div class="aspect-square bg-gray-50 flex items-center justify-center"><svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>'}
  <div class="p-3 text-center">
    <h3 class="font-medium text-gray-900 text-sm">${item.name}</h3>
    ${item.price ? `<p class="font-bold text-indigo-600 mt-1">${item.price}</p>` : ''}
  </div>
</a>`,
        'card-featured': (item) => `<div class="relative bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl overflow-hidden max-w-md shadow-xl">
  <div class="absolute top-4 left-4 bg-white/20 backdrop-blur-sm text-white text-xs font-bold px-3 py-1 rounded-full">Featured</div>
  ${item.image ? `<img src="${item.image}" alt="${item.name}" class="w-full h-56 object-cover opacity-90" />` : '<div class="w-full h-56 bg-white/10"></div>'}
  <div class="p-6 text-white">
    <h3 class="font-bold text-2xl mb-2">${item.name}</h3>
    ${item.price ? `<p class="text-3xl font-bold mb-4">${item.price}</p>` : ''}
    <a href="${item.url}" class="inline-flex items-center px-6 py-3 bg-white text-indigo-600 font-bold rounded-lg hover:bg-gray-100 transition shadow-lg">
      Shop Now
      <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
    </a>
  </div>
</div>`,
    },
    category: {
        'card-basic': (item) => `<a href="${item.url}" class="block bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition group">
  ${item.image ? `<div class="h-40 overflow-hidden"><img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" /></div>` : '<div class="h-40 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center"><svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg></div>'}
  <div class="p-4 text-center">
    <h3 class="font-bold text-lg text-gray-900">${item.name}</h3>
    <p class="text-sm text-indigo-600 mt-1">Browse Category &rarr;</p>
  </div>
</a>`,
        'card-overlay': (item) => `<a href="${item.url}" class="block relative rounded-xl overflow-hidden group h-48">
  ${item.image ? `<img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500" />` : '<div class="w-full h-full bg-gradient-to-br from-indigo-500 to-purple-600"></div>'}
  <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
  <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
    <h3 class="font-bold text-xl">${item.name}</h3>
    <p class="text-sm text-white/80 mt-1">Explore Collection</p>
  </div>
</a>`,
        'card-minimal': (item) => `<a href="${item.url}" class="flex items-center gap-4 p-4 bg-white rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition group">
  ${item.image ? `<img src="${item.image}" alt="${item.name}" class="w-16 h-16 rounded-lg object-cover" />` : '<div class="w-16 h-16 rounded-lg bg-indigo-100 flex items-center justify-center"><svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg></div>'}
  <div class="flex-1">
    <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition">${item.name}</h3>
    <p class="text-sm text-gray-500">View Products</p>
  </div>
  <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
</a>`,
    },
    blog: {
        'card-basic': (item) => `<article class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition max-w-sm">
  ${item.image ? `<img src="${item.image}" alt="${item.name}" class="w-full h-48 object-cover" />` : '<div class="w-full h-48 bg-gradient-to-br from-purple-100 to-indigo-100 flex items-center justify-center"><svg class="w-16 h-16 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg></div>'}
  <div class="p-5">
    <span class="inline-block px-3 py-1 bg-purple-100 text-purple-700 text-xs font-semibold rounded-full mb-3">Blog</span>
    <h3 class="font-bold text-lg text-gray-900 mb-3 line-clamp-2">${item.name}</h3>
    <a href="${item.url}" class="inline-flex items-center text-indigo-600 font-semibold hover:text-indigo-800 transition">
      Read Article
      <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
    </a>
  </div>
</article>`,
        'card-horizontal': (item) => `<article class="bg-white rounded-xl shadow-lg overflow-hidden flex hover:shadow-xl transition max-w-2xl">
  ${item.image ? `<img src="${item.image}" alt="${item.name}" class="w-72 h-auto object-cover flex-shrink-0" />` : '<div class="w-72 bg-gradient-to-br from-purple-100 to-indigo-100 flex items-center justify-center flex-shrink-0"><svg class="w-16 h-16 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg></div>'}
  <div class="p-6 flex flex-col justify-center">
    <span class="inline-block px-3 py-1 bg-purple-100 text-purple-700 text-xs font-semibold rounded-full mb-3 w-fit">Blog</span>
    <h3 class="font-bold text-xl text-gray-900 mb-3">${item.name}</h3>
    <a href="${item.url}" class="inline-flex items-center text-indigo-600 font-semibold hover:text-indigo-800 transition">
      Read Full Article
      <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
    </a>
  </div>
</article>`,
        'card-featured': (item) => `<article class="relative rounded-2xl overflow-hidden h-80 group">
  ${item.image ? `<img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />` : '<div class="w-full h-full bg-gradient-to-br from-purple-600 to-indigo-700"></div>'}
  <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
  <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
    <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur-sm text-white text-xs font-semibold rounded-full mb-3">Featured Post</span>
    <h3 class="font-bold text-2xl mb-3">${item.name}</h3>
    <a href="${item.url}" class="inline-flex items-center px-5 py-2 bg-white text-gray-900 font-semibold rounded-lg hover:bg-gray-100 transition">
      Read Now
    </a>
  </div>
</article>`,
    },
    page: {
        'card-basic': (item) => `<a href="${item.url}" class="block bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition max-w-sm group">
  <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center mb-4">
    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
  </div>
  <h3 class="font-bold text-lg text-gray-900 mb-2 group-hover:text-indigo-600 transition">${item.name}</h3>
  <p class="text-gray-500 text-sm">View Page &rarr;</p>
</a>`,
        'card-minimal': (item) => `<a href="${item.url}" class="flex items-center gap-4 p-4 bg-white rounded-lg border border-gray-200 hover:border-amber-300 hover:bg-amber-50 transition group">
  <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
  </div>
  <div class="flex-1">
    <h3 class="font-semibold text-gray-900 group-hover:text-amber-600 transition">${item.name}</h3>
    <p class="text-xs text-gray-500">${item.url}</p>
  </div>
  <svg class="w-5 h-5 text-gray-400 group-hover:text-amber-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
</a>`,
    },
};

// Selected card style
const selectedCardStyle = ref('card-basic');

// Get available card styles for item type
function getCardStyles(type) {
    return Object.keys(cardTemplates[type] || {});
}

// Get card style label
function getCardStyleLabel(style) {
    const labels = {
        'card-basic': 'Basic',
        'card-horizontal': 'Horizontal',
        'card-minimal': 'Minimal',
        'card-overlay': 'Overlay',
        'card-featured': 'Featured',
    };
    return labels[style] || style;
}

function insertAsCard(item, style = null) {
    const cardStyle = style || selectedCardStyle.value;
    const templates = cardTemplates[item.type];
    let cardHtml = '';

    if (templates && templates[cardStyle]) {
        cardHtml = templates[cardStyle](item);
    } else if (templates && templates['card-basic']) {
        cardHtml = templates['card-basic'](item);
    } else {
        // Fallback
        cardHtml = `<div class="bg-white rounded-xl shadow-lg p-6 max-w-sm">
  <span class="text-xs text-gray-500 font-semibold uppercase">${item.type_label}</span>
  <h3 class="font-bold text-lg mt-1">${item.name}</h3>
  <a href="${item.url}" class="mt-4 inline-block text-indigo-600 hover:underline">View &rarr;</a>
</div>`;
    }

    emit('select', {
        ...item,
        insertType: 'card',
        cardStyle: cardStyle,
        html: cardHtml,
    });
    close();
}

// Show card style picker
const showCardPicker = ref(false);
const cardPickerItem = ref(null);

function openCardPicker(item) {
    cardPickerItem.value = item;
    showCardPicker.value = true;
}

function closeCardPicker() {
    showCardPicker.value = false;
    cardPickerItem.value = null;
}

function insertAsButton(item) {
    const buttonHtml = `<a href="${item.url}" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition shadow-md hover:shadow-lg">${item.name}</a>`;
    emit('select', {
        ...item,
        insertType: 'button',
        html: buttonHtml,
    });
    close();
}
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/50" @click="close"></div>

            <!-- Modal -->
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col mx-4">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold">Insert Content Link</h2>
                    <button @click="close" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Search Bar -->
                <div class="p-4 border-b border-gray-100 bg-gray-50">
                    <div class="flex gap-3">
                        <div class="flex-1 relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search products, categories, blog posts..."
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                autofocus
                            />
                        </div>
                        <select
                            v-model="searchType"
                            class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="all">All Types</option>
                            <option value="products">Products</option>
                            <option value="categories">Categories</option>
                            <option value="blog">Blog Posts</option>
                            <option value="pages">Pages</option>
                        </select>
                    </div>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-auto p-4">
                    <!-- Loading -->
                    <div v-if="loading" class="flex items-center justify-center py-12">
                        <svg class="w-8 h-8 text-indigo-600 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- Search Results -->
                    <div v-else-if="searchQuery.length >= 2 && results.length > 0" class="space-y-2">
                        <p class="text-sm text-gray-500 mb-3">{{ results.length }} results found</p>
                        <div
                            v-for="item in results"
                            :key="`${item.type}-${item.id}`"
                            class="flex items-center gap-4 p-3 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition group"
                        >
                            <!-- Thumbnail -->
                            <div class="w-16 h-16 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                                <img v-if="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-cover" />
                                <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="typeIcons[item.type]"></svg>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="typeColors[item.type]">{{ item.type_label }}</span>
                                </div>
                                <h3 class="font-medium text-gray-900 truncate mt-1">{{ item.name }}</h3>
                                <p class="text-xs text-gray-500 truncate">{{ item.url }}</p>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
                                <button @click="insertAsLink(item)" class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-100 rounded-lg" title="Insert as Link">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                </button>
                                <button @click="insertAsButton(item)" class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-100 rounded-lg" title="Insert as Button">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                                </button>
                                <button @click="insertAsCard(item)" class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-100 rounded-lg" title="Insert as Card">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- No Results -->
                    <div v-else-if="searchQuery.length >= 2 && results.length === 0 && !loading" class="text-center py-12 text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <p>No results found for "{{ searchQuery }}"</p>
                    </div>

                    <!-- Popular Items (when no search) -->
                    <div v-else>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="text-sm font-medium text-gray-500">Quick insert:</span>
                            <div class="flex gap-1">
                                <button
                                    @click="loadPopular('products')"
                                    class="px-3 py-1 text-sm rounded-full transition"
                                    :class="activePopularTab === 'products' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100'"
                                >
                                    Products
                                </button>
                                <button
                                    @click="loadPopular('categories')"
                                    class="px-3 py-1 text-sm rounded-full transition"
                                    :class="activePopularTab === 'categories' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100'"
                                >
                                    Categories
                                </button>
                                <button
                                    @click="loadPopular('blog')"
                                    class="px-3 py-1 text-sm rounded-full transition"
                                    :class="activePopularTab === 'blog' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100'"
                                >
                                    Blog Posts
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div
                                v-for="item in popularItems"
                                :key="`${item.type}-${item.id}`"
                                class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition cursor-pointer group"
                            >
                                <!-- Thumbnail -->
                                <div class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                                    <img v-if="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="typeIcons[item.type]"></svg>
                                    </div>
                                </div>

                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-medium text-gray-900 text-sm truncate">{{ item.name }}</h3>
                                    <p v-if="item.price" class="text-xs text-indigo-600 font-medium">{{ item.price }}</p>
                                </div>

                                <!-- Quick Actions -->
                                <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition">
                                    <button @click="insertAsLink(item)" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-100 rounded" title="Link">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    </button>
                                    <button @click="insertAsCard(item)" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-100 rounded" title="Card">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p class="text-xs text-gray-400 text-center mt-4">
                            Start typing to search across all content types
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>