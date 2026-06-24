import { useLexicalComposerContext } from "@lexical/react/LexicalComposerContext";
import { useEffect } from "@wordpress/element";

export function FocusPlugin({ onFocus, onBlur }) {
    const [editor] = useLexicalComposerContext();

    useEffect(() => {

        function handleFocus(event) {
            onFocus?.(event, editor);
        }

        function handleBlur(event) {
            onBlur?.(event, editor);
        }

        return editor.registerRootListener((rootElement, prevRootElement) => {

            if (prevRootElement) {
                prevRootElement.removeEventListener('focusin', handleFocus);
                prevRootElement.removeEventListener('focusout', handleBlur);
            }

            if (rootElement) {
                rootElement.addEventListener('focusin', handleFocus);
                rootElement.addEventListener('focusout', handleBlur);
            }
        });

    }, [editor, onFocus, onBlur]);

    return null;
}