<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import { NodeViewWrapper } from '@tiptap/vue-3';

const props = defineProps({
    node: {
        type: Object,
        required: true,
    },
    updateAttributes: {
        type: Function,
        required: true,
    },
    selected: {
        type: Boolean,
        default: false,
    },
    editor: {
        type: Object,
        required: true,
    },
    getPos: {
        type: Function,
        required: true,
    },
});

const resizing = ref(false);
const rotating = ref(false);
const startX = ref(0);
const startWidth = ref(0);
const startAngle = ref(0);
const centerX = ref(0);
const centerY = ref(0);
const imageRef = ref(null);

const rotation = computed(() => {
    const rot = props.node.attrs.rotation || 0;
    // Show 180° instead of -180° (they're equivalent)
    return rot === -180 ? 180 : rot;
});

const imageStyle = computed(() => {
    const style = {};
    const width = props.node.attrs.width;
    if (width) {
        style.width = typeof width === 'number' ? `${width}px` : width;
    }
    return style;
});

const innerImageStyle = computed(() => {
    const rot = props.node.attrs.rotation || 0;
    if (rot !== 0) {
        return { transform: `rotate(${rot}deg)` };
    }
    return {};
});

const alignment = computed(() => props.node.attrs.alignment || 'center');

const wrapperClass = computed(() => {
    const classes = ['resizable-image-wrapper'];
    if (props.selected) classes.push('selected');
    if (resizing.value) classes.push('resizing');
    if (rotating.value) classes.push('rotating');

    switch (alignment.value) {
        case 'left':
            classes.push('align-left');
            break;
        case 'right':
            classes.push('align-right');
            break;
        default:
            classes.push('align-center');
    }

    return classes.join(' ');
});

// Resize functionality
function startResize(e, direction) {
    e.preventDefault();
    e.stopPropagation();

    resizing.value = true;
    startX.value = e.clientX;
    startWidth.value = imageRef.value?.offsetWidth || 300;

    document.addEventListener('mousemove', onResize);
    document.addEventListener('mouseup', stopResize);
}

function onResize(e) {
    if (!resizing.value) return;

    const diff = e.clientX - startX.value;
    const newWidth = Math.max(100, startWidth.value + diff);

    props.updateAttributes({ width: newWidth });
}

function stopResize() {
    resizing.value = false;
    document.removeEventListener('mousemove', onResize);
    document.removeEventListener('mouseup', stopResize);
}

// Rotation functionality
function startRotate(e) {
    e.preventDefault();
    e.stopPropagation();

    rotating.value = true;

    // Get center of image
    const rect = imageRef.value?.getBoundingClientRect();
    if (rect) {
        centerX.value = rect.left + rect.width / 2;
        centerY.value = rect.top + rect.height / 2;
    }

    // Calculate starting angle
    startAngle.value = (props.node.attrs.rotation || 0) - getAngle(e.clientX, e.clientY);

    document.addEventListener('mousemove', onRotate);
    document.addEventListener('mouseup', stopRotate);
}

function getAngle(x, y) {
    const deltaX = x - centerX.value;
    const deltaY = y - centerY.value;
    const rad = Math.atan2(deltaY, deltaX);
    return rad * (180 / Math.PI);
}

function onRotate(e) {
    if (!rotating.value) return;

    let angle = startAngle.value + getAngle(e.clientX, e.clientY);

    // Snap to 15 degree increments when holding shift
    if (e.shiftKey) {
        angle = Math.round(angle / 15) * 15;
    }

    // Normalize angle to -180 to 180
    while (angle > 180) angle -= 360;
    while (angle < -180) angle += 360;

    props.updateAttributes({ rotation: Math.round(angle) });
}

function stopRotate() {
    rotating.value = false;
    document.removeEventListener('mousemove', onRotate);
    document.removeEventListener('mouseup', stopRotate);
}

function resetRotation() {
    props.updateAttributes({ rotation: 0 });
}

function rotateBy(degrees) {
    const current = props.node.attrs.rotation || 0;
    let newRotation = current + degrees;
    while (newRotation > 180) newRotation -= 360;
    while (newRotation < -180) newRotation += 360;
    props.updateAttributes({ rotation: newRotation });
}

function setAlignment(align) {
    props.updateAttributes({ alignment: align });
}

function deleteImage() {
    const pos = props.getPos();
    props.editor.chain().focus().deleteRange({ from: pos, to: pos + 1 }).run();
}

onBeforeUnmount(() => {
    document.removeEventListener('mousemove', onResize);
    document.removeEventListener('mouseup', stopResize);
    document.removeEventListener('mousemove', onRotate);
    document.removeEventListener('mouseup', stopRotate);
});
</script>

<template>
    <NodeViewWrapper :class="wrapperClass">
        <div class="resizable-image-container" :style="imageStyle" ref="imageRef">
            <img
                :src="node.attrs.src"
                :alt="node.attrs.alt || ''"
                :title="node.attrs.title || ''"
                :style="innerImageStyle"
                draggable="false"
            />

            <!-- Controls when selected -->
            <template v-if="selected">
                <!-- Resize handles -->
                <div class="resize-handle resize-handle-e" @mousedown="startResize($event, 'e')"></div>
                <div class="resize-handle resize-handle-w" @mousedown="startResize($event, 'w')"></div>

                <!-- Rotation handle -->
                <div class="rotate-handle-line"></div>
                <div class="rotate-handle" @mousedown="startRotate" title="Drag to rotate (Shift for 15° snap)">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>

                <!-- Toolbar -->
                <div class="image-toolbar">
                    <!-- Drag handle -->
                    <div class="drag-handle" data-drag-handle title="Drag to move">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                        </svg>
                    </div>

                    <!-- Divider -->
                    <div class="toolbar-divider"></div>

                    <!-- Alignment buttons -->
                    <button
                        type="button"
                        @click="setAlignment('left')"
                        :class="{ active: alignment === 'left' }"
                        title="Align Left"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h16"/>
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="setAlignment('center')"
                        :class="{ active: alignment === 'center' }"
                        title="Align Center"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M4 18h16"/>
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="setAlignment('right')"
                        :class="{ active: alignment === 'right' }"
                        title="Align Right"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M10 12h10M4 18h16"/>
                        </svg>
                    </button>

                    <!-- Divider -->
                    <div class="toolbar-divider"></div>

                    <!-- Rotation buttons -->
                    <button
                        type="button"
                        @click="rotateBy(-90)"
                        title="Rotate Left 90°"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="rotateBy(90)"
                        title="Rotate Right 90°"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6"/>
                        </svg>
                    </button>
                    <button
                        v-if="rotation !== 0"
                        type="button"
                        @click="resetRotation"
                        title="Reset Rotation"
                        class="reset-btn"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    <!-- Divider -->
                    <div class="toolbar-divider"></div>

                    <!-- Delete button -->
                    <button
                        type="button"
                        @click="deleteImage"
                        title="Изтрий изображението"
                        class="delete-btn"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>

                <!-- Rotation indicator -->
                <div v-if="rotation !== 0" class="rotation-indicator">
                    {{ rotation }}°
                </div>
            </template>
        </div>
    </NodeViewWrapper>
</template>

<style scoped>
.resizable-image-wrapper {
    display: flex;
    margin: 1rem 0;
    padding: 80px 0 60px 0;
}

.resizable-image-wrapper.align-left {
    justify-content: flex-start;
}

.resizable-image-wrapper.align-center {
    justify-content: center;
}

.resizable-image-wrapper.align-right {
    justify-content: flex-end;
}

.resizable-image-container {
    position: relative;
    display: inline-block;
    max-width: 100%;
    line-height: 0;
}

.resizable-image-container img {
    display: block;
    width: 100%;
    height: auto;
    border-radius: 4px;
    transition: transform 0.1s ease-out;
}

.resizable-image-wrapper.selected .resizable-image-container {
    outline: 2px solid #6366f1;
    outline-offset: 2px;
}

/* Resize handles */
.resize-handle {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 12px;
    height: 40px;
    background: #6366f1;
    border-radius: 4px;
    cursor: ew-resize;
    opacity: 0;
    transition: opacity 0.15s;
}

.resizable-image-wrapper.selected .resize-handle,
.resizable-image-wrapper.resizing .resize-handle {
    opacity: 1;
}

.resize-handle-e {
    right: -6px;
}

.resize-handle-w {
    left: -6px;
}

.resize-handle:hover {
    background: #4f46e5;
}

/* Rotation handle */
.rotate-handle-line {
    position: absolute;
    top: -30px;
    left: 50%;
    width: 2px;
    height: 20px;
    background: #6366f1;
    transform: translateX(-50%);
    opacity: 0;
    transition: opacity 0.15s;
}

.rotate-handle {
    position: absolute;
    top: -45px;
    left: 50%;
    transform: translateX(-50%);
    width: 24px;
    height: 24px;
    background: #6366f1;
    border-radius: 50%;
    cursor: grab;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    opacity: 0;
    transition: opacity 0.15s, background 0.15s;
}

.rotate-handle:hover {
    background: #4f46e5;
}

.rotate-handle:active {
    cursor: grabbing;
}

.resizable-image-wrapper.selected .rotate-handle,
.resizable-image-wrapper.selected .rotate-handle-line,
.resizable-image-wrapper.rotating .rotate-handle,
.resizable-image-wrapper.rotating .rotate-handle-line {
    opacity: 1;
}

/* Rotation indicator */
.rotation-indicator {
    position: absolute;
    top: -70px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.75);
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    white-space: nowrap;
}

/* Toolbar */
.image-toolbar {
    position: absolute;
    bottom: -45px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 2px;
    padding: 4px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    z-index: 10;
}

.image-toolbar button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border: none;
    background: transparent;
    border-radius: 4px;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.15s;
}

.image-toolbar button:hover {
    background: #f3f4f6;
    color: #374151;
}

.image-toolbar button.active {
    background: #6366f1;
    color: white;
}

.image-toolbar button.reset-btn {
    color: #ef4444;
}

.image-toolbar button.reset-btn:hover {
    background: #fef2f2;
    color: #dc2626;
}

.image-toolbar button.delete-btn {
    color: #ef4444;
}

.image-toolbar button.delete-btn:hover {
    background: #fef2f2;
    color: #dc2626;
}

.drag-handle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border: none;
    background: transparent;
    border-radius: 4px;
    cursor: grab;
    color: #6b7280;
    transition: all 0.15s;
}

.drag-handle:hover {
    background: #f3f4f6;
    color: #374151;
}

.drag-handle:active {
    cursor: grabbing;
    background: #e5e7eb;
}

.toolbar-divider {
    width: 1px;
    height: 20px;
    background: #e5e7eb;
    margin: 4px 4px;
}

/* Active states */
.resizable-image-wrapper.resizing,
.resizable-image-wrapper.rotating {
    user-select: none;
}

.resizable-image-wrapper.resizing .resizable-image-container,
.resizable-image-wrapper.rotating .resizable-image-container {
    outline-color: #4f46e5;
}
</style>