<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import ImageCropper from './ImageCropper.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        default: 'images', // all, images, documents
    },
});

const emit = defineEmits(['close', 'select']);

const currentFolder = ref('');
const parentFolder = ref('');
const folders = ref([]);
const files = ref([]);
const loading = ref(false);
const uploading = ref(false);
const uploadProgress = ref(0);
const selectedFile = ref(null);
const viewMode = ref('grid'); // grid, list
const searchQuery = ref('');
const showNewFolderDialog = ref(false);
const newFolderName = ref('');

// Drag & drop state
const isDragging = ref(false);

// Image cropper state
const showCropper = ref(false);
const cropperImage = ref(null);

// Load files when modal opens
watch(() => props.show, (show) => {
    if (show) {
        searchQuery.value = '';
        selectedFile.value = null;
        showCropper.value = false;
        cropperImage.value = null;
        loadFiles();
    }
});

const filteredFiles = computed(() => {
    if (!searchQuery.value) return files.value;
    const query = searchQuery.value.toLowerCase();
    return files.value.filter(f => f.name.toLowerCase().includes(query));
});

async function loadFiles(folder = '') {
    loading.value = true;
    try {
        const response = await axios.get(route('admin.media.index'), {
            params: { folder, type: props.type }
        });
        currentFolder.value = response.data.current_folder;
        parentFolder.value = response.data.parent_folder;
        folders.value = response.data.folders;
        files.value = response.data.files;
    } catch (error) {
        console.error('Failed to load files:', error);
    }
    loading.value = false;
}

function navigateToFolder(path) {
    loadFiles(path);
}

function goBack() {
    loadFiles(parentFolder.value);
}

function selectFile(file) {
    selectedFile.value = file;
}

function confirmSelection() {
    if (selectedFile.value) {
        emit('select', selectedFile.value);
        close();
    }
}

function close() {
    selectedFile.value = null;
    emit('close');
}

async function uploadFile(event) {
    const file = event.target?.files?.[0];
    if (!file) return;
    await uploadSingleFile(file);
    if (event.target) event.target.value = '';
}

async function uploadSingleFile(file) {
    uploading.value = true;
    uploadProgress.value = 0;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('folder', currentFolder.value || 'uploads');

    try {
        await axios.post(route('admin.media.upload'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress: (e) => {
                uploadProgress.value = Math.round((e.loaded * 100) / e.total);
            }
        });
        loadFiles(currentFolder.value);
    } catch (error) {
        console.error('Upload failed:', error);
    }
    uploading.value = false;
}

// Drag & drop handlers
function handleDragOver(e) {
    e.preventDefault();
    isDragging.value = true;
}

function handleDragLeave(e) {
    e.preventDefault();
    isDragging.value = false;
}

async function handleDrop(e) {
    e.preventDefault();
    isDragging.value = false;

    const files = e.dataTransfer?.files;
    if (!files || files.length === 0) return;

    // Upload all dropped files
    for (const file of files) {
        await uploadSingleFile(file);
    }
}

function clearSearch() {
    searchQuery.value = '';
}

async function deleteFile(file) {
    if (!confirm('Are you sure you want to delete this file?')) return;

    try {
        await axios.post(route('admin.media.delete'), { path: file.path });
        if (selectedFile.value?.path === file.path) {
            selectedFile.value = null;
        }
        loadFiles(currentFolder.value);
    } catch (error) {
        console.error('Delete failed:', error);
    }
}

async function createFolder() {
    if (!newFolderName.value.trim()) return;

    try {
        await axios.post(route('admin.media.create-folder'), {
            name: newFolderName.value,
            parent: currentFolder.value
        });
        newFolderName.value = '';
        showNewFolderDialog.value = false;
        loadFiles(currentFolder.value);
    } catch (error) {
        console.error('Create folder failed:', error);
    }
}

function formatDate(timestamp) {
    return new Date(timestamp * 1000).toLocaleDateString();
}

function openCropper(file) {
    if (!file.is_image) return;
    cropperImage.value = file;
    showCropper.value = true;
}

function closeCropper() {
    showCropper.value = false;
    cropperImage.value = null;
}

async function onCropSave(newFile) {
    // Close cropper first
    closeCropper();

    // Reload files to show the new cropped image
    await loadFiles(currentFolder.value);

    // Select the newly cropped file
    if (newFile?.url) {
        selectedFile.value = newFile;
    }
}
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/50" @click="close"></div>

            <!-- Modal -->
            <div
                class="relative bg-white rounded-xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col mx-4"
                @dragover="handleDragOver"
                @dragleave="handleDragLeave"
                @drop="handleDrop"
            >
                <!-- Drag & Drop Overlay -->
                <div
                    v-if="isDragging"
                    class="absolute inset-0 z-50 bg-indigo-500/90 rounded-xl flex flex-col items-center justify-center pointer-events-none"
                >
                    <svg class="w-20 h-20 text-white mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-white text-xl font-semibold">Drop files to upload</p>
                    <p class="text-white/80 text-sm mt-1">Release to start uploading</p>
                </div>
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold">Media Browser</h2>
                        <span v-if="currentFolder" class="text-sm text-gray-500">/{{ currentFolder }}</span>
                    </div>
                    <button @click="close" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Toolbar -->
                <div class="flex items-center justify-between p-3 border-b border-gray-100 bg-gray-50">
                    <div class="flex items-center gap-2">
                        <!-- Back button -->
                        <button
                            v-if="currentFolder"
                            @click="goBack"
                            class="flex items-center gap-1 px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded-lg transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Back
                        </button>

                        <!-- Upload -->
                        <label class="flex items-center gap-1 px-3 py-1.5 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg cursor-pointer transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Upload
                            <input type="file" class="hidden" @change="uploadFile" :accept="type === 'images' ? 'image/*' : '*'" />
                        </label>

                        <!-- New Folder -->
                        <button
                            @click="showNewFolderDialog = true"
                            class="flex items-center gap-1 px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 bg-gray-200 hover:bg-gray-300 rounded-lg transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                            New Folder
                        </button>

                        <!-- Refresh -->
                        <button @click="loadFiles(currentFolder)" class="p-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-200 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Search -->
                        <div class="relative">
                            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search files..."
                                class="pl-8 pr-8 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent w-48"
                            />
                            <button
                                v-if="searchQuery"
                                @click="clearSearch"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-0.5 text-gray-400 hover:text-gray-600 rounded"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- View Toggle -->
                        <div class="flex items-center bg-gray-200 rounded-lg p-0.5">
                            <button
                                @click="viewMode = 'grid'"
                                class="p-1.5 rounded transition"
                                :class="viewMode === 'grid' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            </button>
                            <button
                                @click="viewMode = 'list'"
                                class="p-1.5 rounded transition"
                                :class="viewMode === 'list' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Upload Progress -->
                <div v-if="uploading" class="px-4 py-2 bg-indigo-50 border-b border-indigo-100">
                    <div class="flex items-center gap-3">
                        <div class="flex-1 h-2 bg-indigo-200 rounded-full overflow-hidden">
                            <div class="h-full bg-indigo-600 transition-all" :style="{ width: uploadProgress + '%' }"></div>
                        </div>
                        <span class="text-sm text-indigo-600 font-medium">{{ uploadProgress }}%</span>
                    </div>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-auto p-4 min-h-[400px]">
                    <!-- Loading -->
                    <div v-if="loading" class="flex items-center justify-center h-full">
                        <svg class="w-8 h-8 text-indigo-600 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="!folders.length && !filteredFiles.length" class="flex flex-col items-center justify-center h-full text-gray-400">
                        <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                        <p class="text-lg font-medium">No files found</p>
                        <p class="text-sm">Upload some files to get started</p>
                    </div>

                    <!-- Grid View -->
                    <div v-else-if="viewMode === 'grid'">
                        <!-- Folders -->
                        <div v-if="folders.length" class="mb-6">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Folders</h3>
                            <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-3">
                                <button
                                    v-for="folder in folders"
                                    :key="folder.path"
                                    @click="navigateToFolder(folder.path)"
                                    class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 transition group"
                                >
                                    <svg class="w-12 h-12 text-amber-500 mb-1" fill="currentColor" viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                                    <span class="text-xs text-gray-700 truncate max-w-full">{{ folder.name }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Files -->
                        <div v-if="filteredFiles.length">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Files</h3>
                            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3">
                                <div
                                    v-for="file in filteredFiles"
                                    :key="file.path"
                                    @click="selectFile(file)"
                                    class="relative group cursor-pointer rounded-lg border-2 transition overflow-hidden"
                                    :class="selectedFile?.path === file.path ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-transparent hover:border-gray-300'"
                                >
                                    <!-- Image Preview -->
                                    <div v-if="file.is_image" class="aspect-square bg-gray-100">
                                        <img :src="file.thumbnail || file.url" :alt="file.name" class="w-full h-full object-cover" />
                                    </div>

                                    <!-- File Icon -->
                                    <div v-else class="aspect-square bg-gray-100 flex items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>

                                    <!-- File Info -->
                                    <div class="p-2">
                                        <p class="text-xs text-gray-700 truncate" :title="file.name">{{ file.name }}</p>
                                        <p class="text-xs text-gray-400">{{ file.size }}</p>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="absolute top-1 right-1 flex gap-1 opacity-0 group-hover:opacity-100 transition">
                                        <!-- Edit Button (for images) -->
                                        <button
                                            v-if="file.is_image"
                                            @click.stop="openCropper(file)"
                                            class="p-1 bg-indigo-500 text-white rounded-full hover:bg-indigo-600 transition"
                                            title="Edit Image"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <!-- Delete Button -->
                                        <button
                                            @click.stop="deleteFile(file)"
                                            class="p-1 bg-red-500 text-white rounded-full hover:bg-red-600 transition"
                                            title="Delete"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>

                                    <!-- Selection Indicator -->
                                    <div v-if="selectedFile?.path === file.path" class="absolute top-1 left-1 p-1 bg-indigo-500 text-white rounded-full">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- List View -->
                    <div v-else class="space-y-1">
                        <!-- Folders -->
                        <button
                            v-for="folder in folders"
                            :key="folder.path"
                            @click="navigateToFolder(folder.path)"
                            class="flex items-center gap-3 w-full px-3 py-2 rounded-lg hover:bg-gray-100 transition"
                        >
                            <svg class="w-8 h-8 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                            <span class="text-sm text-gray-700">{{ folder.name }}</span>
                        </button>

                        <!-- Files -->
                        <div
                            v-for="file in filteredFiles"
                            :key="file.path"
                            @click="selectFile(file)"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg cursor-pointer transition"
                            :class="selectedFile?.path === file.path ? 'bg-indigo-50 ring-1 ring-indigo-200' : 'hover:bg-gray-50'"
                        >
                            <div v-if="file.is_image" class="w-10 h-10 rounded overflow-hidden bg-gray-100 flex-shrink-0">
                                <img :src="file.thumbnail || file.url" :alt="file.name" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="w-10 h-10 rounded bg-gray-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-700 truncate">{{ file.name }}</p>
                                <p class="text-xs text-gray-400">{{ file.size }} - {{ formatDate(file.modified) }}</p>
                            </div>
                            <!-- Edit button for images -->
                            <button
                                v-if="file.is_image"
                                @click.stop="openCropper(file)"
                                class="p-1.5 text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 rounded transition"
                                title="Edit Image"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button
                                @click.stop="deleteFile(file)"
                                class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded transition"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer with Selection Info -->
                <div class="flex items-center justify-between p-4 border-t border-gray-200 bg-gray-50">
                    <div class="text-sm text-gray-500">
                        <span v-if="selectedFile">
                            Selected: <strong>{{ selectedFile.name }}</strong>
                        </span>
                        <span v-else>No file selected</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="close" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded-lg transition">
                            Cancel
                        </button>
                        <button
                            @click="confirmSelection"
                            :disabled="!selectedFile"
                            class="px-4 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Select File
                        </button>
                    </div>
                </div>
            </div>

            <!-- New Folder Dialog -->
            <div v-if="showNewFolderDialog" class="fixed inset-0 z-[110] flex items-center justify-center">
                <div class="absolute inset-0 bg-black/30" @click="showNewFolderDialog = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl p-6 w-full max-w-sm mx-4">
                    <h3 class="text-lg font-semibold mb-4">Create New Folder</h3>
                    <input
                        v-model="newFolderName"
                        type="text"
                        placeholder="Folder name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent mb-4"
                        @keyup.enter="createFolder"
                    />
                    <div class="flex justify-end gap-2">
                        <button @click="showNewFolderDialog = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">
                            Cancel
                        </button>
                        <button @click="createFolder" class="px-4 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
                            Create
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Cropper -->
        <ImageCropper
            :show="showCropper"
            :image="cropperImage"
            @close="showCropper = false"
            @save="onCropSave"
        />
    </Teleport>
</template>