<script setup>
import { ref, watch, onMounted, onBeforeUnmount, computed } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import { StarterKit } from '@tiptap/starter-kit';
import { Link } from '@tiptap/extension-link';
import { ResizableImageExtension } from '../Extensions/ResizableImageExtension.js';
import { TextAlign } from '@tiptap/extension-text-align';
import { Underline } from '@tiptap/extension-underline';
import { Placeholder } from '@tiptap/extension-placeholder';
import { TextStyle, Color } from '@tiptap/extension-text-style';
import { Highlight } from '@tiptap/extension-highlight';
import { Subscript } from '@tiptap/extension-subscript';
import { Superscript } from '@tiptap/extension-superscript';
import { Youtube } from '@tiptap/extension-youtube';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableCell } from '@tiptap/extension-table-cell';
import { TableHeader } from '@tiptap/extension-table-header';
import MediaBrowser from './MediaBrowser.vue';
import ContentLinkSearch from './ContentLinkSearch.vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Start writing...',
    },
});

const emit = defineEmits(['update:modelValue']);

// State
const mode = ref('visual');
const sourceCode = ref(props.modelValue);
const showColorPicker = ref(false);
const showHighlightPicker = ref(false);
const showTailwindPanel = ref(false);
const showMediaBrowser = ref(false);
const showContentSearch = ref(false);
const showHtmlWarning = ref(false);
const currentColor = ref('#000000');
const currentHighlight = ref('#ffff00');
const activeTab = ref('snippets'); // snippets, tailwind, elements


// Check if HTML contains complex structures that Tiptap can't handle
const hasComplexHtml = computed(() => {
    const html = sourceCode.value || '';
    // Check for divs, sections, custom classes, grid/flex layouts, etc.
    const complexPatterns = [
        /<div[\s>]/i,
        /<section[\s>]/i,
        /<article[\s>]/i,
        /<aside[\s>]/i,
        /<nav[\s>]/i,
        /<header[\s>]/i,
        /<footer[\s>]/i,
        /<figure[\s>]/i,
        /class="[^"]*(?:flex|grid|container|bg-|shadow|rounded|gap-|space-|px-|py-|mx-|my-)/i,
        /<style[\s>]/i,
    ];
    return complexPatterns.some(pattern => pattern.test(html));
});

// Editor setup
const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            heading: { levels: [1, 2, 3, 4, 5, 6] },
        }),
        Underline,
        Subscript,
        Superscript,
        TextStyle,
        Color,
        Highlight.configure({ multicolor: true }),
        Link.configure({
            openOnClick: false,
            HTMLAttributes: { class: 'text-indigo-600 underline hover:text-indigo-800' },
        }),
        ResizableImageExtension,
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Placeholder.configure({ placeholder: props.placeholder }),
        Youtube.configure({
            controls: true,
            nocookie: true,
            width: 640,
            height: 360,
        }),
        Table.configure({
            resizable: true,
            HTMLAttributes: { class: 'border-collapse border border-gray-300 w-full' },
        }),
        TableRow,
        TableHeader.configure({
            HTMLAttributes: { class: 'border border-gray-300 bg-gray-100 p-2 font-semibold text-left' },
        }),
        TableCell.configure({
            HTMLAttributes: { class: 'border border-gray-300 p-2' },
        }),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm max-w-none focus:outline-none min-h-[400px] px-4 py-3',
        },
    },
    onUpdate: ({ editor }) => {
        if (mode.value === 'visual') {
            const html = editor.getHTML();
            sourceCode.value = html;
            emit('update:modelValue', html);
        }
    },
});

watch(() => props.modelValue, (newValue) => {
    if (mode.value === 'visual') {
        if (editor.value && newValue !== editor.value.getHTML()) {
            editor.value.commands.setContent(newValue, false);
        }
    } else {
        sourceCode.value = newValue;
    }
});

onBeforeUnmount(() => {
    editor.value?.destroy();
});


// Mode switching
const switchMode = (newMode) => {
    if (newMode === mode.value) return;

    if (newMode === 'source') {
        // Switching to source mode - get HTML from editor
        if (editor.value) {
            sourceCode.value = editor.value.getHTML();
        }
        mode.value = newMode;
    } else {
        // Switching to visual mode - check for complex HTML
        if (hasComplexHtml.value) {
            showHtmlWarning.value = true;
        } else {
            applyVisualMode();
        }
    }
};

const applyVisualMode = () => {
    if (editor.value) {
        editor.value.commands.setContent(sourceCode.value, false);
    }
    mode.value = 'visual';
    showHtmlWarning.value = false;
};

const cancelVisualMode = () => {
    showHtmlWarning.value = false;
};

const onSourceChange = (event) => {
    const html = event.target.value;
    sourceCode.value = html;
    emit('update:modelValue', html);
};

// Editor actions
const setLink = () => {
    const previousUrl = editor.value?.getAttributes('link').href;
    const url = window.prompt('URL', previousUrl);
    if (url === null) return;
    if (url === '') {
        editor.value?.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }
    editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
};

const addImageUrl = () => {
    const url = window.prompt('Image URL');
    if (url) {
        const alt = window.prompt('Alt text (optional)', '');
        editor.value?.chain().focus().setImage({ src: url, alt: alt || '' }).run();
    }
};

const addYoutube = () => {
    const url = window.prompt('YouTube URL');
    if (url) {
        editor.value?.chain().focus().setYoutubeVideo({ src: url }).run();
    }
};

const setColor = (color) => {
    currentColor.value = color;
    editor.value?.chain().focus().setColor(color).run();
    showColorPicker.value = false;
};

const setHighlight = (color) => {
    currentHighlight.value = color;
    editor.value?.chain().focus().toggleHighlight({ color }).run();
    showHighlightPicker.value = false;
};

const insertTable = () => {
    editor.value?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
};

// Media browser
const onMediaSelect = (file) => {
    if (mode.value === 'source') {
        insertSnippet(`<img src="${file.url}" alt="${file.name}" class="max-w-full h-auto rounded-lg" />`);
    } else {
        editor.value?.chain().focus().setImage({ src: file.url, alt: file.name }).run();
    }
    showMediaBrowser.value = false;
};

// Content link search
const onContentSelect = (item) => {
    insertSnippet(item.html);
    showContentSearch.value = false;
};

// Insert snippet
const insertSnippet = (snippet) => {
    if (mode.value === 'source') {
        const textarea = document.getElementById('source-editor');
        if (textarea) {
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const newValue = sourceCode.value.substring(0, start) + snippet + sourceCode.value.substring(end);
            sourceCode.value = newValue;
            emit('update:modelValue', newValue);
            setTimeout(() => {
                textarea.selectionStart = textarea.selectionEnd = start + snippet.length;
                textarea.focus();
            }, 0);
        }
    } else {
        if (editor.value) {
            editor.value.chain().focus().insertContent(snippet).run();
        }
    }
};

// Colors
const colors = [
    '#000000', '#434343', '#666666', '#999999', '#b7b7b7', '#cccccc', '#d9d9d9', '#efefef', '#f3f3f3', '#ffffff',
    '#980000', '#ff0000', '#ff9900', '#ffff00', '#00ff00', '#00ffff', '#4a86e8', '#0000ff', '#9900ff', '#ff00ff',
    '#e6b8af', '#f4cccc', '#fce5cd', '#fff2cc', '#d9ead3', '#d0e0e3', '#c9daf8', '#cfe2f3', '#d9d2e9', '#ead1dc',
];

const highlightColors = [
    '#ffff00', '#00ff00', '#00ffff', '#ff00ff', '#ff0000', '#0000ff', '#ffa500', '#ffc0cb',
];

// Tailwind classes organized by category
const tailwindClasses = {
    spacing: {
        label: 'Spacing',
        classes: [
            { label: 'p-0', desc: 'No padding' },
            { label: 'p-2', desc: 'Padding 0.5rem' },
            { label: 'p-4', desc: 'Padding 1rem' },
            { label: 'p-6', desc: 'Padding 1.5rem' },
            { label: 'p-8', desc: 'Padding 2rem' },
            { label: 'px-4', desc: 'Horizontal padding' },
            { label: 'py-4', desc: 'Vertical padding' },
            { label: 'm-0', desc: 'No margin' },
            { label: 'm-4', desc: 'Margin 1rem' },
            { label: 'mx-auto', desc: 'Center horizontally' },
            { label: 'my-4', desc: 'Vertical margin' },
            { label: 'mt-4', desc: 'Margin top' },
            { label: 'mb-4', desc: 'Margin bottom' },
            { label: 'gap-2', desc: 'Gap 0.5rem' },
            { label: 'gap-4', desc: 'Gap 1rem' },
            { label: 'gap-6', desc: 'Gap 1.5rem' },
            { label: 'space-x-4', desc: 'Horizontal space' },
            { label: 'space-y-4', desc: 'Vertical space' },
        ]
    },
    layout: {
        label: 'Layout',
        classes: [
            { label: 'flex', desc: 'Flexbox' },
            { label: 'flex-col', desc: 'Flex column' },
            { label: 'flex-row', desc: 'Flex row' },
            { label: 'flex-wrap', desc: 'Flex wrap' },
            { label: 'items-center', desc: 'Align center' },
            { label: 'items-start', desc: 'Align start' },
            { label: 'items-end', desc: 'Align end' },
            { label: 'justify-center', desc: 'Justify center' },
            { label: 'justify-between', desc: 'Space between' },
            { label: 'justify-around', desc: 'Space around' },
            { label: 'grid', desc: 'Grid' },
            { label: 'grid-cols-2', desc: '2 columns' },
            { label: 'grid-cols-3', desc: '3 columns' },
            { label: 'grid-cols-4', desc: '4 columns' },
            { label: 'col-span-2', desc: 'Span 2 cols' },
            { label: 'container', desc: 'Container' },
            { label: 'block', desc: 'Block' },
            { label: 'inline-block', desc: 'Inline block' },
            { label: 'hidden', desc: 'Hidden' },
        ]
    },
    sizing: {
        label: 'Sizing',
        classes: [
            { label: 'w-full', desc: 'Full width' },
            { label: 'w-1/2', desc: '50% width' },
            { label: 'w-1/3', desc: '33% width' },
            { label: 'w-1/4', desc: '25% width' },
            { label: 'w-auto', desc: 'Auto width' },
            { label: 'max-w-sm', desc: 'Max width small' },
            { label: 'max-w-md', desc: 'Max width medium' },
            { label: 'max-w-lg', desc: 'Max width large' },
            { label: 'max-w-xl', desc: 'Max width xl' },
            { label: 'max-w-full', desc: 'Max full' },
            { label: 'h-auto', desc: 'Auto height' },
            { label: 'h-full', desc: 'Full height' },
            { label: 'h-screen', desc: 'Screen height' },
            { label: 'min-h-screen', desc: 'Min screen height' },
            { label: 'aspect-square', desc: 'Square aspect' },
            { label: 'aspect-video', desc: 'Video aspect' },
        ]
    },
    typography: {
        label: 'Typography',
        classes: [
            { label: 'text-xs', desc: 'Extra small' },
            { label: 'text-sm', desc: 'Small' },
            { label: 'text-base', desc: 'Base' },
            { label: 'text-lg', desc: 'Large' },
            { label: 'text-xl', desc: 'Extra large' },
            { label: 'text-2xl', desc: '2x large' },
            { label: 'text-3xl', desc: '3x large' },
            { label: 'text-4xl', desc: '4x large' },
            { label: 'font-thin', desc: 'Thin' },
            { label: 'font-light', desc: 'Light' },
            { label: 'font-normal', desc: 'Normal' },
            { label: 'font-medium', desc: 'Medium' },
            { label: 'font-semibold', desc: 'Semibold' },
            { label: 'font-bold', desc: 'Bold' },
            { label: 'italic', desc: 'Italic' },
            { label: 'underline', desc: 'Underline' },
            { label: 'line-through', desc: 'Strikethrough' },
            { label: 'uppercase', desc: 'Uppercase' },
            { label: 'lowercase', desc: 'Lowercase' },
            { label: 'capitalize', desc: 'Capitalize' },
            { label: 'text-left', desc: 'Left align' },
            { label: 'text-center', desc: 'Center align' },
            { label: 'text-right', desc: 'Right align' },
            { label: 'text-justify', desc: 'Justify' },
            { label: 'leading-tight', desc: 'Tight leading' },
            { label: 'leading-normal', desc: 'Normal leading' },
            { label: 'leading-relaxed', desc: 'Relaxed leading' },
            { label: 'tracking-tight', desc: 'Tight tracking' },
            { label: 'tracking-wide', desc: 'Wide tracking' },
        ]
    },
    colors: {
        label: 'Colors',
        classes: [
            { label: 'text-white', desc: 'White text' },
            { label: 'text-black', desc: 'Black text' },
            { label: 'text-gray-500', desc: 'Gray text' },
            { label: 'text-gray-700', desc: 'Dark gray' },
            { label: 'text-gray-900', desc: 'Darker gray' },
            { label: 'text-red-500', desc: 'Red text' },
            { label: 'text-red-600', desc: 'Dark red' },
            { label: 'text-green-500', desc: 'Green text' },
            { label: 'text-green-600', desc: 'Dark green' },
            { label: 'text-blue-500', desc: 'Blue text' },
            { label: 'text-blue-600', desc: 'Dark blue' },
            { label: 'text-indigo-500', desc: 'Indigo text' },
            { label: 'text-indigo-600', desc: 'Dark indigo' },
            { label: 'text-purple-500', desc: 'Purple text' },
            { label: 'text-amber-500', desc: 'Amber text' },
            { label: 'bg-white', desc: 'White bg' },
            { label: 'bg-black', desc: 'Black bg' },
            { label: 'bg-gray-50', desc: 'Light gray bg' },
            { label: 'bg-gray-100', desc: 'Gray bg' },
            { label: 'bg-gray-200', desc: 'Medium gray' },
            { label: 'bg-gray-900', desc: 'Dark bg' },
            { label: 'bg-red-50', desc: 'Light red' },
            { label: 'bg-red-500', desc: 'Red bg' },
            { label: 'bg-green-50', desc: 'Light green' },
            { label: 'bg-green-500', desc: 'Green bg' },
            { label: 'bg-blue-50', desc: 'Light blue' },
            { label: 'bg-blue-500', desc: 'Blue bg' },
            { label: 'bg-indigo-50', desc: 'Light indigo' },
            { label: 'bg-indigo-500', desc: 'Indigo bg' },
            { label: 'bg-indigo-600', desc: 'Dark indigo' },
            { label: 'bg-amber-50', desc: 'Light amber' },
            { label: 'bg-amber-500', desc: 'Amber bg' },
            { label: 'bg-gradient-to-r', desc: 'Gradient right' },
            { label: 'bg-gradient-to-br', desc: 'Gradient BR' },
            { label: 'from-indigo-500', desc: 'From indigo' },
            { label: 'to-purple-500', desc: 'To purple' },
            { label: 'via-pink-500', desc: 'Via pink' },
        ]
    },
    borders: {
        label: 'Borders',
        classes: [
            { label: 'border', desc: 'Border' },
            { label: 'border-0', desc: 'No border' },
            { label: 'border-2', desc: 'Border 2px' },
            { label: 'border-4', desc: 'Border 4px' },
            { label: 'border-t', desc: 'Border top' },
            { label: 'border-b', desc: 'Border bottom' },
            { label: 'border-l', desc: 'Border left' },
            { label: 'border-r', desc: 'Border right' },
            { label: 'border-gray-200', desc: 'Gray border' },
            { label: 'border-gray-300', desc: 'Medium gray' },
            { label: 'border-indigo-500', desc: 'Indigo border' },
            { label: 'rounded', desc: 'Rounded' },
            { label: 'rounded-md', desc: 'Rounded md' },
            { label: 'rounded-lg', desc: 'Rounded lg' },
            { label: 'rounded-xl', desc: 'Rounded xl' },
            { label: 'rounded-2xl', desc: 'Rounded 2xl' },
            { label: 'rounded-full', desc: 'Fully rounded' },
            { label: 'rounded-none', desc: 'No rounding' },
            { label: 'divide-y', desc: 'Divide Y' },
            { label: 'divide-x', desc: 'Divide X' },
        ]
    },
    effects: {
        label: 'Effects',
        classes: [
            { label: 'shadow', desc: 'Shadow' },
            { label: 'shadow-sm', desc: 'Small shadow' },
            { label: 'shadow-md', desc: 'Medium shadow' },
            { label: 'shadow-lg', desc: 'Large shadow' },
            { label: 'shadow-xl', desc: 'XL shadow' },
            { label: 'shadow-2xl', desc: '2XL shadow' },
            { label: 'shadow-none', desc: 'No shadow' },
            { label: 'opacity-50', desc: '50% opacity' },
            { label: 'opacity-75', desc: '75% opacity' },
            { label: 'opacity-90', desc: '90% opacity' },
            { label: 'hover:opacity-100', desc: 'Hover full' },
            { label: 'hover:shadow-lg', desc: 'Hover shadow' },
            { label: 'hover:scale-105', desc: 'Hover scale' },
            { label: 'transition', desc: 'Transition' },
            { label: 'transition-all', desc: 'Transition all' },
            { label: 'duration-200', desc: 'Duration 200ms' },
            { label: 'duration-300', desc: 'Duration 300ms' },
            { label: 'ease-in-out', desc: 'Ease in out' },
            { label: 'transform', desc: 'Transform' },
            { label: 'scale-95', desc: 'Scale 95%' },
            { label: 'scale-100', desc: 'Scale 100%' },
            { label: 'scale-105', desc: 'Scale 105%' },
            { label: 'rotate-45', desc: 'Rotate 45deg' },
            { label: '-rotate-45', desc: 'Rotate -45deg' },
            { label: 'translate-x-1', desc: 'Translate X' },
            { label: 'translate-y-1', desc: 'Translate Y' },
        ]
    },
    responsive: {
        label: 'Responsive',
        classes: [
            { label: 'sm:', desc: 'Small screens' },
            { label: 'md:', desc: 'Medium screens' },
            { label: 'lg:', desc: 'Large screens' },
            { label: 'xl:', desc: 'XL screens' },
            { label: '2xl:', desc: '2XL screens' },
            { label: 'md:grid-cols-2', desc: 'MD 2 cols' },
            { label: 'md:grid-cols-3', desc: 'MD 3 cols' },
            { label: 'lg:grid-cols-4', desc: 'LG 4 cols' },
            { label: 'md:flex-row', desc: 'MD flex row' },
            { label: 'md:text-left', desc: 'MD text left' },
            { label: 'lg:text-xl', desc: 'LG text xl' },
            { label: 'md:px-8', desc: 'MD padding X' },
            { label: 'lg:py-12', desc: 'LG padding Y' },
            { label: 'hidden md:block', desc: 'Hide on mobile' },
            { label: 'md:hidden', desc: 'Hide on desktop' },
        ]
    },
    position: {
        label: 'Position',
        classes: [
            { label: 'relative', desc: 'Relative' },
            { label: 'absolute', desc: 'Absolute' },
            { label: 'fixed', desc: 'Fixed' },
            { label: 'sticky', desc: 'Sticky' },
            { label: 'top-0', desc: 'Top 0' },
            { label: 'right-0', desc: 'Right 0' },
            { label: 'bottom-0', desc: 'Bottom 0' },
            { label: 'left-0', desc: 'Left 0' },
            { label: 'inset-0', desc: 'All 0' },
            { label: 'z-10', desc: 'Z-index 10' },
            { label: 'z-20', desc: 'Z-index 20' },
            { label: 'z-50', desc: 'Z-index 50' },
            { label: 'overflow-hidden', desc: 'Overflow hidden' },
            { label: 'overflow-auto', desc: 'Overflow auto' },
            { label: 'overflow-scroll', desc: 'Overflow scroll' },
        ]
    },
};

// HTML Element Templates
const htmlElements = {
    containers: {
        label: 'Containers',
        elements: [
            { label: 'Section', code: '<section class="py-16 px-4">\n  \n</section>' },
            { label: 'Article', code: '<article class="prose max-w-none">\n  \n</article>' },
            { label: 'Aside', code: '<aside class="p-4 bg-gray-50 rounded-lg">\n  \n</aside>' },
            { label: 'Header', code: '<header class="py-4 border-b">\n  \n</header>' },
            { label: 'Footer', code: '<footer class="py-8 border-t">\n  \n</footer>' },
            { label: 'Main', code: '<main class="flex-1">\n  \n</main>' },
            { label: 'Nav', code: '<nav class="flex items-center gap-4">\n  \n</nav>' },
            { label: 'Figure', code: '<figure>\n  <img src="" alt="" class="rounded-lg" />\n  <figcaption class="text-sm text-gray-500 mt-2">Caption</figcaption>\n</figure>' },
        ]
    },
    divs: {
        label: 'Div Layouts',
        elements: [
            { label: 'Div Container', code: '<div class="container mx-auto px-4">\n  \n</div>' },
            { label: 'Div Flex Center', code: '<div class="flex items-center justify-center">\n  \n</div>' },
            { label: 'Div Flex Between', code: '<div class="flex items-center justify-between">\n  \n</div>' },
            { label: 'Div Flex Col', code: '<div class="flex flex-col gap-4">\n  \n</div>' },
            { label: 'Div Grid 2', code: '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">\n  <div></div>\n  <div></div>\n</div>' },
            { label: 'Div Grid 3', code: '<div class="grid grid-cols-1 md:grid-cols-3 gap-6">\n  <div></div>\n  <div></div>\n  <div></div>\n</div>' },
            { label: 'Div Grid 4', code: '<div class="grid grid-cols-2 md:grid-cols-4 gap-4">\n  <div></div>\n  <div></div>\n  <div></div>\n  <div></div>\n</div>' },
            { label: 'Div Card', code: '<div class="bg-white rounded-xl shadow-lg p-6">\n  \n</div>' },
            { label: 'Div Overlay', code: '<div class="relative">\n  <div class="absolute inset-0 bg-black/50"></div>\n  <div class="relative z-10">\n    \n  </div>\n</div>' },
            { label: 'Div Sticky', code: '<div class="sticky top-0 z-50 bg-white shadow">\n  \n</div>' },
        ]
    },
    text: {
        label: 'Text Elements',
        elements: [
            { label: 'Heading 1', code: '<h1 class="text-4xl font-bold text-gray-900">Heading 1</h1>' },
            { label: 'Heading 2', code: '<h2 class="text-3xl font-bold text-gray-900">Heading 2</h2>' },
            { label: 'Heading 3', code: '<h3 class="text-2xl font-semibold text-gray-900">Heading 3</h3>' },
            { label: 'Heading 4', code: '<h4 class="text-xl font-semibold text-gray-800">Heading 4</h4>' },
            { label: 'Paragraph', code: '<p class="text-gray-600 leading-relaxed">Your text here...</p>' },
            { label: 'Lead Text', code: '<p class="text-xl text-gray-600 leading-relaxed">Lead paragraph text...</p>' },
            { label: 'Small Text', code: '<p class="text-sm text-gray-500">Small text here...</p>' },
            { label: 'Blockquote', code: '<blockquote class="border-l-4 border-indigo-500 pl-4 italic text-gray-700">\n  Quote text here...\n</blockquote>' },
            { label: 'Code Block', code: '<pre class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto"><code>// Your code here</code></pre>' },
            { label: 'Inline Code', code: '<code class="bg-gray-100 text-red-600 px-1.5 py-0.5 rounded text-sm">code</code>' },
        ]
    },
    lists: {
        label: 'Lists',
        elements: [
            { label: 'Unordered List', code: '<ul class="list-disc list-inside space-y-2 text-gray-600">\n  <li>Item 1</li>\n  <li>Item 2</li>\n  <li>Item 3</li>\n</ul>' },
            { label: 'Ordered List', code: '<ol class="list-decimal list-inside space-y-2 text-gray-600">\n  <li>Step 1</li>\n  <li>Step 2</li>\n  <li>Step 3</li>\n</ol>' },
            { label: 'Check List', code: '<ul class="space-y-3">\n  <li class="flex items-center gap-2">\n    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>\n    <span>Feature 1</span>\n  </li>\n  <li class="flex items-center gap-2">\n    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>\n    <span>Feature 2</span>\n  </li>\n</ul>' },
            { label: 'Definition List', code: '<dl class="space-y-4">\n  <div>\n    <dt class="font-semibold text-gray-900">Term 1</dt>\n    <dd class="text-gray-600 mt-1">Definition for term 1</dd>\n  </div>\n  <div>\n    <dt class="font-semibold text-gray-900">Term 2</dt>\n    <dd class="text-gray-600 mt-1">Definition for term 2</dd>\n  </div>\n</dl>' },
        ]
    },
    media: {
        label: 'Media',
        elements: [
            { label: 'Image Responsive', code: '<img src="" alt="" class="w-full h-auto rounded-lg shadow-md" />' },
            { label: 'Image Circle', code: '<img src="" alt="" class="w-32 h-32 rounded-full object-cover" />' },
            { label: 'Image Card', code: '<div class="overflow-hidden rounded-xl shadow-lg">\n  <img src="" alt="" class="w-full h-48 object-cover" />\n  <div class="p-4">\n    <h3 class="font-bold">Title</h3>\n    <p class="text-gray-600 text-sm">Description</p>\n  </div>\n</div>' },
            { label: 'Video Embed', code: '<div class="aspect-video">\n  <iframe src="" class="w-full h-full rounded-lg" frameborder="0" allowfullscreen></iframe>\n</div>' },
            { label: 'Image Gallery', code: '<div class="grid grid-cols-2 md:grid-cols-3 gap-4">\n  <img src="" alt="" class="w-full aspect-square object-cover rounded-lg" />\n  <img src="" alt="" class="w-full aspect-square object-cover rounded-lg" />\n  <img src="" alt="" class="w-full aspect-square object-cover rounded-lg" />\n</div>' },
        ]
    },
    interactive: {
        label: 'Interactive',
        elements: [
            { label: 'Link', code: '<a href="#" class="text-indigo-600 hover:text-indigo-800 underline">Link text</a>' },
            { label: 'Button Primary', code: '<a href="#" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition shadow-md hover:shadow-lg">Button</a>' },
            { label: 'Button Secondary', code: '<a href="#" class="inline-flex items-center px-6 py-3 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition">Button</a>' },
            { label: 'Button Outline', code: '<a href="#" class="inline-flex items-center px-6 py-3 border-2 border-indigo-600 text-indigo-600 font-semibold rounded-lg hover:bg-indigo-600 hover:text-white transition">Button</a>' },
            { label: 'Button Ghost', code: '<a href="#" class="inline-flex items-center px-6 py-3 text-indigo-600 font-semibold rounded-lg hover:bg-indigo-50 transition">Button</a>' },
            { label: 'Button Icon', code: '<a href="#" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">\n  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>\n  Button\n</a>' },
            { label: 'Button Group', code: '<div class="flex gap-3">\n  <a href="#" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Primary</a>\n  <a href="#" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Secondary</a>\n</div>' },
        ]
    },
};

// Full snippets
const snippets = [
    // Layout
    { label: 'Container', code: '<div class="container mx-auto px-4">\n  \n</div>', category: 'Layout' },
    { label: 'Flex Row', code: '<div class="flex items-center gap-4">\n  \n</div>', category: 'Layout' },
    { label: 'Flex Col', code: '<div class="flex flex-col gap-4">\n  \n</div>', category: 'Layout' },
    { label: 'Grid 2-col', code: '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">\n  <div></div>\n  <div></div>\n</div>', category: 'Layout' },
    { label: 'Grid 3-col', code: '<div class="grid grid-cols-1 md:grid-cols-3 gap-6">\n  <div></div>\n  <div></div>\n  <div></div>\n</div>', category: 'Layout' },
    { label: 'Grid 4-col', code: '<div class="grid grid-cols-2 md:grid-cols-4 gap-4">\n  <div></div>\n  <div></div>\n  <div></div>\n  <div></div>\n</div>', category: 'Layout' },

    // Components
    { label: 'Card', code: '<div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition">\n  <h3 class="text-xl font-bold mb-2">Title</h3>\n  <p class="text-gray-600">Content</p>\n</div>', category: 'Components' },
    { label: 'Card Image', code: '<div class="bg-white rounded-xl shadow-lg overflow-hidden">\n  <img src="" alt="" class="w-full h-48 object-cover" />\n  <div class="p-6">\n    <h3 class="text-xl font-bold mb-2">Title</h3>\n    <p class="text-gray-600">Content</p>\n  </div>\n</div>', category: 'Components' },
    { label: 'Feature Box', code: '<div class="text-center p-6">\n  <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">\n    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>\n  </div>\n  <h3 class="text-xl font-bold mb-2">Feature Title</h3>\n  <p class="text-gray-600">Feature description goes here.</p>\n</div>', category: 'Components' },
    { label: 'Testimonial', code: '<div class="bg-gray-50 rounded-xl p-6">\n  <p class="text-gray-600 italic mb-4">"Quote text goes here. This is an amazing product!"</p>\n  <div class="flex items-center">\n    <img src="" alt="" class="w-12 h-12 rounded-full mr-4" />\n    <div>\n      <p class="font-bold">Name</p>\n      <p class="text-sm text-gray-500">Position</p>\n    </div>\n  </div>\n</div>', category: 'Components' },
    { label: 'Pricing Card', code: '<div class="bg-white rounded-xl shadow-lg p-8 text-center border-2 border-transparent hover:border-indigo-600 transition">\n  <h3 class="text-xl font-bold mb-2">Plan Name</h3>\n  <p class="text-4xl font-bold mb-6">$99<span class="text-lg text-gray-500">/mo</span></p>\n  <ul class="text-left space-y-3 mb-8">\n    <li class="flex items-center"><svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Feature 1</li>\n    <li class="flex items-center"><svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Feature 2</li>\n  </ul>\n  <a href="#" class="block w-full py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Get Started</a>\n</div>', category: 'Components' },
    { label: 'Stats', code: '<div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">\n  <div>\n    <p class="text-4xl font-bold text-indigo-600">100+</p>\n    <p class="text-gray-600">Customers</p>\n  </div>\n  <div>\n    <p class="text-4xl font-bold text-indigo-600">50K</p>\n    <p class="text-gray-600">Downloads</p>\n  </div>\n  <div>\n    <p class="text-4xl font-bold text-indigo-600">99%</p>\n    <p class="text-gray-600">Satisfaction</p>\n  </div>\n  <div>\n    <p class="text-4xl font-bold text-indigo-600">24/7</p>\n    <p class="text-gray-600">Support</p>\n  </div>\n</div>', category: 'Components' },
    { label: 'Team Member', code: '<div class="text-center">\n  <img src="" alt="" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover" />\n  <h3 class="text-lg font-bold">Name</h3>\n  <p class="text-indigo-600">Position</p>\n  <p class="text-gray-600 text-sm mt-2">Short bio...</p>\n  <div class="flex justify-center gap-3 mt-4">\n    <a href="#" class="text-gray-400 hover:text-indigo-600"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg></a>\n    <a href="#" class="text-gray-400 hover:text-indigo-600"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg></a>\n  </div>\n</div>', category: 'Components' },
    { label: 'FAQ Item', code: '<details class="group border-b border-gray-200">\n  <summary class="flex justify-between items-center py-4 cursor-pointer list-none font-semibold">\n    Question goes here?\n    <svg class="w-5 h-5 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>\n  </summary>\n  <p class="text-gray-600 pb-4">Answer goes here...</p>\n</details>', category: 'Components' },
    { label: 'Timeline', code: '<div class="space-y-8 relative before:absolute before:inset-0 before:ml-5 before:w-0.5 before:-translate-x-px before:bg-gradient-to-b before:from-indigo-500 before:to-purple-500">\n  <div class="relative flex items-center">\n    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-indigo-600 text-white font-bold text-sm">1</div>\n    <div class="ml-6">\n      <h3 class="font-bold">Step One</h3>\n      <p class="text-gray-600 text-sm">Description...</p>\n    </div>\n  </div>\n  <div class="relative flex items-center">\n    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-purple-600 text-white font-bold text-sm">2</div>\n    <div class="ml-6">\n      <h3 class="font-bold">Step Two</h3>\n      <p class="text-gray-600 text-sm">Description...</p>\n    </div>\n  </div>\n</div>', category: 'Components' },

    // Alerts
    { label: 'Alert Info', code: '<div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 rounded">\n  <p class="font-bold">Info</p>\n  <p>Your message here.</p>\n</div>', category: 'Alerts' },
    { label: 'Alert Success', code: '<div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">\n  <p class="font-bold">Success</p>\n  <p>Your message here.</p>\n</div>', category: 'Alerts' },
    { label: 'Alert Warning', code: '<div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">\n  <p class="font-bold">Warning</p>\n  <p>Your message here.</p>\n</div>', category: 'Alerts' },
    { label: 'Alert Error', code: '<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded">\n  <p class="font-bold">Error</p>\n  <p>Your message here.</p>\n</div>', category: 'Alerts' },

    // Sections
    { label: 'Hero Section', code: '<div class="py-20 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-center">\n  <h1 class="text-4xl md:text-6xl font-bold mb-4">Hero Title</h1>\n  <p class="text-xl mb-8 opacity-90">Subtitle goes here</p>\n  <a href="#" class="inline-block px-8 py-4 bg-white text-indigo-600 font-bold rounded-lg hover:shadow-xl transition">Get Started</a>\n</div>', category: 'Sections' },
    { label: 'Hero Split', code: '<div class="grid md:grid-cols-2 gap-8 items-center py-16 px-4">\n  <div>\n    <h1 class="text-4xl font-bold mb-4">Hero Title</h1>\n    <p class="text-gray-600 text-lg mb-6">Description text goes here. Explain your product or service.</p>\n    <div class="flex gap-4">\n      <a href="#" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">Primary</a>\n      <a href="#" class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50">Secondary</a>\n    </div>\n  </div>\n  <div>\n    <img src="" alt="" class="w-full rounded-xl shadow-xl" />\n  </div>\n</div>', category: 'Sections' },
    { label: 'CTA Banner', code: '<div class="bg-indigo-600 rounded-xl p-8 md:p-12 text-center text-white">\n  <h2 class="text-2xl md:text-3xl font-bold mb-4">Ready to get started?</h2>\n  <p class="mb-6 opacity-90">Join thousands of satisfied customers today.</p>\n  <a href="#" class="inline-block px-8 py-3 bg-white text-indigo-600 font-bold rounded-lg hover:shadow-xl transition">Sign Up Now</a>\n</div>', category: 'Sections' },
    { label: 'CTA Split', code: '<div class="bg-gray-900 rounded-xl p-8 md:p-12 flex flex-col md:flex-row items-center justify-between gap-6">\n  <div>\n    <h2 class="text-2xl font-bold text-white">Ready to dive in?</h2>\n    <p class="text-gray-400">Start your free trial today.</p>\n  </div>\n  <div class="flex gap-4">\n    <a href="#" class="px-6 py-3 bg-white text-gray-900 font-semibold rounded-lg hover:bg-gray-100">Get Started</a>\n    <a href="#" class="px-6 py-3 border border-gray-600 text-white font-semibold rounded-lg hover:bg-gray-800">Learn More</a>\n  </div>\n</div>', category: 'Sections' },
    { label: 'Features Grid', code: '<div class="py-16">\n  <div class="text-center mb-12">\n    <h2 class="text-3xl font-bold mb-4">Features</h2>\n    <p class="text-gray-600 max-w-2xl mx-auto">Everything you need to succeed</p>\n  </div>\n  <div class="grid md:grid-cols-3 gap-8">\n    <div class="text-center p-6">\n      <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mx-auto mb-4">\n        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>\n      </div>\n      <h3 class="font-bold mb-2">Feature 1</h3>\n      <p class="text-gray-600 text-sm">Description</p>\n    </div>\n    <div class="text-center p-6">\n      <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mx-auto mb-4">\n        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>\n      </div>\n      <h3 class="font-bold mb-2">Feature 2</h3>\n      <p class="text-gray-600 text-sm">Description</p>\n    </div>\n    <div class="text-center p-6">\n      <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mx-auto mb-4">\n        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>\n      </div>\n      <h3 class="font-bold mb-2">Feature 3</h3>\n      <p class="text-gray-600 text-sm">Description</p>\n    </div>\n  </div>\n</div>', category: 'Sections' },
    { label: 'Contact Section', code: '<div class="grid md:grid-cols-2 gap-12 py-16">\n  <div>\n    <h2 class="text-3xl font-bold mb-4">Get in Touch</h2>\n    <p class="text-gray-600 mb-8">We\'d love to hear from you.</p>\n    <div class="space-y-4">\n      <div class="flex items-center gap-4">\n        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">\n          <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>\n        </div>\n        <span>email@example.com</span>\n      </div>\n      <div class="flex items-center gap-4">\n        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">\n          <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>\n        </div>\n        <span>+1 (555) 123-4567</span>\n      </div>\n    </div>\n  </div>\n  <div class="bg-gray-50 rounded-xl p-8">\n    <form class="space-y-4">\n      <input type="text" placeholder="Name" class="w-full px-4 py-3 border border-gray-300 rounded-lg" />\n      <input type="email" placeholder="Email" class="w-full px-4 py-3 border border-gray-300 rounded-lg" />\n      <textarea placeholder="Message" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></textarea>\n      <button type="submit" class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">Send Message</button>\n    </form>\n  </div>\n</div>', category: 'Sections' },

    // Buttons
    { label: 'Button Primary', code: '<a href="#" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition shadow-md hover:shadow-lg">Button</a>', category: 'Buttons' },
    { label: 'Button Outline', code: '<a href="#" class="inline-flex items-center px-6 py-3 border-2 border-indigo-600 text-indigo-600 font-semibold rounded-lg hover:bg-indigo-600 hover:text-white transition">Button</a>', category: 'Buttons' },
    { label: 'Button Group', code: '<div class="flex gap-3">\n  <a href="#" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Primary</a>\n  <a href="#" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Secondary</a>\n</div>', category: 'Buttons' },

    // Media
    { label: 'Image Rounded', code: '<img src="" alt="" class="w-full rounded-xl shadow-lg" />', category: 'Media' },
    { label: 'Image Circle', code: '<img src="" alt="" class="w-32 h-32 rounded-full object-cover mx-auto" />', category: 'Media' },

    // Utility
    { label: 'Divider', code: '<hr class="my-8 border-gray-200" />', category: 'Utility' },
    { label: 'Spacer SM', code: '<div class="h-8"></div>', category: 'Utility' },
    { label: 'Spacer MD', code: '<div class="h-16"></div>', category: 'Utility' },
    { label: 'Spacer LG', code: '<div class="h-24"></div>', category: 'Utility' },
];

// Group snippets by category
const snippetCategories = computed(() => {
    const categories = {};
    snippets.forEach(s => {
        if (!categories[s.category]) {
            categories[s.category] = [];
        }
        categories[s.category].push(s);
    });
    return categories;
});

// Selected Tailwind category
const selectedTailwindCategory = ref('spacing');
</script>

<template>
    <div class="border border-gray-300 rounded-lg overflow-hidden bg-white focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500">
        <!-- Mode Toggle & Top Bar -->
        <div class="flex items-center justify-between p-2 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center gap-3">
                <!-- Mode Toggle -->
                <div class="flex items-center rounded-lg bg-gray-200 p-0.5">
                    <button type="button" @click="switchMode('visual')" class="px-3 py-1.5 text-xs font-medium rounded-md transition" :class="mode === 'visual' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                        Visual
                    </button>
                    <button type="button" @click="switchMode('source')" class="px-3 py-1.5 text-xs font-medium rounded-md transition" :class="mode === 'source' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                        HTML
                    </button>
                </div>

                <!-- Tab Toggle -->
                <div class="flex items-center rounded-lg bg-gray-200 p-0.5">
                    <button type="button" @click="activeTab = 'snippets'" class="px-2 py-1 text-xs font-medium rounded-md transition" :class="activeTab === 'snippets' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                        Snippets
                    </button>
                    <button type="button" @click="activeTab = 'tailwind'" class="px-2 py-1 text-xs font-medium rounded-md transition" :class="activeTab === 'tailwind' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                        Tailwind
                    </button>
                    <button type="button" @click="activeTab = 'elements'" class="px-2 py-1 text-xs font-medium rounded-md transition" :class="activeTab === 'elements' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                        Elements
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Content Search Button -->
                <button type="button" @click="showContentSearch = true" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    Content
                </button>
                <!-- Media Browser Button -->
                <button type="button" @click="showMediaBrowser = true" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Media
                </button>
            </div>
        </div>

        <!-- Snippets Panel -->
        <div v-if="activeTab === 'snippets'" class="border-b border-gray-200 bg-gray-50 max-h-40 overflow-y-auto">
            <div class="p-2">
                <div v-for="(items, category) in snippetCategories" :key="category" class="mb-3 last:mb-0">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5 px-1">{{ category }}</p>
                    <div class="flex flex-wrap gap-1">
                        <button v-for="s in items" :key="s.label" type="button" @click="insertSnippet(s.code)" class="px-2 py-1 text-xs bg-white border border-gray-200 rounded hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition">
                            {{ s.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tailwind Classes Panel -->
        <div v-if="activeTab === 'tailwind'" class="border-b border-gray-200 bg-gray-50">
            <div class="flex">
                <!-- Category Tabs -->
                <div class="w-32 border-r border-gray-200 bg-white flex-shrink-0 max-h-48 overflow-y-auto">
                    <button v-for="(cat, key) in tailwindClasses" :key="key" type="button" @click="selectedTailwindCategory = key" class="w-full px-3 py-2 text-xs text-left transition" :class="selectedTailwindCategory === key ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50'">
                        {{ cat.label }}
                    </button>
                </div>
                <!-- Classes Grid -->
                <div class="flex-1 p-2 max-h-48 overflow-y-auto">
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-1">
                        <button v-for="c in tailwindClasses[selectedTailwindCategory].classes" :key="c.label" type="button" @click="insertSnippet(c.label)" class="px-2 py-1 text-xs bg-white border border-gray-200 rounded hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition truncate" :title="c.desc">
                            {{ c.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- HTML Elements Panel -->
        <div v-if="activeTab === 'elements'" class="border-b border-gray-200 bg-gray-50 max-h-48 overflow-y-auto">
            <div class="p-2">
                <div v-for="(cat, key) in htmlElements" :key="key" class="mb-3 last:mb-0">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5 px-1">{{ cat.label }}</p>
                    <div class="flex flex-wrap gap-1">
                        <button v-for="el in cat.elements" :key="el.label" type="button" @click="insertSnippet(el.code)" class="px-2 py-1 text-xs bg-white border border-gray-200 rounded hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition">
                            {{ el.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visual Editor Toolbar -->
        <div v-if="editor && mode === 'visual'" class="flex flex-wrap items-center gap-0.5 p-2 border-b border-gray-200 bg-gray-50">
            <!-- Text Format -->
            <div class="flex items-center gap-0.5 pr-2 border-r border-gray-300">
                <button type="button" @click="editor.chain().focus().toggleBold().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('bold') }" class="p-1.5 rounded hover:bg-gray-200 transition" title="Bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().toggleItalic().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('italic') }" class="p-1.5 rounded hover:bg-gray-200 transition" title="Italic">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4h4m-2 0v16m0-16l-4 16m4-16l4 16m-8 0h4"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().toggleUnderline().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('underline') }" class="p-1.5 rounded hover:bg-gray-200 transition" title="Underline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8v4a5 5 0 0010 0V8M5 20h14"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().toggleStrike().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('strike') }" class="p-1.5 rounded hover:bg-gray-200 transition" title="Strikethrough">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v3m0 12v3m-9-9h18"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().toggleSubscript().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('subscript') }" class="p-1.5 rounded hover:bg-gray-200 transition text-xs font-bold" title="Subscript">X<sub>2</sub></button>
                <button type="button" @click="editor.chain().focus().toggleSuperscript().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('superscript') }" class="p-1.5 rounded hover:bg-gray-200 transition text-xs font-bold" title="Superscript">X<sup>2</sup></button>
            </div>

            <!-- Colors -->
            <div class="flex items-center gap-0.5 px-2 border-r border-gray-300">
                <div class="relative">
                    <button type="button" @click="showColorPicker = !showColorPicker; showHighlightPicker = false" class="p-1.5 rounded hover:bg-gray-200 transition flex items-center" title="Text Color">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10M12 3v14M5.5 7.5L12 3l6.5 4.5"/></svg>
                        <span class="w-3 h-1 ml-0.5 rounded" :style="{ backgroundColor: currentColor }"></span>
                    </button>
                    <div v-if="showColorPicker" class="absolute top-full left-0 mt-1 p-2 bg-white rounded-lg shadow-xl border z-50 w-48">
                        <div class="grid grid-cols-10 gap-1">
                            <button v-for="c in colors" :key="c" type="button" @click="setColor(c)" class="w-4 h-4 rounded border border-gray-300 hover:scale-110 transition" :style="{ backgroundColor: c }"></button>
                        </div>
                        <input type="color" v-model="currentColor" @change="setColor(currentColor)" class="w-full h-6 mt-2 cursor-pointer" />
                    </div>
                </div>
                <div class="relative">
                    <button type="button" @click="showHighlightPicker = !showHighlightPicker; showColorPicker = false" class="p-1.5 rounded hover:bg-gray-200 transition flex items-center" title="Highlight">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M15.24 2.75a2 2 0 012.83 0l3.18 3.18a2 2 0 010 2.83l-10.6 10.6a2 2 0 01-1.42.59H5.5a.5.5 0 01-.5-.5v-3.73a2 2 0 01.59-1.42l10.65-10.55z"/></svg>
                        <span class="w-3 h-1 ml-0.5 rounded" :style="{ backgroundColor: currentHighlight }"></span>
                    </button>
                    <div v-if="showHighlightPicker" class="absolute top-full left-0 mt-1 p-2 bg-white rounded-lg shadow-xl border z-50">
                        <div class="flex gap-1">
                            <button v-for="c in highlightColors" :key="c" type="button" @click="setHighlight(c)" class="w-6 h-6 rounded border border-gray-300 hover:scale-110 transition" :style="{ backgroundColor: c }"></button>
                        </div>
                        <button type="button" @click="editor.chain().focus().unsetHighlight().run(); showHighlightPicker = false" class="w-full mt-2 text-xs text-gray-500 hover:text-gray-700">Remove</button>
                    </div>
                </div>
            </div>

            <!-- Headings -->
            <div class="flex items-center gap-0.5 px-2 border-r border-gray-300">
                <button v-for="level in [1,2,3,4]" :key="level" type="button" @click="editor.chain().focus().toggleHeading({ level }).run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('heading', { level }) }" class="p-1.5 rounded hover:bg-gray-200 transition text-xs font-bold" :title="'Heading ' + level">H{{ level }}</button>
                <button type="button" @click="editor.chain().focus().setParagraph().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('paragraph') }" class="p-1.5 rounded hover:bg-gray-200 transition text-xs font-medium" title="Paragraph">P</button>
            </div>

            <!-- Lists -->
            <div class="flex items-center gap-0.5 px-2 border-r border-gray-300">
                <button type="button" @click="editor.chain().focus().toggleBulletList().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('bulletList') }" class="p-1.5 rounded hover:bg-gray-200 transition" title="Bullet List">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().toggleOrderedList().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('orderedList') }" class="p-1.5 rounded hover:bg-gray-200 transition" title="Numbered List">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 6h13M7 12h13M7 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                </button>
            </div>

            <!-- Alignment -->
            <div class="flex items-center gap-0.5 px-2 border-r border-gray-300">
                <button type="button" @click="editor.chain().focus().setTextAlign('left').run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive({ textAlign: 'left' }) }" class="p-1.5 rounded hover:bg-gray-200 transition" title="Left">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M3 10h12M3 14h18M3 18h12"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().setTextAlign('center').run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive({ textAlign: 'center' }) }" class="p-1.5 rounded hover:bg-gray-200 transition" title="Center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M6 10h12M3 14h18M6 18h12"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().setTextAlign('right').run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive({ textAlign: 'right' }) }" class="p-1.5 rounded hover:bg-gray-200 transition" title="Right">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M9 10h12M3 14h18M9 18h12"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().setTextAlign('justify').run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive({ textAlign: 'justify' }) }" class="p-1.5 rounded hover:bg-gray-200 transition" title="Justify">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M3 10h18M3 14h18M3 18h18"/></svg>
                </button>
            </div>

            <!-- Insert -->
            <div class="flex items-center gap-0.5 px-2 border-r border-gray-300">
                <button type="button" @click="setLink" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('link') }" class="p-1.5 rounded hover:bg-gray-200 transition" title="Link">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                </button>
                <button type="button" @click="showMediaBrowser = true" class="p-1.5 rounded hover:bg-gray-200 transition" title="Image from Media">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </button>
                <button type="button" @click="addImageUrl" class="p-1.5 rounded hover:bg-gray-200 transition" title="Image URL">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1M15 9l-3 3m0-3l3 3"/></svg>
                </button>
                <button type="button" @click="addYoutube" class="p-1.5 rounded hover:bg-gray-200 transition" title="YouTube">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                </button>
                <button type="button" @click="insertTable" class="p-1.5 rounded hover:bg-gray-200 transition" title="Table">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 3v18M14 3v18M3 3h18v18H3V3z"/></svg>
                </button>
            </div>

            <!-- Table Controls -->
            <div v-if="editor.isActive('table')" class="flex items-center gap-0.5 px-2 border-r border-gray-300">
                <button type="button" @click="editor.chain().focus().addColumnBefore().run()" class="p-1 rounded hover:bg-gray-200 transition text-xs" title="Add Column Before">+C←</button>
                <button type="button" @click="editor.chain().focus().addColumnAfter().run()" class="p-1 rounded hover:bg-gray-200 transition text-xs" title="Add Column After">+C→</button>
                <button type="button" @click="editor.chain().focus().addRowBefore().run()" class="p-1 rounded hover:bg-gray-200 transition text-xs" title="Add Row Before">+R↑</button>
                <button type="button" @click="editor.chain().focus().addRowAfter().run()" class="p-1 rounded hover:bg-gray-200 transition text-xs" title="Add Row After">+R↓</button>
                <button type="button" @click="editor.chain().focus().deleteColumn().run()" class="p-1 rounded hover:bg-red-100 text-red-600 transition text-xs" title="Delete Column">-C</button>
                <button type="button" @click="editor.chain().focus().deleteRow().run()" class="p-1 rounded hover:bg-red-100 text-red-600 transition text-xs" title="Delete Row">-R</button>
                <button type="button" @click="editor.chain().focus().deleteTable().run()" class="p-1 rounded hover:bg-red-100 text-red-600 transition text-xs" title="Delete Table">×T</button>
            </div>

            <!-- Other -->
            <div class="flex items-center gap-0.5 px-2 border-r border-gray-300">
                <button type="button" @click="editor.chain().focus().toggleBlockquote().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('blockquote') }" class="p-1.5 rounded hover:bg-gray-200 transition" title="Quote">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().toggleCode().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('code') }" class="p-1.5 rounded hover:bg-gray-200 transition" title="Code">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().toggleCodeBlock().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('codeBlock') }" class="p-1.5 rounded hover:bg-gray-200 transition" title="Code Block">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().setHorizontalRule().run()" class="p-1.5 rounded hover:bg-gray-200 transition" title="Divider">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"/></svg>
                </button>
            </div>

            <!-- Undo/Redo -->
            <div class="flex items-center gap-0.5 ml-auto pl-2">
                <button type="button" @click="editor.chain().focus().undo().run()" :disabled="!editor.can().undo()" class="p-1.5 rounded hover:bg-gray-200 transition disabled:opacity-30" title="Undo">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().redo().run()" :disabled="!editor.can().redo()" class="p-1.5 rounded hover:bg-gray-200 transition disabled:opacity-30" title="Redo">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6"/></svg>
                </button>
            </div>
        </div>

        <!-- Visual Editor Content -->
        <div v-show="mode === 'visual'" @click="showColorPicker = false; showHighlightPicker = false">
            <EditorContent :editor="editor" />
        </div>

        <!-- Source Code Editor -->
        <div v-show="mode === 'source'" class="relative">
            <textarea
                id="source-editor"
                :value="sourceCode"
                @input="onSourceChange"
                class="w-full min-h-[400px] px-4 py-3 font-mono text-sm bg-gray-900 text-green-400 focus:outline-none resize-y"
                placeholder="<div class='container mx-auto px-4'>
    <h2 class='text-2xl font-bold text-gray-900'>Title</h2>
    <p class='text-gray-600 mt-4'>Your content here...</p>
</div>"
                spellcheck="false"
            ></textarea>
            <div class="absolute top-2 right-2 text-xs text-gray-500 bg-gray-800 px-2 py-1 rounded">
                HTML + Tailwind CSS
            </div>
        </div>

        <!-- Media Browser Modal -->
        <MediaBrowser
            :show="showMediaBrowser"
            type="images"
            @close="showMediaBrowser = false"
            @select="onMediaSelect"
        />

        <!-- Content Link Search Modal -->
        <ContentLinkSearch
            :show="showContentSearch"
            @close="showContentSearch = false"
            @select="onContentSelect"
        />


        <!-- HTML Warning Modal -->
        <Teleport to="body">
            <div v-if="showHtmlWarning" class="fixed inset-0 z-[100] flex items-center justify-center">
                <div class="absolute inset-0 bg-black/50" @click="cancelVisualMode"></div>
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Complex HTML Detected</h3>
                            <p class="text-sm text-gray-600 mb-4">
                                Your HTML contains div elements, Tailwind classes or complex layouts that the visual editor cannot fully preserve. Switching to visual mode may change or simplify your HTML structure.
                            </p>
                            <p class="text-sm text-gray-500 mb-4">
                                <strong>Tip:</strong> For complex layouts, it's recommended to work in HTML mode only.
                            </p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-2">
                        <button @click="cancelVisualMode" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                            Stay in HTML
                        </button>
                        <button @click="applyVisualMode" type="button" class="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition">
                            Switch Anyway
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style>
.ProseMirror p.is-editor-empty:first-child::before {
    color: #9ca3af;
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}

.ProseMirror table {
    border-collapse: collapse;
    margin: 1rem 0;
    overflow: hidden;
    width: 100%;
}

.ProseMirror th,
.ProseMirror td {
    border: 1px solid #d1d5db;
    padding: 0.5rem;
    position: relative;
    vertical-align: top;
}

.ProseMirror th {
    background-color: #f3f4f6;
    font-weight: 600;
}

.ProseMirror .selectedCell {
    background-color: #dbeafe;
}

.ProseMirror iframe {
    border-radius: 0.5rem;
    margin: 1rem 0;
}
</style>