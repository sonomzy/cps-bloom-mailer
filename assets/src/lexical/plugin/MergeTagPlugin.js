import { useLexicalComposerContext } from '@lexical/react/LexicalComposerContext';
import { useEffect } from '@wordpress/element';
import { $insertNodes, COMMAND_PRIORITY_EDITOR, createCommand } from 'lexical';
import { DecoratorNode } from 'lexical';

export class MergeTagNode extends DecoratorNode {
    __value;

    static getType() {
        return 'merge-tag';
    }

    static clone(node) {
        return new MergeTagNode(node.__value, node.__key);
    }

    constructor(value, key) {
        super(key);
        this.__value = value;
    }

    createDOM(config) {
        const dom = document.createElement('span');
        dom.style.display = 'inline-block';
        return dom;
    }

    updateDOM() {
        return false;
    }

    exportJSON() {
        return {
            type: 'merge-tag',
            version: 1,
            value: this.__value,
        };
    }

    exportDOM() {
        const element = document.createElement('span');
        element.setAttribute('class', 'merge-tag');
        element.setAttribute('data-merge-tag-value', this.__value);
        element.setAttribute('contenteditable', 'false');
        element.textContent = this.__value;  // or use {{value}} style
        return { element };
    }

    static importJSON(serializedNode) {
        return new MergeTagNode(serializedNode.value);
    }

    static importDOM() {
        return {
            span: (domNode) => {
                // Check if the span has the expected class and data attribute
                if (
                    domNode instanceof HTMLElement &&
                    domNode.classList.contains('merge-tag') &&
                    domNode.hasAttribute('data-merge-tag-value')
                ) {
                    const value = domNode.getAttribute('data-merge-tag-value');
                    if (value) {
                        return {
                            node: (node) => new MergeTagNode(value),
                            // optional: how to handle children (none needed for merge tag)
                        };
                    }
                }
                return null;
            },
        };
    }

    // This renders your visual element safely isolated from editor text editing
    decorate() {
        return (
            <span>
                <code
                    className="editor-placeholder"
                    contentEditable={false}
                >
                    {this.__value}
                </code>
            </span>
        );
    }
}

const $createMergeTagNode = (value) => {
    return new MergeTagNode(value);
}

export function $isMergeTagNode(node) {
    return node instanceof MergeTagNode;
}

export const INSERT_MERGE_TAG_COMMAND = createCommand('INSERT_MERGE_TAG_COMMAND');

export function MergeTagPlugin() {
    const [editor] = useLexicalComposerContext();

    useEffect(() => {
        return editor.registerCommand(
            INSERT_MERGE_TAG_COMMAND,
            (tagValue) => {
                editor.update(() => {
                    // Create the proper structured node wrapper
                    const mergeTagNode = $createMergeTagNode(tagValue);
                    $insertNodes([mergeTagNode]);
                });
                return true;
            },
            COMMAND_PRIORITY_EDITOR
        );
    }, [editor]);

    return null;
}

