import { __ } from '@wordpress/i18n';
import { useCallback, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as interfaceStore } from '@wordpress/interface';
import { Button, DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { plus, drawerRight, chevronLeft, undo, redo, listView, moreVertical, seen, external } from '@wordpress/icons';
import { displayShortcut } from '@wordpress/keycodes';
import { useShortcut, store as keyboardShortcutsStore } from '@wordpress/keyboard-shortcuts';

const SCOPE = 'cps-bloom-mailer';
const SIDEBAR_ID = 'cps-bloom-mailer/block-inspector';

export default function Header({
    onSave, onClose, setHistory, emailData, setEmailData, setSecondaryPanel, onTestEmail, onPreview, onSendCampaign, onChangeEditorMode, onToggleFullscreen, states
}) {
    const { isMobile = false, isFullscreen = false, history, loading, isTemplate, editorMode = 'visual', lastSaved, secondaryPanel } = states;
    const isCodeMode = editorMode === 'code';
    const hasUndo = history.past.length > 0;
    const hasRedo = history.future.length > 0;
    const isOpen = useSelect(
        (select) => select(interfaceStore).getActiveComplementaryArea(SCOPE) === SIDEBAR_ID,
        []
    );
    const { enableComplementaryArea, disableComplementaryArea } = useDispatch(interfaceStore);

    function toggleSidebar() {
        if (isOpen) {
            disableComplementaryArea(SCOPE, SIDEBAR_ID);
        } else {
            enableComplementaryArea(SCOPE, SIDEBAR_ID);
        }
    }

    function togglePanel(panel) {
        setSecondaryPanel(current => current === panel ? null : panel);
    }

    const doUndo = useCallback(() => {
        setHistory(h => {
            if (!h.past.length) return h;
            const previous = h.past[h.past.length - 1];
            return {
                past: h.past.slice(0, -1),
                present: previous,
                future: [h.present, ...h.future],
            };
        });
    }, []);

    const doRedo = useCallback(() => {
        setHistory(h => {
            if (!h.future.length) return h;
            const next = h.future[0];
            return {
                past: [...h.past, h.present],
                present: next,
                future: h.future.slice(1),
            };
        });
    }, []);

    const { registerShortcut } = useDispatch(keyboardShortcutsStore);
    useEffect(() => {
        registerShortcut({
            name: 'cps-bloom-mailer/undo',
            category: 'global',
            description: __('Undo your last changes.', 'cps-bloom-mailer'),
            keyCombination: { modifier: 'primary', character: 'z' },
        });
        registerShortcut({
            name: 'cps-bloom-mailer/redo',
            category: 'global',
            description: __('Redo your last changes.', 'cps-bloom-mailer'),
            keyCombination: { modifier: 'primaryShift', character: 'z' },
        });
        registerShortcut({
            name: 'cps-bloom-mailer/toggle-fullscreen-mode',
            category: 'global',
            description: __('Toggle fullscreen mode.', 'cps-bloom-mailer'),
            keyCombination: { modifier: 'secondary', character: 'f' },
        });
    }, []);

    useShortcut('cps-bloom-mailer/undo', (e) => { e.preventDefault(); if (!isCodeMode) doUndo(); });
    useShortcut('cps-bloom-mailer/redo', (e) => { e.preventDefault(); if (!isCodeMode) doRedo(); });
    useShortcut('cps-bloom-mailer/toggle-fullscreen-mode', (e) => { e.preventDefault(); onToggleFullscreen?.(); });

    return (
        <div
            className="editor-header edit-email-header"
            role="region"
            aria-label={__('Email Editor top bar', 'cps-bloom-mailer')}
            tabIndex="-1"
        >
            {/* ── Left: inserter + undo/redo ───────────────────────────── */}
            <div className="email-header__toolbar">
                <Button
                    icon={plus}
                    variant="primary"
                    isPressed={secondaryPanel === 'inserter'}
                    label={__('Block Inserter', 'cps-bloom-mailer')}
                    className="email-header__inserter-toggle"
                    onClick={() => togglePanel('inserter')}
                />

                {!isMobile && (
                    <>
                        <Button
                            icon={undo}
                            label={__('Undo', 'cps-bloom-mailer')}
                            shortcut={displayShortcut.primary('z')}
                            onClick={doUndo}
                            disabled={!hasUndo}
                            className="email-header__undo-btn"
                        />

                        <Button
                            icon={redo}
                            label={__('Redo', 'cps-bloom-mailer')}
                            shortcut={displayShortcut.primaryShift('z')}
                            onClick={doRedo}
                            disabled={!hasRedo}
                            className="email-header__redo-btn"
                        />

                        <Button
                            icon={listView}
                            isPressed={secondaryPanel === 'listview'}
                            label={__('Document Overview', 'cps-bloom-mailer')}
                            className="editor-document-tools__document-overview-toggle"
                            onClick={() => togglePanel('listview')}
                        />
                    </>
                )}
            </div>

            {/* ── Centre: title ────────────────────────────────────────── */}
            <div className="editor-header__center">
                <h1 className="components-truncate components-text">
                    <div
                        contentEditable={true}
                        suppressContentEditableWarning
                        className="email-header__title"
                        onInput={(e) => setEmailData({ ...emailData, title: e.currentTarget.textContent })}
                        dangerouslySetInnerHTML={{ __html: emailData?.title || __('Untitled Email', 'cps-bloom-mailer') }}
                    />
                </h1>
            </div>

            {/* ── Right: save + sidebar toggle ─────────────────────────── */}
            <div className="email-header__actions">
                {lastSaved && loading !== 'save' && !isMobile && (
                    <span className="email-header__save-status">
                        {__('Saved', 'cps-bloom-mailer')} {lastSaved.toLocaleTimeString()}
                    </span>
                )}

                {!isMobile && (
                    <Button
                        variant="secondary"
                        isDestructive
                        icon={chevronLeft}
                        onClick={onClose}
                        disabled={!!loading}
                    />
                )}

                {!isMobile && (
                    <Button
                        icon={seen}
                        variant="secondary"
                        onClick={onPreview}
                        disabled={loading}
                    />
                )}

                {!isTemplate && !isMobile && (
                    <Button
                        variant="secondary"
                        onClick={onTestEmail}
                        disabled={loading}
                    >
                        {__('Send Test Email', 'cps-bloom-mailer')}
                    </Button>
                )}

                {!isTemplate && (
                    <Button
                        variant="secondary"
                        onClick={onSendCampaign}
                        isBusy={loading === 'send'}
                        disabled={loading}
                    >
                        {__('Send', 'cps-bloom-mailer')}
                    </Button>
                )}

                <Button
                    variant="primary"
                    className="email-header__save-btn"
                    onClick={onSave}
                    isBusy={loading === 'save'}
                    disabled={loading}
                >
                    {loading === 'save'
                        ? __('Saving…', 'cps-bloom-mailer')
                        : __('Save', 'cps-bloom-mailer')}
                </Button>

                <Button
                    icon={drawerRight}
                    label={__('Toggle inspector', 'cps-bloom-mailer')}
                    isPressed={isOpen}
                    onClick={toggleSidebar}
                />

                <DropdownMenu
                    icon={moreVertical}
                    label={__('Options', 'cps-bloom-mailer')}
                    className="email-header__options-menu"
                    popoverProps={{ placement: 'bottom-end' }}
                >
                    {({ onClose: closeMenu }) => (
                        <>
                            <MenuGroup label={__('View', 'cps-bloom-mailer')}>
                                <MenuItem
                                    icon={seen}
                                    onClick={() => { onPreview(); closeMenu(); }}
                                >
                                    {__('Preview', 'cps-bloom-mailer')}
                                </MenuItem>
                                <MenuItem
                                    role="menuitemcheckbox"
                                    isSelected={isFullscreen}
                                    shortcut={displayShortcut.secondary('f')}
                                    info={__('Show and hide the admin menu and toolbar', 'cps-bloom-mailer')}
                                    onClick={() => { onToggleFullscreen?.(); closeMenu(); }}
                                >
                                    {__('Fullscreen mode', 'cps-bloom-mailer')}
                                </MenuItem>
                            </MenuGroup>

                            <MenuGroup label={__('Editor', 'cps-bloom-mailer')}>
                                <MenuItem
                                    role="menuitemradio"
                                    isSelected={editorMode === 'visual'}
                                    onClick={() => { onChangeEditorMode('visual'); closeMenu(); }}
                                >
                                    {__('Visual editor', 'cps-bloom-mailer')}
                                </MenuItem>
                                <MenuItem
                                    role="menuitemradio"
                                    isSelected={editorMode === 'code'}
                                    onClick={() => { onChangeEditorMode('code'); closeMenu(); }}
                                >
                                    {__('Code editor', 'cps-bloom-mailer')}
                                </MenuItem>
                            </MenuGroup>

                            {isMobile && !isCodeMode && (
                                <MenuGroup label={__('Edit', 'cps-bloom-mailer')}>
                                    <MenuItem
                                        icon={undo}
                                        disabled={!hasUndo}
                                        shortcut={displayShortcut.primary('z')}
                                        onClick={() => { doUndo(); closeMenu(); }}
                                    >
                                        {__('Undo', 'cps-bloom-mailer')}
                                    </MenuItem>
                                    <MenuItem
                                        icon={redo}
                                        disabled={!hasRedo}
                                        shortcut={displayShortcut.primaryShift('z')}
                                        onClick={() => { doRedo(); closeMenu(); }}
                                    >
                                        {__('Redo', 'cps-bloom-mailer')}
                                    </MenuItem>
                                </MenuGroup>
                            )}

                            {!isTemplate && (
                                <MenuGroup label={__('Email', 'cps-bloom-mailer')}>
                                    <MenuItem
                                        icon={external}
                                        onClick={() => { onTestEmail(); closeMenu(); }}
                                    >
                                        {__('Send Test Email', 'cps-bloom-mailer')}
                                    </MenuItem>
                                </MenuGroup>
                            )}

                            <MenuGroup label={__('Navigate', 'cps-bloom-mailer')}>
                                <MenuItem
                                    icon={chevronLeft}
                                    onClick={() => { onClose(); closeMenu();}}
                                    disabled={!!loading}
                                >
                                    {isTemplate
                                        ? __('Back to Templates', 'cps-bloom-mailer')
                                        : __('Back to Campaigns', 'cps-bloom-mailer')}
                                </MenuItem>
                            </MenuGroup>
                        </>
                    )}
                </DropdownMenu>
            </div>
        </div>
    );
}