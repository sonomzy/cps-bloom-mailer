import { useEffect, useState, useCallback, createPortal } from "@wordpress/element";
import { Button, Dropdown, MenuGroup, MenuItem } from "@wordpress/components";
import { useLexicalComposerContext } from "@lexical/react/LexicalComposerContext";
import { Icon, chevronDown, paragraph, headingLevel1, headingLevel2, headingLevel3, headingLevel4, headingLevel5, headingLevel6, alignCenter, alignJustify, alignLeft, alignRight, quote, tag, formatListBullets, formatListNumbered } from '@wordpress/icons';
import { $getSelection, $isRangeSelection, $createParagraphNode, FORMAT_ELEMENT_COMMAND } from "lexical";
import { $isListNode, INSERT_ORDERED_LIST_COMMAND, INSERT_UNORDERED_LIST_COMMAND, REMOVE_LIST_COMMAND } from "@lexical/list";
import { $isQuoteNode, $createQuoteNode, $isHeadingNode, $createHeadingNode } from "@lexical/rich-text";
import { INSERT_MERGE_TAG_COMMAND } from './MergeTagPlugin';

const DOM_ELEMENT = document.body;

export function BlockToolbar({ blockType, blockState }) {
    const [editor] = useLexicalComposerContext();
    const [coords, setCoords] = useState({ x: 0, y: 0 });
    const placeholders = window.cbmData?.placeholders || [];
    const [state, setState] = useState({
        isBulletList: false,
        isOrderedList: false,
        isQuote: false,
        headingTag: null,
        alignment: 'left',
    });

    const updatePosition = useCallback(() => {
        const root = editor.getRootElement();
        if (!root) return;
        const rect = root.getBoundingClientRect();
        setCoords({
            x: rect.left + window.scrollX,
            y: rect.top + window.scrollY - 40,
        });
    }, [editor]);

    useEffect(() => {
        return editor.registerUpdateListener(({ editorState }) => {
            editorState.read(() => {
                const selection = $getSelection();
                if (!$isRangeSelection(selection)) return;

                const anchorNode = selection.anchor.getNode();
                const topLevel = anchorNode.getTopLevelElement();

                if (!topLevel) {
                    setState({
                        isBulletList: false,
                        isOrderedList: false,
                        isQuote: false,
                        headingTag: null,
                        alignment: 'left',
                    });
                    return;
                }

                const isBulletList = $isListNode(topLevel) && topLevel.getListType() === 'bullet';
                const isOrderedList = $isListNode(topLevel) && topLevel.getListType() === 'number';
                const isQuote = $isQuoteNode(topLevel);
                const headingTag = $isHeadingNode(topLevel) ? topLevel.getTag() : null;
                const alignment = topLevel.getFormatType() || 'left';

                setState({ isBulletList, isOrderedList, isQuote, headingTag, alignment });
            });
        });
    }, [editor]);

    useEffect(() => {
        const root = editor.getRootElement();
        if (!root) return;
        updatePosition();
    }, [editor]);

    const toggleBulletList = () => {
        editor.dispatchCommand(
            state.isBulletList ? REMOVE_LIST_COMMAND : INSERT_UNORDERED_LIST_COMMAND,
            undefined
        );
    };

    const toggleOrderedList = () => {
        editor.dispatchCommand(
            state.isOrderedList ? REMOVE_LIST_COMMAND : INSERT_ORDERED_LIST_COMMAND,
            undefined
        );
    };

    const toggleQuote = () => {
        editor.update(() => {
            const selection = $getSelection();
            if (!$isRangeSelection(selection)) return;

            const anchorNode = selection.anchor.getNode();
            const topLevel = anchorNode.getTopLevelElementOrThrow();

            if ($isQuoteNode(topLevel)) {
                const paragraph = $createParagraphNode();
                paragraph.append(...topLevel.getChildren());
                topLevel.replace(paragraph);
                paragraph.select();
            } else {
                const quote = $createQuoteNode();
                quote.append(...topLevel.getChildren());
                topLevel.replace(quote);
                quote.select();
            }
        });
    };

    const setAlignment = (alignmentValue) => {
        if (blockType === 'heading') {
            blockState.handleSetting('alignment', alignmentValue);
            return;
        }

        editor.dispatchCommand(FORMAT_ELEMENT_COMMAND, alignmentValue);
    };

    // Helper to get the current format label
    const getFormatLabel = () => {
        if (blockType === 'heading') {
            return `H${blockState.setting.level}`;
        } else {
            return state.headingTag ? state.headingTag.toUpperCase() : 'P';
        }
    };

    const setFormat = (format) => {
        if (blockType === 'heading') {
            blockState.handleSetting('level', format === 'p' ? 2 : format.replace('h', ''));
            return;
        }

        editor.update(() => {
            const selection = $getSelection();
            if (!$isRangeSelection(selection)) return;

            const anchorNode = selection.anchor.getNode();
            const topLevel = anchorNode.getTopLevelElementOrThrow();

            if (format === 'p') {
                const paragraph = $createParagraphNode();
                paragraph.append(...topLevel.getChildren());
                topLevel.replace(paragraph);
                paragraph.select();
            } else {
                const heading = $createHeadingNode(format);
                heading.append(...topLevel.getChildren());
                topLevel.replace(heading);
                heading.select();
            }
        });
    };

    return createPortal(
        <div
            className={'email-block-toolbar'}
            style={{
                position: 'absolute',
                top: coords.y,
                left: coords.x,
                display: 'flex',
                alignItems: 'center',
                gap: '4px',
                padding: '4px',
                background: '#f1f5f9',
                border: '1px solid #cbd5e1',
                borderRadius: '6px',
                zIndex: 9999,
            }}
        >
            {(blockType === 'text' || blockType === 'heading') && (
                <>
                    <Dropdown
                        renderToggle={({ onToggle }) => (
                            <>
                                <Button
                                    size="small"
                                    icon={(() => {
                                            const currentFormat = blockType === 'heading' ? `h${blockState.setting.level ?? 2}` : state.headingTag;
                                            const icons = {
                                                h1: headingLevel1,
                                                h2: headingLevel2,
                                                h3: headingLevel3,
                                                h4: headingLevel4,
                                                h5: headingLevel5,
                                                h6: headingLevel6,
                                            };
                                            return icons[currentFormat] || paragraph;
                                        })()
                                    }
                                    onClick={(e) => { e.stopPropagation(); onToggle(); }}

                                >
                                    <Icon icon={chevronDown} size={14} />
                                </Button>
                            </>
                        )}
                        renderContent={() => (
                            <MenuGroup>
                                {[
                                    blockType === 'text' ? { value: 'p', label: 'Paragraph' } : null,
                                    { value: 'h1', label: 'Heading 1' },
                                    { value: 'h2', label: 'Heading 2' },
                                    { value: 'h3', label: 'Heading 3' },
                                    { value: 'h4', label: 'Heading 4' },
                                    { value: 'h5', label: 'Heading 5' },
                                    { value: 'h6', label: 'Heading 6' },
                                ].filter(Boolean).map(({ value, label }) => {
                                    const isActive =
                                        (value === 'p' && !state.headingTag) ||
                                        value === state.headingTag ||
                                        (blockType === 'heading' && value === `h${blockState.setting.level}`);
                                    return (
                                        <MenuItem
                                            key={value}
                                            onClick={() => setFormat(value)}
                                            style={{
                                                background: isActive ? '#f1f5f9' : 'none',
                                                fontWeight: isActive ? '600' : '400',
                                            }}
                                        >
                                            {label}
                                        </MenuItem>
                                    );
                                })}
                            </MenuGroup>
                        )}
                    />

                    <span style={{ width: '1px', height: '16px', background: '#cbd5e1', margin: '0 2px' }} />
                    <Dropdown
                        renderToggle={({ onToggle }) => (
                            <Button
                                size="small"
                                icon={
                                    (() => {
                                        const currentAlignment = blockType === 'heading' ? blockState.setting.alignment : state.alignment;
                                        const icons = {
                                            left: alignLeft,
                                            center: alignCenter,
                                            right: alignRight,
                                            justify: alignJustify,
                                        };
                                        return icons[currentAlignment] || alignLeft;
                                    })()
                                }
                                aria-label="Alignment"
                                onClick={(e) => { e.stopPropagation(); onToggle(); }}
                                aria-pressed={blockType === 'heading' ? (blockState.setting.alignment !== 'left') : (state.alignment !== 'left')}
                            >
                                <svg viewBox="0 0 20 20" fill="currentColor" width="10" height="10">
                                    <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                                </svg>
                            </Button>
                        )}
                        renderContent={() => (
                            <MenuGroup>
                                {[
                                    { value: 'left', icon: 'alignLeft', label: 'Left' },
                                    { value: 'center', icon: 'alignCenter', label: 'Center' },
                                    { value: 'right', icon: 'alignRight', label: 'Right' },
                                    { value: 'justify', icon: 'justify', label: 'Justify' },
                                ].filter(Boolean).map(({ value, icon, label }) => {
                                    const isActive =
                                        value === state.alignment ||
                                        (blockType === 'heading' && value === blockState.setting.alignment);
                                    return (
                                        <MenuItem
                                            key={value}
                                            onClick={() => {
                                                setAlignment(value);
                                            }}
                                            style={{
                                                background: isActive ? '#f1f5f9' : 'none',
                                                fontWeight: isActive ? '600' : '400',
                                            }}
                                        >
                                            {label}
                                        </MenuItem>
                                    );
                                })}
                            </MenuGroup>
                        )}
                    />
                </>
            )}

            {blockType === 'text' && (
                <>
                    <span style={{ width: '1px', height: '16px', background: '#cbd5e1', margin: '0 2px' }} />
                    <Button icon={formatListBullets} aria-label="Bullet list" aria-pressed={state.isBulletList} onClick={toggleBulletList} />
                    <Button icon={formatListNumbered} aria-label="Numbered list" aria-pressed={state.isOrderedList} onClick={toggleOrderedList} />
                    <Button icon={quote} aria-label="Quote" aria-pressed={state.isQuote} onClick={toggleQuote} />
                </>
            )}
            {placeholders.length > 0 && (
                <Dropdown
                    renderToggle={({ onToggle }) => (
                        <Button
                            icon={tag}
                            aria-label="Insert merge tag"
                            onMouseDown={(e) => e.preventDefault()}
                            onClick={(e) => { e.stopPropagation(); onToggle(); }}
                        >
                            <svg viewBox="0 0 20 20" fill="currentColor" width="10" height="10">
                                <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                            </svg>
                        </Button>
                    )}
                    renderContent={() => (
                        <MenuGroup>
                            {placeholders.map((p) => (
                                <MenuItem
                                    key={p.value}
                                    onClick={() => {
                                        editor.dispatchCommand(INSERT_MERGE_TAG_COMMAND, p.value);
                                    }}
                                >
                                    {p.label}
                                </MenuItem>
                            ))}
                        </MenuGroup>
                    )}
                />
            )}
        </div>,
        DOM_ELEMENT
    );
}