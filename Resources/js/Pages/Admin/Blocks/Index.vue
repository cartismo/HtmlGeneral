<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import {
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    MagnifyingGlassIcon,
    CodeBracketIcon,
    BuildingStorefrontIcon,
    FunnelIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    blocks: Object,
    positions: Array,
    stores: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const selectedPosition = ref(props.filters?.position || '');
const selectedStore = ref(props.filters?.store_id || '');
const selectedStatus = ref(props.filters?.status || '');

const applyFilters = () => {
    router.get(route('admin.general.html.index'), {
        search: search.value || undefined,
        position: selectedPosition.value || undefined,
        store_id: selectedStore.value || undefined,
        status: selectedStatus.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    selectedPosition.value = '';
    selectedStore.value = '';
    selectedStatus.value = '';
    router.get(route('admin.general.html.index'));
};

const hasFilters = computed(() => {
    return search.value || selectedPosition.value || selectedStore.value || selectedStatus.value;
});

const deleteBlock = (block) => {
    if (confirm('Are you sure you want to delete this HTML block?')) {
        router.delete(route('admin.general.html.destroy', block.id));
    }
};

const toggleStatus = (block) => {
    router.post(route('admin.general.html.toggle', block.id));
};

const getPositionLabel = (code) => {
    const position = props.positions.find(p => p.value === code);
    return position ? position.label : code;
};
</script>

<template>
    <AdminLayout title="HTML Blocks">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">HTML Blocks</h1>
                    <p class="text-sm text-gray-500">Manage multilingual HTML content for various theme positions</p>
                </div>
                <Link
                    :href="route('admin.general.html.create')"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition"
                >
                    <PlusIcon class="w-5 h-5 mr-2" />
                    Add Block
                </Link>
            </div>
        </template>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <div class="flex flex-wrap gap-4">
                <!-- Search -->
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search blocks..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                </div>

                <!-- Position Filter -->
                <select
                    v-model="selectedPosition"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    @change="applyFilters"
                >
                    <option value="">All Positions</option>
                    <option v-for="position in positions" :key="position.value" :value="position.value">
                        {{ position.label }}
                    </option>
                </select>

                <!-- Store Filter -->
                <select
                    v-model="selectedStore"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    @change="applyFilters"
                >
                    <option value="">All Stores</option>
                    <option value="global">Global (All Stores)</option>
                    <option v-for="store in stores" :key="store.id" :value="store.id">
                        {{ store.code }}
                    </option>
                </select>

                <!-- Status Filter -->
                <select
                    v-model="selectedStatus"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    @change="applyFilters"
                >
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>

                <!-- Apply / Clear -->
                <button
                    @click="applyFilters"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                >
                    <FunnelIcon class="w-5 h-5" />
                </button>
                <button
                    v-if="hasFilters"
                    @click="clearFilters"
                    class="px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition"
                >
                    Clear
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Block
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Position
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Store
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Order
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="block in blocks.data" :key="block.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-3">
                                    <CodeBracketIcon class="w-5 h-5 text-indigo-600" />
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ block.title || 'Untitled' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        ID: {{ block.id }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ getPositionLabel(block.code) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <BuildingStorefrontIcon class="w-4 h-4 mr-1" />
                                {{ block.store?.code || 'Global' }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <button
                                @click="toggleStatus(block)"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition"
                                :class="block.is_active
                                    ? 'bg-green-100 text-green-800 hover:bg-green-200'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            >
                                {{ block.is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ block.sort_order }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <Link
                                    :href="route('admin.general.html.edit', block.id)"
                                    class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                                >
                                    <PencilSquareIcon class="w-5 h-5" />
                                </Link>
                                <button
                                    @click="deleteBlock(block)"
                                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                >
                                    <TrashIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="blocks.data.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center">
                            <CodeBracketIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                            <p class="text-gray-500">No HTML blocks found</p>
                            <Link
                                :href="route('admin.general.html.create')"
                                class="inline-flex items-center mt-4 text-indigo-600 hover:text-indigo-800"
                            >
                                <PlusIcon class="w-5 h-5 mr-1" />
                                Create your first block
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="blocks.data.length > 0" class="px-6 py-4 border-t border-gray-200">
                <Pagination :links="blocks.links" />
            </div>
        </div>
    </AdminLayout>
</template>