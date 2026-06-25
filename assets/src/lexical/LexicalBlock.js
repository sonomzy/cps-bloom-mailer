import { useMemo, useRef, useCallback } from '@wordpress/element';
import { LinkNode } from "@lexical/link";
import { ListItemNode, ListNode } from "@lexical/list";
import { HeadingNode, QuoteNode } from "@lexical/rich-text";
import { LexicalComposer } from "@lexical/react/LexicalComposer";
import { RichTextPlugin } from "@lexical/react/LexicalRichTextPlugin";
import { ListPlugin } from "@lexical/react/LexicalListPlugin";
import { LinkPlugin } from "@lexical/react/LexicalLinkPlugin";
import { ContentEditable } from "@lexical/react/LexicalContentEditable";
import { HistoryPlugin } from '@lexical/react/LexicalHistoryPlugin';
import { OnChangePlugin } from '@lexical/react/LexicalOnChangePlugin';
import { LexicalErrorBoundary } from '@lexical/react/LexicalErrorBoundary';
import { $generateHtmlFromNodes, $generateNodesFromDOM } from '@lexical/html';
import { $getRoot, $createParagraphNode, $isElementNode } from 'lexical';
import { useEditing } from '../utils';
import { FloatingMenuPlugin } from "./plugin/FloatingMenuPlugin";
import { MergeTagNode, MergeTagPlugin } from './plugin/MergeTagPlugin';
import { FocusPlugin } from './plugin/FocusPlugin';
import { BlockToolbar } from './plugin/BlockToolbar';

const EDITOR_NODES = [
    HeadingNode,
    LinkNode,
    ListNode,
    ListItemNode,
    QuoteNode,
    MergeTagNode,
];

function isValidUrl(string) {
    try {
        const url = new URL(string);
        return url.protocol === "http:" || url.protocol === "https:";
    } catch (e) {
        return false;
    }
}

export function LexicalBlock({ blockId, initialHTML, selected, onChange, style, blockType = null, savedState = null, blockState = {}, placeholder = '', onFocus = () => { }, onBlur = () => { }}) {
    const { setIsEditing } = useEditing();
    const initialConfig = useMemo(() => ({
        namespace: `block-${blockId}`,
        nodes: EDITOR_NODES,
        theme: {
            root: "cps-lexical",
            link: "cps-link",
            text: {
                bold: 'cps-bold',
                code: 'cps-code',
                italic: 'cps-italic',
                strikethrough: 'cps-strikethrough',
                subscript: 'cps-subscript',
                superscript: 'cps-superscript',
                underline: 'cps-underline',
            },
        },
        onError: (error) => {
            console.error(error);
        },
        editorState: (() => {
            const isValidState =
                savedState &&
                typeof savedState === 'object' &&
                !Array.isArray(savedState) &&
                savedState.root &&
                Array.isArray(savedState.root.children) &&
                savedState.root.children.length > 0;

            if (isValidState) {
                return JSON.stringify(savedState);
            }

            if (initialHTML && initialHTML.trim() !== '') {
                return (editor) => {
                    const parser = new DOMParser();
                    const dom = parser.parseFromString(initialHTML, 'text/html');
                    const nodes = $generateNodesFromDOM(editor, dom);

                    const root = $getRoot();
                    root.clear();

                    if (nodes.length > 0) {
                        nodes.forEach((node) => {
                            if ($isElementNode(node)) {
                                root.append(node);
                            } else {
                                const paragraph = $createParagraphNode();
                                paragraph.append(node);
                                root.append(paragraph);
                            }
                        });
                    } else {
                        root.append($createParagraphNode());
                    }
                };
            }
            return '{"root":{"children":[{"children":[],"direction":null,"format":"","indent":0,"type":"paragraph","version":1}],"direction":null,"format":"","indent":0,"type":"root","version":1}}';
        })(),

    }), [blockId, initialHTML, savedState]);
    const latestEditorRef = useRef(null);

    // Keep OnChangePlugin but only to track state, not call onChange
    const handleChange = useCallback((editorState, editor) => {
        latestEditorRef.current = { editorState, editor };
    }, []);

    const handleBlur = useCallback(() => {
        onBlur();
        setIsEditing(false);
        if (!latestEditorRef.current) return;
        const { editorState, editor } = latestEditorRef.current;
        editor.read(() => {
            const html = $generateHtmlFromNodes(editor, null);
            onChange({ html, json: editorState.toJSON() });
        });
    }, [onBlur, onChange]);
    // const handleChange = (editorState, editor) => {
    //     editor.read(() => {
    //         const html = $generateHtmlFromNodes(editor, null);
    //         onChange({ html, json: editorState.toJSON() });
    //     });
    // };

    return (
        <LexicalComposer initialConfig={initialConfig}>
            <RichTextPlugin
                contentEditable={
                    <ContentEditable
                        spellCheck={true}
                        style={style}
                        className="editable"
                        aria-placeholder={placeholder}
                    />
                }
                ErrorBoundary={LexicalErrorBoundary}
            />

            <HistoryPlugin />
            <ListPlugin />
            <MergeTagPlugin />
            <LinkPlugin /*validateUrl={isValidUrl} */ />
            <FocusPlugin
                onFocus={() => { onFocus(); setIsEditing(true); }}
                onBlur={handleBlur}
            />
            <OnChangePlugin onChange={handleChange} />
            {selected === blockId && (
                <>
                    <FloatingMenuPlugin blockType={blockType ?? blockId} />
                    <BlockToolbar blockType={blockType ?? blockId} blockState={blockState} />
                </>
            )}
        </LexicalComposer>
    );
}