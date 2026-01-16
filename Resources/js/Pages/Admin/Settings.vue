<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StoreSettingsTabs from '@/Components/Admin/StoreSettingsTabs.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import {
    ArrowPathIcon,
    CodeBracketIcon,
    Cog6ToothIcon,
    ArrowLeftIcon,
    InformationCircleIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';
import { CheckIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    module: Object,
    stores: Array,
    storeSettings: Object,
    defaultSettings: Object,
    config: Object,
});

const storeTabsRef = ref(null);
const saving = ref(false);

const submit = () => {
    if (!storeTabsRef.value) return;

    saving.value = true;
    router.put(route('admin.general.html.settings.update'), {
        store_id: storeTabsRef.value.activeStoreId,
        is_enabled: storeTabsRef.value.isEnabled,
        settings: storeTabsRef.value.localSettings,
    }, {
        preserveScroll: true,
        onFinish: () => {
            saving.value = false;
        },
    });
};

const hasChanges = computed(() => {
    return storeTabsRef.value?.hasChanges ?? false;
});

const positions = props.config?.positions || {};
</script>

<template>
    <AdminLayout title="HTML Blocks Settings">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('admin.modules.installed.index')"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div class="flex items-center space-x-3">
                        <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg shadow-indigo-500/25">
                            <CodeBracketIcon class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">HTML Blocks Settings</h1>
                            <p class="text-sm text-gray-500">Configure the HTML Blocks module</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('admin.general.html.index')"
                        class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
                    >
                        <CodeBracketIcon class="w-4 h-4 inline mr-2" />
                        Manage Blocks
                    </Link>
                    <span v-if="hasChanges" class="flex items-center text-sm text-amber-600 font-medium">
                        <span class="w-2 h-2 bg-amber-500 rounded-full mr-2 animate-pulse"></span>
                        Unsaved changes
                    </span>
                    <button
                        type="submit"
                        form="settings-form"
                        :disabled="saving"
                        class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl hover:from-indigo-600 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-indigo-500/25"
                    >
                        <CheckIcon class="w-4 h-4 inline mr-2" />
                        Save Changes
                    </button>
                </div>
            </div>
        </template>

        <form id="settings-form" @submit.prevent="submit">
            <StoreSettingsTabs
                ref="storeTabsRef"
                :stores="stores"
                :store-settings="storeSettings"
                :default-settings="defaultSettings"
                module-slug="html-general"
            >
                <template #default="{ store, settings, updateSetting, isEnabled }">
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        <!-- Left Sidebar -->
                        <div class="lg:col-span-1 space-y-6">
                            <!-- Status Card -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                                <div class="p-5 border-b border-gray-100">
                                    <h3 class="font-semibold text-gray-900">Module Status</h3>
                                </div>
                                <div class="p-5 space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Status</span>
                                        <span
                                            :class="settings.enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                            class="px-3 py-1 text-xs font-semibold rounded-full"
                                        >
                                            {{ settings.enabled ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Version</span>
                                        <span class="text-sm font-mono text-gray-900">v{{ module?.installed_version || '1.0.0' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Type</span>
                                        <span class="text-sm text-gray-900">General</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Help Card -->
                            <div class="bg-indigo-50 rounded-2xl p-5 border border-indigo-100">
                                <div class="flex items-start space-x-3">
                                    <InformationCircleIcon class="w-5 h-5 text-indigo-500 mt-0.5 flex-shrink-0" />
                                    <div>
                                        <h4 class="text-sm font-medium text-indigo-900">How to use</h4>
                                        <p class="text-sm text-indigo-700 mt-1">
                                            Create HTML blocks and assign them to positions. Use the HtmlBlockService in your theme to render them.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Side - Settings -->
                        <div class="lg:col-span-3 space-y-6">
                            <!-- Enable/Disable -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div :class="settings.enabled ? 'bg-green-100' : 'bg-gray-100'" class="p-3 rounded-xl transition-colors">
                                            <component :is="settings.enabled ? CheckCircleIcon : XCircleIcon"
                                                :class="settings.enabled ? 'text-green-600' : 'text-gray-400'"
                                                class="w-6 h-6"
                                            />
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900">Enable HTML Blocks Module</h3>
                                            <p class="text-sm text-gray-500">Show HTML blocks on your storefront</p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="updateSetting('enabled', !settings.enabled)"
                                        :class="settings.enabled ? 'bg-green-500' : 'bg-gray-300'"
                                        class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                                    >
                                        <span
                                            :class="settings.enabled ? 'translate-x-5' : 'translate-x-0'"
                                            class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                                        />
                                    </button>
                                </div>
                            </div>

                            <!-- Sort Order -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                    <div class="flex items-center space-x-3">
                                        <Cog6ToothIcon class="w-5 h-5 text-gray-400" />
                                        <h2 class="font-semibold text-gray-900">Display Settings</h2>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                                        <input
                                            type="number"
                                            :value="settings.sort_order"
                                            @input="updateSetting('sort_order', Number($event.target.value))"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            min="0"
                                        />
                                        <p class="mt-2 text-sm text-gray-500">Lower numbers display first when multiple blocks share a position.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Available Positions -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                    <h2 class="font-semibold text-gray-900">Available Positions</h2>
                                    <p class="text-sm text-gray-500">These positions can be used in your theme templates</p>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        <div
                                            v-for="(label, code) in positions"
                                            :key="code"
                                            class="flex items-center p-3 bg-gray-50 rounded-lg border border-gray-200"
                                        >
                                            <CodeBracketIcon class="w-5 h-5 text-indigo-500 mr-3 flex-shrink-0" />
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">{{ label }}</p>
                                                <p class="text-xs text-gray-500 font-mono">{{ code }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Usage Instructions -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                    <h2 class="font-semibold text-gray-900">Usage in Theme</h2>
                                </div>
                                <div class="p-6">
                                    <div class="prose prose-sm max-w-none">
                                        <p class="text-gray-600 mb-4">To display HTML blocks in your theme, use the HtmlBlockService:</p>
                                        <pre class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-sm"><code>// In your controller or view composer
use Modules\HtmlGeneral\Services\HtmlBlockService;

$htmlService = app(HtmlBlockService::class);

// Get content for a position
$content = $htmlService->getContent('footer_left', $storeId);

// Check if position has content
if ($htmlService->hasContent('header_top', $storeId)) {
    // Show the block
}

// In Blade template
{!! $htmlService->getContent('homepage_top') !!}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </StoreSettingsTabs>
        </form>
    </AdminLayout>
</template>