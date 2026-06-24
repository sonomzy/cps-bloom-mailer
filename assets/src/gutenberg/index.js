import { __ } from '@wordpress/i18n';
import { useRef, useState, useCallback, useEffect, useMemo } from '@wordpress/element';
import { StrictMode } from '@wordpress/element';
import { SlotFillProvider, Popover } from '@wordpress/components';
import { InterfaceSkeleton } from '@wordpress/interface';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
import { serialize, parse } from '@wordpress/blocks';
import { BlockEditorProvider } from '@wordpress/block-editor';
import { uploadMedia } from '@wordpress/media-utils';
import { useViewportMatch } from '@wordpress/compose';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

import Header from './sections/Header';
import Canvas from './sections/Canvas';
import Sidebar from './sections/Sidebar';
import Actions from './sections/Actions';
import { InserterSidebar, ListViewSidebar } from './sections/Secondary';
import PreviewModal from '../components/PreviewModal';
import TestModal from '../components/TestModal';
import CodeEditor from './components/CodeEditor';
import SendWizard from '../send-wizard';
import { Notices, useBlockHistory, useAutosave } from './components/Utils';
import './style.scss';

function Inner({ campaign, onSave, loading, templates, isTemplate, sendDemo, sendCampaign, onClose }) {
    const isMobile = useViewportMatch('medium', '<');
    const { createErrorNotice } = useDispatch(noticesStore);

    // — email meta —
    const [emailData, setEmailData] = useState({
        title: campaign?.title ?? '',
        status: campaign?.status ?? 'draft',
        template_key: campaign?.template_key,
        is_default: campaign?.is_default,
        description: campaign?.description,
        subject: campaign?.subject,
        scheduledAt: campaign?.scheduledAt,
        previewText: campaign?.previewText,
        replyTo: campaign?.replyTo,
        fromName: campaign?.fromName,
        fromEmail: campaign?.fromEmail,
    });

    // — design / header / footer —
    const [design, setDesign] = useState(campaign?.design || {});
    const [header, setHeaderContent] = useState(campaign?.header || {});
    const [footer, setFooterContent] = useState(campaign?.footer || {});

    // — UI state —
    const [selectedSection, setSelectedSection] = useState(null);
    const [secondaryPanel, setSecondaryPanel] = useState(null);
    const [showTestModal, setShowTestModal] = useState(false);
    const [showPreview, setShowPreview] = useState(false);
    const [showSendWizard, setShowSendWizard] = useState(false); // ← ADD
    const [lastSaved, setLastSaved] = useState(null);
    const [editorMode, setEditorMode] = useState('visual');
    const [codeValue, setCodeValue] = useState('');

    // — block history —
    const { history, setHistory, onInput, onChange: onBlockChange } = useBlockHistory(campaign?.blocks);
    const blocks = history.present;
    const blocksRef = useRef(blocks);
    useEffect(() => { blocksRef.current = blocks; }, [blocks]);

    // — refs to avoid stale closures in saveCampaign —
    const emailDataRef = useRef(emailData);
    const designRef = useRef(design);
    const headerRef = useRef(header);
    const footerRef = useRef(footer);
    const editorModeRef = useRef(editorMode);
    const codeValueRef = useRef(codeValue);
    useEffect(() => { emailDataRef.current = emailData; }, [emailData]);
    useEffect(() => { designRef.current = design; }, [design]);
    useEffect(() => { headerRef.current = header; }, [header]);
    useEffect(() => { footerRef.current = footer; }, [footer]);
    useEffect(() => { editorModeRef.current = editorMode; }, [editorMode]);
    useEffect(() => { codeValueRef.current = codeValue; }, [codeValue]);

    // — fullscreen mode —
    const [isFullscreen, setIsFullscreen] = useState(() => {
        try {
            return window.localStorage.getItem('cps-bloom-mailer-fullscreen-mode') === 'true';
        } catch (e) {
            return false;
        }
    });

    useEffect(() => {
        document.body.classList.toggle('cps-bloom-mailer-fullscreen-mode', isFullscreen);
        document.documentElement.classList.toggle('cps-bloom-mailer-fullscreen-mode', isFullscreen);
        try {
            window.localStorage.setItem('cps-bloom-mailer-fullscreen-mode', isFullscreen ? 'true' : 'false');
        } catch (e) { }

        return () => {
            document.body.classList.remove('cps-bloom-mailer-fullscreen-mode');
            document.documentElement.classList.remove('cps-bloom-mailer-fullscreen-mode');
        };
    }, [isFullscreen]);

    const toggleFullscreen = useCallback(() => setIsFullscreen((f) => !f), []);

    // — save —
    const saveCampaign = useCallback(({ silent = false } = {}) => {
        const ed = emailDataRef.current;
        const inCodeMode = editorModeRef.current === 'code';
        let serializedBlocks;

        if (inCodeMode) {
            serializedBlocks = codeValueRef.current;
            try {
                onBlockChange(parse(codeValueRef.current));
            } catch (e) { }
        } else {
            serializedBlocks = serialize(blocksRef.current);
        }

        let payload = {
            id: campaign?.id,
            title: ed.title || '',
            subject: ed.subject,
            preview_text: ed.previewText,
            blocks: serializedBlocks,
            design: designRef.current,
            header: headerRef.current,
            footer: footerRef.current,
        };

        payload = isTemplate
            ? { ...payload, description: ed.description, template_key: ed.template_key, is_default: ed.is_default }
            : { ...payload, status: ed.status || 'draft', reply_to: ed.replyTo, from_name: ed.fromName, from_email: ed.fromEmail };

        const result = onSave(payload, silent);
        if (result) setLastSaved(new Date());
    }, [campaign?.id, isTemplate, onSave, onBlockChange]);

    // — autosave wires into onChange —
    const scheduleAutosave = useAutosave(campaign?.id, saveCampaign);

    const handleChange = useCallback((newBlocks) => {
        onBlockChange(newBlocks);
        scheduleAutosave();
    }, [onBlockChange, scheduleAutosave]);

    // — visual / code editor toggle —
    const handleEditorModeChange = useCallback((mode) => {
        if (mode === editorMode) return;

        if (mode === 'code') {
            setCodeValue(serialize(blocksRef.current));
            setSecondaryPanel(null);
        } else {
            try {
                handleChange(parse(codeValueRef.current));
            } catch (e) {
                createErrorNotice(
                    __('Could not parse the code editor content — switched back without applying changes.', 'cps-bloom-mailer'),
                    { type: 'snackbar' }
                );
            }
        }

        setEditorMode(mode);
    }, [editorMode, handleChange, createErrorNotice]);

    // — wizard send handler ← ADD
    // Saves first so recipients are sent against the latest version,
    // then fires sendCampaign with the wizard payload.
    const handleWizardSend = useCallback(({ emailData: wizardEmailData, recipients }) => {
        saveCampaign({ silent: true });
        sendCampaign({
            campaign_id: campaign?.id,
            recipients,
            // wizard may have overridden subject/from/schedule
            subject: wizardEmailData.subject,
            preview_text: wizardEmailData.previewText,
            from_name: wizardEmailData.fromName,
            from_email: wizardEmailData.fromEmail,
            reply_to: wizardEmailData.replyTo,
            scheduled_at: wizardEmailData.scheduledAt,
        });
        setShowSendWizard(false);
    }, [campaign?.id, saveCampaign, sendCampaign]);

    // — settings —
    const settings = useMemo(() => ({
        ...(window?.cbmData?.editor_settings ?? {}),
        mediaUpload: uploadMedia,
        hasFixedToolbar: isMobile,
    }), [isMobile]);

    // — secondary sidebar —
    const secondarySidebar = useMemo(() => {
        if (secondaryPanel === 'inserter') return <InserterSidebar onClose={() => setSecondaryPanel(null)} />;
        if (secondaryPanel === 'listview') return <ListViewSidebar />;
        return undefined;
    }, [secondaryPanel]);

    return (
        <SlotFillProvider>
            <BlockEditorProvider
                value={blocks}
                onInput={onInput}
                onChange={handleChange}
                settings={settings}
                useSubRegistry={false}
            >
                <InterfaceSkeleton
                    className="cps-email-editor"
                    header={
                        <Header
                            blocksRef={blocksRef}
                            onSave={saveCampaign}
                            onClose={onClose}
                            lastSaved={lastSaved}
                            setHistory={setHistory}
                            emailData={emailData}
                            setEmailData={setEmailData}
                            setSecondaryPanel={setSecondaryPanel}
                            onChangeEditorMode={handleEditorModeChange}
                            onPreview={() => setShowPreview(true)}
                            onTestEmail={() => setShowTestModal(true)}
                            onSendCampaign={() => setShowSendWizard(true)} // ← CHANGE (was: sendCampaign)
                            states={{ isMobile, isFullscreen, history, loading, isTemplate, editorMode, lastSaved, secondaryPanel }}
                            onToggleFullscreen={toggleFullscreen}
                        />
                    }
                    {...(secondarySidebar && { secondarySidebar })}
                    content={
                        <>
                            <Notices />
                            {editorMode === 'code' ? (
                                <CodeEditor value={codeValue} onChange={setCodeValue} onClose={() => setEditorMode('visual')} />
                            ) : (
                                <Canvas
                                    isMobile={isMobile}
                                    header={header}
                                    footer={footer}
                                    design={design}
                                    selectedSection={selectedSection}
                                    setHeaderContent={setHeaderContent}
                                    setFooterContent={setFooterContent}
                                    setSelectedSection={setSelectedSection}
                                />
                            )}
                        </>
                    }
                    sidebar={<Sidebar.Slot />}
                    actions={
                        <Actions
                            onSave={saveCampaign}
                            lastSaved={lastSaved}
                            loading={loading}
                        />
                    }
                />

                <Sidebar
                    loading={loading}
                    templates={templates}
                    isTemplate={isTemplate}
                    states={{ header, design, footer, emailData, selectedSection }}
                    setStates={{ setDesign, setEmailData, onBlockChange, setHeaderContent, setFooterContent }}
                />
                <Popover.Slot />
            </BlockEditorProvider>

            {showTestModal && (
                <TestModal
                    emailData={emailData}
                    blocks={serialize(blocksRef.current)}
                    design={design}
                    header={header}
                    footer={footer}
                    sendDemo={sendDemo}
                    loading={loading}
                    onClose={() => setShowTestModal(false)}
                />
            )}
            {showPreview && (
                <PreviewModal
                    subject={emailData?.subject}
                    header={header}
                    blocks={serialize(blocksRef.current)}
                    footer={footer}
                    design={design}
                    close={() => setShowPreview(false)}
                />
            )}

            {/* ← ADD — lives outside BlockEditorProvider, same as the other modals */}
            {showSendWizard && (
                <SendWizard
                    emailData={emailData}
                    setEmailData={setEmailData}
                    blocksRef={blocksRef}
                    design={design}
                    header={header}
                    footer={footer}
                    loading={loading}
                    onSend={handleWizardSend}
                    onClose={() => setShowSendWizard(false)}
                />
            )}
        </SlotFillProvider>
    );
}

export default function Editor({ campaign, onSave, loading, templates, isTemplate = false, sendDemo = null, sendCampaign = null, onClose = null }) {
    return (
        <StrictMode>
            <ShortcutProvider>
                <Inner
                    campaign={campaign}
                    onSave={onSave}
                    loading={loading}
                    templates={templates}
                    isTemplate={isTemplate}
                    sendDemo={sendDemo}
                    sendCampaign={sendCampaign}
                    onClose={onClose}
                />
            </ShortcutProvider>
        </StrictMode>
    );
}