import { forwardRef, useEffect, useState } from "@wordpress/element";
import { $isLinkNode, TOGGLE_LINK_COMMAND } from '@lexical/link';
import { $findMatchingParent } from '@lexical/utils';
import { $getSelection, $setSelection, $isRangeSelection, FORMAT_TEXT_COMMAND } from "lexical";
import { Button } from "@wordpress/components";
import { formatBold, link, linkOff, check, code, formatUnderline, formatItalic, formatStrikethrough, close} from '@wordpress/icons';


export const LinkPopover = forwardRef(function LinkPopover(props, ref) {
    const { editor, coords, linkUrl, setLinkUrl, lastSelection, setShowLinkInput, setCoords } = props;

    const applyLink = () => {
        if (!linkUrl) return;

        editor.update(() => {
            if (lastSelection !== null) {
                $setSelection(lastSelection);
            }
            editor.dispatchCommand(TOGGLE_LINK_COMMAND, linkUrl);
        });

        cancelLink();
    };

    const cancelLink = () => {
        setLinkUrl('');
        setShowLinkInput(false);
        setCoords(undefined);
        setTimeout(() => editor.getRootElement()?.focus(), 0);
    };

    const removeLink = () => {
        editor.dispatchCommand(TOGGLE_LINK_COMMAND, null);
        cancelLink();
    }

    return (
        <div
            ref={ref}
            className="email-block-toolbar-link"
            style={{
                position: 'absolute',
                top: coords?.y,
                left: coords?.x,
                background: '#fff',
                border: '1px solid #cbd5e1',
                borderRadius: '6px',
                padding: '10px',
                boxShadow: '0 4px 16px rgba(0,0,0,0.15)',
                zIndex: 99999,
                display: 'flex',
                gap: '5px',
                alignItems: 'center',
            }}
        >
            <input
                value={linkUrl}
                onChange={(e) => setLinkUrl(e.target.value)}
                placeholder="https://"
                autoFocus
                onKeyDown={(e) => {
                    if (e.key === 'Enter') applyLink();
                    if (e.key === 'Escape') cancelLink();
                }}
                style={{
                    fontSize: '12px',
                    padding: '4px 8px',
                    border: '1px solid #cbd5e1',
                    borderRadius: '3px',
                    outline: 'none',
                    width: '220px',
                }}
            />
            {linkUrl && <Button icon={linkOff} onMouseDown={(e) => e.preventDefault()} aria-label="Remove link" onClick={removeLink} />}
            <Button icon={check} onMouseDown={(e) => e.preventDefault()} aria-label="Apply link" onClick={applyLink} />
            <Button icon={close} onMouseDown={(e) => e.preventDefault()} aria-label="Cancel" onClick={cancelLink} />
        </div>
    );
});

export const FloatingMenu = forwardRef(function FloatingMenu(props, ref) {
    const { editor, coords, blockType = 'text', setLinkUrl, handleOpenLinkInput } = props;
    const shouldShow = coords !== undefined;
    const [state, setState] = useState({
        isBold: false,
        isCode: false,
        isItalic: false,
        isStrikethrough: false,
        isUnderline: false,
        isLink: false,
        linkUrl: '',
    });

    useEffect(() => {
        const unregisterListener = editor.registerUpdateListener(
            ({ editorState }) => {
                editorState.read(() => {
                    const selection = $getSelection();
                    if (!$isRangeSelection(selection)) return;

                    const node = selection.anchor.getNode();
                    const linkNode = $findMatchingParent(node, $isLinkNode);

                    setState({
                        isBold: selection.hasFormat("bold"),
                        isCode: selection.hasFormat("code"),
                        isItalic: selection.hasFormat("italic"),
                        isStrikethrough: selection.hasFormat("strikethrough"),
                        isUnderline: selection.hasFormat("underline"),
                        isLink: !!linkNode,
                        linkUrl: linkNode ? linkNode.getURL() : '',
                    });
                });
            }
        );
        return unregisterListener;
    }, [editor]);

    const handleLinkClick = () => {
        setLinkUrl(state.linkUrl);
        handleOpenLinkInput();
    };

    return (
        <div
            ref={ref}
            aria-hidden={!shouldShow}
            onMouseDown={(e) => e.preventDefault()}
            className="email-block-floating-menu"
            style={{
                position: "absolute",
                top: coords?.y,
                left: coords?.x,
                visibility: shouldShow ? "visible" : "hidden",
                opacity: shouldShow ? 1 : 0,
                display: "flex",
                alignItems: "center",
                gap: "4px",
                padding: "4px",
                background: "#f1f5f9",
                border: "1px solid #cbd5e1",
                borderRadius: "6px",
            }}
        >
            <Button
                icon={formatBold}
                aria-label="Format text as bold"
                aria-pressed={state.isBold}
                onClick={() => editor.dispatchCommand(FORMAT_TEXT_COMMAND, "bold")}
            />
            <Button
                icon={formatItalic}
                aria-label="Format text as italics"
                aria-pressed={state.isItalic}
                onClick={() => editor.dispatchCommand(FORMAT_TEXT_COMMAND, "italic")}
            />
            <Button
                icon={formatUnderline}
                aria-label="Format text to underlined"
                aria-pressed={state.isUnderline}
                onClick={() => editor.dispatchCommand(FORMAT_TEXT_COMMAND, "underline")}
            />
            <Button
                icon={formatStrikethrough}
                aria-label="Format text with a strikethrough"
                aria-pressed={state.isStrikethrough}
                onClick={() => editor.dispatchCommand(FORMAT_TEXT_COMMAND, "strikethrough")}
            />
            <Button
                icon={code}
                aria-label="Format text with inline code"
                aria-pressed={state.isCode}
                onClick={() => editor.dispatchCommand(FORMAT_TEXT_COMMAND, "code")}
            />
            {blockType !== 'button' && (
                <Button
                    icon={link}
                    aria-label="Insert link"
                    aria-pressed={state.isLink}
                    onClick={handleLinkClick}
                />
            )}
        </div>
    );
});