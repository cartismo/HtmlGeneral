<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import HtmlCodeEditor from '../../../Components/HtmlCodeEditor.vue';
import {
    ArrowLeftIcon,
    ArrowPathIcon,
    CodeBracketIcon,
    LanguageIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    languages: Array,
    positions: Array,
    stores: Array,
});

// Active tab for translations
const activeTab = ref(props.languages[0]?.code || 'en');

// Build initial translations object
const buildTranslations = () => {
    const translations = {};
    props.languages.forEach(lang => {
        translations[lang.code] = {
            title: '',
            content: '',
        };
    });
    return translations;
};

const form = useForm({
    store_id: null,
    code: '',
    is_active: true,
    sort_order: 0,
    translations: buildTranslations(),
});

const submit = () => {
    form.post(route('admin.general.html.store'));
};

// Validation
const hasValidTranslation = computed(() => {
    return Object.values(form.translations).some(t => t.title?.trim() || t.content?.trim());
});

// Check if translation tab has content
const tabHasContent = (langCode) => {
    const t = form.translations[langCode];
    return t?.title || t?.content;
};
</script>

<template>
    <AdminLayout title="Create HTML Block">
        <template #header>
            <div class="flex items-center">
                <Link :href="route('admin.general.html.index')" class="mr-4 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Create HTML Block</h1>
                    <p class="text-sm text-gray-500">Add a new multilingual HTML content block</p>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Settings -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-900">Block Settings</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Position -->
                        <div>
                            <InputLabel for="code" value="Position" />
                            <select
                                id="code"
                                v-model="form.code"
                                class="mt-2 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                required
                            >
                                <option value="">Select position...</option>
                                <option v-for="position in positions" :key="position.value" :value="position.value">
                                    {{ position.label }}
                                </option>
                            </select>
                            <p class="mt-1.5 text-xs text-gray-500">Where this block will appear in the theme</p>
                            <InputError :message="form.errors.code" class="mt-2" />
                        </div>

                        <!-- Store -->
                        <div>
                            <InputLabel for="store_id" value="Store" />
                            <select
                                id="store_id"
                                v-model="form.store_id"
                                class="mt-2 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                            >
                                <option :value="null">Global (All Stores)</option>
                                <option v-for="store in stores" :key="store.id" :value="store.id">
                                    {{ store.code }}
                                </option>
                            </select>
                            <p class="mt-1.5 text-xs text-gray-500">Leave empty for all stores or select a specific store</p>
                            <InputError :message="form.errors.store_id" class="mt-2" />
                        </div>

                        <!-- Sort Order -->
                        <div>
                            <InputLabel for="sort_order" value="Sort Order" />
                            <TextInput
                                id="sort_order"
                                v-model="form.sort_order"
                                type="number"
                                min="0"
                                class="mt-2 block w-full"
                            />
                            <p class="mt-1.5 text-xs text-gray-500">Lower numbers appear first</p>
                            <InputError :message="form.errors.sort_order" class="mt-2" />
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center pt-8">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                <span class="ms-3 text-sm font-medium text-gray-900">Active</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Translations -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <div class="flex items-center">
                        <LanguageIcon class="w-5 h-5 text-gray-400 mr-2" />
                        <h2 class="text-base font-semibold text-gray-900">Content</h2>
                    </div>
                    <p class="text-sm text-gray-500">Manage HTML content for each language</p>
                </div>

                <!-- Language Tabs -->
                <div class="border-b border-gray-200">
                    <div class="flex overflow-x-auto px-6">
                        <button
                            v-for="language in languages"
                            :key="language.code"
                            type="button"
                            @click="activeTab = language.code"
                            class="relative flex items-center px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                            :class="activeTab === language.code
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        >
                            <span :class="'fi fi-' + language.flag" class="mr-2 text-base"></span>
                            {{ language.name }}
                            <!-- Content indicator -->
                            <span
                                v-if="tabHasContent(language.code)"
                                class="ml-2 w-2 h-2 bg-green-500 rounded-full"
                            ></span>
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <div
                        v-for="language in languages"
                        :key="language.code"
                        v-show="activeTab === language.code"
                        class="space-y-6"
                    >
                        <!-- Title (optional) -->
                        <div>
                            <InputLabel :for="'title_' + language.code" value="Title (Admin Reference)" />
                            <TextInput
                                :id="'title_' + language.code"
                                v-model="form.translations[language.code].title"
                                type="text"
                                class="mt-2 block w-full"
                                :placeholder="'Block title in ' + language.name"
                            />
                            <p class="mt-1.5 text-xs text-gray-500">Optional title for admin reference (not shown on frontend)</p>
                            <InputError :message="form.errors['translations.' + language.code + '.title']" class="mt-2" />
                        </div>

                        <!-- HTML Content -->
                        <div>
                            <InputLabel :for="'content_' + language.code" value="HTML Content" />
                            <div class="mt-2">
                                <HtmlCodeEditor
                                    v-model="form.translations[language.code].content"
                                    :placeholder="'Enter HTML content in ' + language.name + '...'"
                                />
                            </div>
                            <InputError :message="form.errors['translations.' + language.code + '.content']" class="mt-2" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4">
                <Link
                    :href="route('admin.general.html.index')"
                    class="px-6 py-2.5 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition"
                >
                    Cancel
                </Link>
                <PrimaryButton
                    :disabled="form.processing || !form.code"
                    :class="{ 'opacity-50 cursor-not-allowed': !form.code }"
                >
                    <ArrowPathIcon
                        v-if="form.processing"
                        class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                    />
                    {{ form.processing ? 'Creating...' : 'Create Block' }}
                </PrimaryButton>
            </div>
        </form>
    </AdminLayout>
</template>