import { useRef, createPortal, useCallback, useEffect, useState } from "@wordpress/element";
import { computePosition } from "@floating-ui/dom";
import { useLexicalComposerContext } from "@lexical/react/LexicalComposerContext";
import { $getSelection, $isRangeSelection } from "lexical";
import { FloatingMenu, LinkPopover } from "./FloatingMenu";

const DOM_ELEMENT = document.body;

function usePointerInteractions() {
    const [isPointerDown, setIsPointerDown] = useState(false);
    const [isPointerReleased, setIsPointerReleased] = useState(true);

    useEffect(() => {
        const onPointerDown = () => {
            setIsPointerDown(true);
            setIsPointerReleased(false);
        };
        const onPointerUp = () => {
            setIsPointerDown(false);
            setIsPointerReleased(true);
        };

        document.addEventListener('pointerdown', onPointerDown);
        document.addEventListener('pointerup', onPointerUp);
        return () => {
            document.removeEventListener('pointerdown', onPointerDown);
            document.removeEventListener('pointerup', onPointerUp);
        };
    }, []);

    return { isPointerDown, isPointerReleased };
}

export function FloatingMenuPlugin({ blockType }) {
    const ref = useRef(null);
    const popoverRef = useRef(null);
    const [coords, setCoords] = useState(undefined);
    const [editor] = useLexicalComposerContext();
    const [linkUrl, setLinkUrl] = useState('');
    const [showLinkInput, setShowLinkInput] = useState(false);
    const showLinkInputRef = useRef(false);
    const [lastSelection, setLastSelection] = useState(null);

    const { isPointerDown, isPointerReleased } = usePointerInteractions();

    const updateShowLinkInput = useCallback((val) => {
        showLinkInputRef.current = val;
        setShowLinkInput(val);
    }, []);

    const calculatePosition = useCallback(() => {
        const domSelection = getSelection();
        const domRange =
            domSelection?.rangeCount !== 0 && domSelection?.getRangeAt(0);

        if (!domRange || (!ref.current && !popoverRef.current) || isPointerDown) return setCoords(undefined);

        computePosition(domRange, ref.current, { placement: "top" })
            .then((pos) => {
                setCoords({ x: pos.x, y: pos.y - 10 });
            })
            .catch(() => {
                setCoords(undefined);
            });
    }, [isPointerDown]);

    const handleOpenLinkInput = useCallback(() => {
        editor.getEditorState().read(() => {
            const selection = $getSelection();
            if ($isRangeSelection(selection)) {
                setLastSelection(selection.clone());
            }
        });
        updateShowLinkInput(true);
    }, [editor, updateShowLinkInput]);

    const $handleSelectionChange = useCallback(() => {
        if (showLinkInputRef.current) return;

        if (
            editor.isComposing() ||
            editor.getRootElement() !== document.activeElement
        ) {
            setCoords(undefined);
            return;
        }

        const selection = $getSelection();
        if ($isRangeSelection(selection) && !selection.anchor.is(selection.focus)) {
            calculatePosition();
        } else {
            setCoords(undefined);
        }
    }, [editor, calculatePosition]);

    useEffect(() => {
        const unregisterListener = editor.registerUpdateListener(
            ({ editorState }) => {
                editorState.read(() => $handleSelectionChange());
            }
        );
        return unregisterListener;
    }, [editor, $handleSelectionChange]);

    const show = coords !== undefined;

    useEffect(() => {
        if (!show && isPointerReleased) {
            editor.getEditorState().read(() => $handleSelectionChange());
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isPointerReleased, $handleSelectionChange, editor]);

    useEffect(() => {
        const root = editor.getRootElement();
        if (!root) return;

        const handleBlur = () => {
            if (showLinkInputRef.current) return;
            setCoords(undefined);
        };

        root.addEventListener('blur', handleBlur);
        return () => root.removeEventListener('blur', handleBlur);
    }, [editor]);

    useEffect(() => {
        if (!showLinkInput) return;

        const handlePointerDown = (e) => {
            if (ref.current?.contains(e.target)) return;
            if (popoverRef.current?.contains(e.target)) return;

            updateShowLinkInput(false);
            setCoords(undefined);
        };

        document.addEventListener('pointerdown', handlePointerDown);
        return () => document.removeEventListener('pointerdown', handlePointerDown);
    }, [showLinkInput, updateShowLinkInput]);

    return createPortal(
        <>
            {blockType !== 'button' && showLinkInput && (
                <LinkPopover
                    coords={coords}
                    ref={popoverRef}
                    editor={editor}
                    linkUrl={linkUrl}
                    setCoords={setCoords}
                    lastSelection={lastSelection}
                    setLinkUrl={setLinkUrl}
                    setShowLinkInput={updateShowLinkInput}
                />
            )}
            <FloatingMenu
                ref={ref}
                editor={editor}
                coords={coords}
                blockType={blockType}
                setLinkUrl={setLinkUrl}
                handleOpenLinkInput={handleOpenLinkInput}
            />
        </>,
        DOM_ELEMENT
    );
}