<script setup>
import { ref, onBeforeUnmount, watch, nextTick } from 'vue';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    image: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'save']);

const imageRef = ref(null);
const cropper = ref(null);
const loading = ref(false);
const aspectRatio = ref(NaN);
const cropData = ref(null);
const isLoading = ref(true);
const loadError = ref(null);

const aspectRatios = [
    { label: 'Свободно', value: NaN },
    { label: '1:1', value: 1 },
    { label: '16:9', value: 16/9 },
    { label: '4:3', value: 4/3 },
    { label: '3:2', value: 3/2 },
    { label: '2:3', value: 2/3 },
    { label: '9:16', value: 9/16 },
];

watch(() => props.show, async (show) => {
    if (show && props.image) {
        isLoading.value = true;
        loadError.value = null;
        aspectRatio.value = NaN;
        cropData.value = null;
        await nextTick();
    } else {
        destroyCropper();
        isLoading.value = true;
        loadError.value = null;
    }
});

function onImageLoad() {
    isLoading.value = false;
    nextTick(() => {
        initCropper();
    });
}

function onImageError() {
    isLoading.value = false;
    loadError.value = 'Грешка при зареждане на изображението.';
}

function initCropper() {
    if (!imageRef.value) return;

    destroyCropper();

    cropper.value = new Cropper(imageRef.value, {
        aspectRatio: aspectRatio.value,
        viewMode: 1,
        dragMode: 'move',
        autoCropArea: 0.8,
        restore: false,
        guides: true,
        center: true,
        highlight: true,
        cropBoxMovable: true,
        cropBoxResizable: true,
        toggleDragModeOnDblclick: true,
        checkCrossOrigin: false,
        checkOrientation: true,
        ready() {
            updateCropData();
        },
        crop() {
            updateCropData();
        },
    });
}

function destroyCropper() {
    if (cropper.value) {
        cropper.value.destroy();
        cropper.value = null;
    }
}

function updateCropData() {
    if (cropper.value) {
        try {
            cropData.value = cropper.value.getData(true);
        } catch (e) {
            console.warn('Could not get crop data:', e);
        }
    }
}

function setAspectRatio(ratio) {
    aspectRatio.value = ratio;
    if (cropper.value) {
        cropper.value.setAspectRatio(ratio);
    }
}

function rotate(degrees) {
    if (cropper.value) {
        cropper.value.rotate(degrees);
    }
}

function flipHorizontal() {
    if (cropper.value) {
        const data = cropper.value.getData();
        cropper.value.scaleX(data.scaleX === -1 ? 1 : -1);
    }
}

function flipVertical() {
    if (cropper.value) {
        const data = cropper.value.getData();
        cropper.value.scaleY(data.scaleY === -1 ? 1 : -1);
    }
}

function zoomIn() {
    if (cropper.value) {
        cropper.value.zoom(0.1);
    }
}

function zoomOut() {
    if (cropper.value) {
        cropper.value.zoom(-0.1);
    }
}

function reset() {
    if (cropper.value) {
        cropper.value.reset();
    }
}

async function saveCrop() {
    if (!cropper.value) return;

    loading.value = true;

    try {
        const canvas = cropper.value.getCroppedCanvas({
            maxWidth: 2000,
            maxHeight: 2000,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        if (!canvas) {
            throw new Error('Неуспешно създаване на canvas');
        }

        const ext = (props.image.extension || props.image.name?.split('.').pop() || 'jpg').toLowerCase();
        let mimeType = 'image/jpeg';
        let quality = 0.92;
        let outputExt = 'jpg';

        if (ext === 'png') {
            mimeType = 'image/png';
            quality = undefined;
            outputExt = 'png';
        } else if (ext === 'webp') {
            mimeType = 'image/webp';
            outputExt = 'webp';
        }

        const blob = await new Promise((resolve, reject) => {
            canvas.toBlob((b) => {
                if (b) resolve(b);
                else reject(new Error('Неуспешно създаване на blob'));
            }, mimeType, quality);
        });

        const baseName = props.image.name?.replace(/\.[^.]+$/, '') || 'cropped';
        const filename = `${baseName}.${outputExt}`;

        const formData = new FormData();
        formData.append('file', blob, filename);
        formData.append('folder', getFolder());
        formData.append('original_name', props.image.name || '');

        const response = await axios.post(route('admin.media.crop'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        if (response.data?.success) {
            emit('save', response.data.file);
            close();
        } else {
            throw new Error(response.data?.message || 'Неуспешно запазване');
        }
    } catch (error) {
        console.error('Failed to save cropped image:', error);
        alert('Грешка при запазване: ' + (error.response?.data?.message || error.message));
    }

    loading.value = false;
}

function getFolder() {
    if (!props.image?.path) return 'uploads';
    const parts = props.image.path.split('/');
    parts.pop();
    return parts.join('/') || 'uploads';
}

function close() {
    destroyCropper();
    emit('close');
}

onBeforeUnmount(() => {
    destroyCropper();
});
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[110] flex items-center justify-center">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/70" @click="close"></div>

            <!-- Modal -->
            <div class="relative bg-gray-900 rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col mx-4">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-700">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold text-white">Редактиране на изображение</h2>
                        <span class="text-sm text-gray-400">{{ image?.name }}</span>
                    </div>
                    <button type="button" @click="close" class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Toolbar -->
                <div class="flex items-center justify-between p-3 border-b border-gray-700 bg-gray-800 flex-wrap gap-2">
                    <!-- Aspect Ratio -->
                    <div class="flex items-center gap-1 flex-wrap">
                        <span class="text-xs text-gray-400 mr-2">Размер:</span>
                        <button
                            v-for="ratio in aspectRatios"
                            :key="ratio.label"
                            type="button"
                            @click="setAspectRatio(ratio.value)"
                            class="px-2 py-1 text-xs rounded transition"
                            :class="(isNaN(aspectRatio) && isNaN(ratio.value)) || aspectRatio === ratio.value
                                ? 'bg-indigo-600 text-white'
                                : 'text-gray-300 hover:bg-gray-700'"
                        >
                            {{ ratio.label }}
                        </button>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1">
                        <button type="button" @click="rotate(-90)" class="p-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition" title="Завърти наляво">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        </button>
                        <button type="button" @click="rotate(90)" class="p-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition" title="Завърти надясно">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6"/></svg>
                        </button>

                        <div class="w-px h-6 bg-gray-600 mx-1"></div>

                        <button type="button" @click="flipHorizontal" class="p-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition" title="Обърни хоризонтално">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12M8 12h12M8 17h8m-8 4h8M4 3v18"/></svg>
                        </button>
                        <button type="button" @click="flipVertical" class="p-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition" title="Обърни вертикално">
                            <svg class="w-5 h-5 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12M8 12h12M8 17h8m-8 4h8M4 3v18"/></svg>
                        </button>

                        <div class="w-px h-6 bg-gray-600 mx-1"></div>

                        <button type="button" @click="zoomOut" class="p-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition" title="Намали">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"/></svg>
                        </button>
                        <button type="button" @click="zoomIn" class="p-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition" title="Увеличи">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                        </button>

                        <div class="w-px h-6 bg-gray-600 mx-1"></div>

                        <button type="button" @click="reset" class="p-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition" title="Нулиране">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Cropper Area -->
                <div class="flex-1 overflow-hidden bg-gray-950 min-h-[400px] flex items-center justify-center relative">
                    <!-- Loading state -->
                    <div v-if="isLoading && !loadError" class="absolute inset-0 flex items-center justify-center bg-gray-950">
                        <div class="flex flex-col items-center gap-3">
                            <svg class="w-8 h-8 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-gray-400">Зареждане...</span>
                        </div>
                    </div>

                    <!-- Error state -->
                    <div v-if="loadError" class="absolute inset-0 flex items-center justify-center bg-gray-950">
                        <div class="flex flex-col items-center gap-3 text-center px-4">
                            <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <p class="text-red-400">{{ loadError }}</p>
                            <button type="button" @click="close" class="mt-2 px-4 py-2 text-sm bg-gray-700 text-white rounded-lg hover:bg-gray-600">
                                Затвори
                            </button>
                        </div>
                    </div>

                    <img
                        v-if="image"
                        ref="imageRef"
                        :src="image.url"
                        :alt="image.name || 'Image'"
                        class="max-w-full max-h-full"
                        @load="onImageLoad"
                        @error="onImageError"
                    />
                </div>

                <!-- Crop Info -->
                <div v-if="cropData" class="px-4 py-2 bg-gray-800 border-t border-gray-700">
                    <div class="flex items-center gap-4 text-xs text-gray-400">
                        <span>Ширина: <strong class="text-gray-200">{{ cropData.width }}px</strong></span>
                        <span>Височина: <strong class="text-gray-200">{{ cropData.height }}px</strong></span>
                        <span>X: {{ cropData.x }}px</span>
                        <span>Y: {{ cropData.y }}px</span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-3 p-4 border-t border-gray-700 bg-gray-800">
                    <button type="button" @click="close" class="px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition">
                        Отказ
                    </button>
                    <button
                        type="button"
                        @click="saveCrop"
                        :disabled="loading || isLoading"
                        class="flex items-center gap-2 px-5 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ loading ? 'Запазване...' : 'Запази' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style>
/* Cropper.js custom styles */
.cropper-view-box,
.cropper-face {
    border-radius: 0;
}

.cropper-view-box {
    outline: 1px solid rgba(99, 102, 241, 0.75);
    outline-color: rgba(99, 102, 241, 0.75);
}

.cropper-line {
    background-color: rgba(99, 102, 241, 0.75);
}

.cropper-point {
    background-color: rgba(99, 102, 241, 1);
    width: 8px;
    height: 8px;
    opacity: 1;
}

.cropper-point.point-se {
    width: 10px;
    height: 10px;
}
</style>