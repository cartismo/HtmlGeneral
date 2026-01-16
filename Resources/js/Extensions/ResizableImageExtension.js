import { Image } from '@tiptap/extension-image';
import { VueNodeViewRenderer } from '@tiptap/vue-3';
import ResizableImage from '../Components/ResizableImage.vue';

export const ResizableImageExtension = Image.extend({
    name: 'resizableImage',

    draggable: true,

    addAttributes() {
        return {
            ...this.parent?.(),
            width: {
                default: null,
                parseHTML: element => {
                    const width = element.getAttribute('width') || element.style.width;
                    return width ? parseInt(width, 10) : null;
                },
                renderHTML: attributes => {
                    if (!attributes.width) {
                        return {};
                    }
                    return {
                        width: attributes.width,
                        style: `width: ${attributes.width}px`,
                    };
                },
            },
            height: {
                default: null,
                parseHTML: element => {
                    const height = element.getAttribute('height') || element.style.height;
                    return height ? parseInt(height, 10) : null;
                },
                renderHTML: attributes => {
                    if (!attributes.height) {
                        return {};
                    }
                    return {
                        height: attributes.height,
                    };
                },
            },
            rotation: {
                default: 0,
                parseHTML: element => {
                    // Check for data attribute
                    const dataRotation = element.getAttribute('data-rotation');
                    if (dataRotation) return parseInt(dataRotation, 10);

                    // Check for transform style
                    const transform = element.style.transform;
                    if (transform) {
                        const match = transform.match(/rotate\((-?\d+)deg\)/);
                        if (match) return parseInt(match[1], 10);
                    }

                    return 0;
                },
                renderHTML: attributes => {
                    if (!attributes.rotation || attributes.rotation === 0) {
                        return {};
                    }
                    return {
                        'data-rotation': attributes.rotation,
                        style: `transform: rotate(${attributes.rotation}deg)`,
                    };
                },
            },
            alignment: {
                default: 'center',
                parseHTML: element => {
                    // Check for data attribute
                    const dataAlign = element.getAttribute('data-alignment');
                    if (dataAlign) return dataAlign;

                    // Check for style
                    const style = element.style;
                    if (style.float === 'left') return 'left';
                    if (style.float === 'right') return 'right';

                    // Check parent alignment
                    const parent = element.parentElement;
                    if (parent) {
                        if (parent.style.textAlign === 'left') return 'left';
                        if (parent.style.textAlign === 'right') return 'right';
                    }

                    return 'center';
                },
                renderHTML: attributes => {
                    return {
                        'data-alignment': attributes.alignment,
                    };
                },
            },
        };
    },

    addNodeView() {
        return VueNodeViewRenderer(ResizableImage);
    },

    parseHTML() {
        return [
            {
                tag: 'img[src]',
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        const { alignment, rotation, ...attrs } = HTMLAttributes;

        // Build style string
        let styleStr = '';
        if (rotation && rotation !== 0) {
            styleStr += `transform: rotate(${rotation}deg);`;
        }
        if (attrs.style) {
            styleStr += attrs.style;
        }

        // Add alignment as wrapper style when rendering
        const wrapperStyle = {
            left: 'text-align: left;',
            center: 'text-align: center;',
            right: 'text-align: right;',
        };

        const imgAttrs = { ...attrs };
        if (styleStr) {
            imgAttrs.style = styleStr;
        }
        if (rotation && rotation !== 0) {
            imgAttrs['data-rotation'] = rotation;
        }

        return [
            'figure',
            { style: wrapperStyle[alignment] || wrapperStyle.center },
            ['img', imgAttrs],
        ];
    },
});

export default ResizableImageExtension;