import '@wordpress/editor';
import '@wordpress/format-library';
import { useEffect, useRef } from '@wordpress/element';
import { useMergeRefs } from '@wordpress/compose';
import { parse } from '@wordpress/blocks';
import { useDispatch } from '@wordpress/data';
import {
    BlockList,
    BlockTools,
    BlockToolbar,
    WritingFlow,
    ObserveTyping,
    BlockBreadcrumb,
    BlockEditorKeyboardShortcuts,
    __unstableEditorStyles as EditorStyles,
    __unstableUseBlockSelectionClearer as useBlockSelectionClearer,
    useBlockCommands,
} from '@wordpress/block-editor';
import { EditingProvider, boxValues, defaultSpacing, resolveFontStack } from '../../utils';
import { Header, Footer } from '../components/lexicalBlocks';

function Canvas({ isMobile, header, footer, design, selectedSection, setHeaderContent, setFooterContent, setSelectedSection }) {
    const clearerRef = useBlockSelectionClearer();
    const localRef = useRef();
    const contentRef = useMergeRefs([clearerRef, localRef]);
    const { replaceBlocks } = useDispatch('core/block-editor');
    useEffect(() => {
        const stored = window.localStorage.getItem('cpsBlocks');
        if (stored?.length) {
            replaceBlocks([], parse(stored));
        }
    }, [replaceBlocks]);

    useBlockCommands();
    return (
        <>
            {(isMobile && !selectedSection) && <BlockToolbar hideDragHandle={true} />}
            {/* Canvas */}
            <div
                style={{ padding: 20, fontSize: design?.fontSize, lineHeight: design?.lineHeight, width: '100%', minHeight: '100vh' }}
                onClick={() => setSelectedSection(null)}
            >
                <div
                    style={{ borderRadius: design?.borderRadius, maxWidth: design?.containerWidth, background: design?.containerBg, margin: 'auto', overflow: 'hidden' }}
                    className="editor-canvas"
                >
                    <BlockEditorKeyboardShortcuts.Register />
                    {header?.enabled &&
                        <EditingProvider >
                            <Header
                                header={header}
                                design={design}
                                onClick={(e) => {
                                    e.stopPropagation();
                                    setSelectedSection('header');
                                }}
                                selectedSection={selectedSection}
                                setHeaderContent={setHeaderContent}
                                setSelectedSection={setSelectedSection}
                            />
                        </EditingProvider>
                    }

                    <BlockTools className='block-editor-wrapper' __unstableContentRef={localRef}>
                        <WritingFlow
                            ref={contentRef}
                            className="editor-styles-wrapper"
                            tabIndex={-1}
                            style={{
                                overflow: 'auto',
                                fontSize: design?.fontSize,
                                lineHeight: design?.lineHeight,
                                padding: boxValues(design?.padding || defaultSpacing()),
                                background: design?.containerBg,
                                fontFamily: resolveFontStack(design?.fontFamily)
                            }}
                        >
                            <ObserveTyping>
                                <BlockList className="cps-block-editor__block-list" />
                            </ObserveTyping>
                        </WritingFlow>
                    </BlockTools>

                    {footer?.enabled &&
                        <EditingProvider>
                            <Footer
                                footer={footer}
                                design={design}
                                onClick={(e) => {
                                    e.stopPropagation();
                                    setSelectedSection('footer');
                                }}
                                selectedSection={selectedSection}
                                setFooterContent={setFooterContent}
                                setSelectedSection={setSelectedSection}
                            />
                        </EditingProvider>
                    }
                </div >

                <EditorStyles
                    styles={[]}
                    scope=":where(.editor-styles-wrapper)"
                    transformOptions={{
                        ignoredSelectors: [/\.editor-styles-wrapper/gi],
                    }}
                />
            </div>
            <BlockBreadcrumb />

        </>
    );
}

export default Canvas;