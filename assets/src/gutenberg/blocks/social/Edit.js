import clsx from 'clsx';
import { DELETE, BACKSPACE, ENTER } from '@wordpress/keycodes';
import { useDispatch, useSelect } from '@wordpress/data';
import {
    BlockControls,
    InspectorControls,
    URLPopover,
    URLInput,
    useBlockEditingMode,
    useBlockProps,
    PanelColorSettings,
    store as blockEditorStore,
} from '@wordpress/block-editor';
import { useState, useRef, createInterpolateElement } from '@wordpress/element';
import { useViewportMatch, useMergeRefs } from '@wordpress/compose';
import {
    Button,
    Dropdown,
    TextControl,
    ToolbarButton,
    ExternalLink,
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem,
    __experimentalInputControlSuffixWrapper as InputControlSuffixWrapper,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { keyboardReturn } from '@wordpress/icons';
import { store as blocksStore } from '@wordpress/blocks';

function useToolsPanelDropdownMenuProps() {
    const isMobile = useViewportMatch('medium', '<');
    return !isMobile
        ? { popoverProps: { placement: 'left-start', offset: 259 } }
        : {};
}

const getIconUrl = (service, color) => {
    const socials = ['facebook', 'x', 'instagram', 'linkedin', 'youtube', 'pinterest', 'tiktok', 'twitch', 'whatsapp', 'discord', 'snapchat', 'threads', 'telegram', 'etsy', 'goodreads', 'medium', 'wordpress', 'twitter', 'vimeo', 'github',];
    if (!socials.includes(service)) {
        return '';
    }
    return `/wp-content/plugins/cps-bloom-mailer/assets/socials/${color || 'black'}/${service}.png`;
}

const SocialLinkURLPopover = ({ url, setAttributes, setPopover, popoverAnchor, clientId }) => {
    const { removeBlock } = useDispatch(blockEditorStore);
    return (
        <URLPopover
            anchor={popoverAnchor}
            aria-label={__('Edit social link')}
            onClose={() => {
                setPopover(false);
                popoverAnchor?.focus();
            }}
        >
            <form
                className="block-editor-url-popover__link-editor"
                onSubmit={(event) => {
                    event.preventDefault();
                    setPopover(false);
                    popoverAnchor?.focus();
                }}
            >
                <div className="block-editor-url-input">
                    <URLInput
                        value={url}
                        onChange={(nextURL) => setAttributes({ url: nextURL })}
                        placeholder={__('Enter social link')}
                        label={__('Enter social link')}
                        hideLabelFromVision
                        disableSuggestions
                        onKeyDown={(event) => {
                            if (!!url || event.defaultPrevented || ![BACKSPACE, DELETE].includes(event.keyCode)) {
                                return;
                            }
                            removeBlock(clientId);
                        }}
                        suffix={
                            <InputControlSuffixWrapper variant="control">
                                <Button icon={keyboardReturn} label={__('Apply')} type="submit" size="small" />
                            </InputControlSuffixWrapper>
                        }
                    />
                </div>
            </form>
        </URLPopover>
    );
};

const SocialEdit = ({ attributes, context, isSelected, setAttributes, clientId, name }) => {
    const { url, service, label = '', rel } = attributes;
    const dropdownMenuProps = useToolsPanelDropdownMenuProps();
    const {
        showLabels,
        iconColor,
        iconColorValue,
        iconBackgroundColorValue,
    } = context;
    const [showURLPopover, setPopover] = useState(false);
    const [popoverAnchor, setPopoverAnchor] = useState(null);
    const isContentOnlyMode = useBlockEditingMode() === 'contentOnly';

    const { activeVariation } = useSelect(
        (select) => {
            const { getActiveBlockVariation } = select(blocksStore);
            return { activeVariation: getActiveBlockVariation(name, attributes) };
        },
        [name, attributes]
    );

    const socialLinkName = activeVariation?.title ?? service;
    const iconUrl = getIconUrl(service, iconColor);
    const socialLinkText = label.trim() === '' ? socialLinkName : label;

    const wrapperClasses = clsx('cps-social-link', 'cps-social-link-' + service, {
        'cps-social-link__is-incomplete': !url,
    });

    const ref = useRef();
    const blockProps = useBlockProps({
        className: 'cps-social-link-anchor',
        ref: useMergeRefs([setPopoverAnchor, ref]),
        onClick: () => setPopover(true),
        onKeyDown: (event) => {
            if (event.keyCode === ENTER) {
                event.preventDefault();
                setPopover(true);
            }
        },
    });

    return (
        <>
            {isContentOnlyMode && showLabels && (
                <BlockControls group="other">
                    <Dropdown
                        popoverProps={{ placement: 'bottom-start' }}
                        renderToggle={({ isOpen, onToggle }) => (
                            <ToolbarButton onClick={onToggle} aria-haspopup="true" aria-expanded={isOpen}>
                                {__('Text')}
                            </ToolbarButton>
                        )}
                        renderContent={() => (
                            <TextControl
                                className="cps-social-link__toolbar_content_text"
                                label={__('Text')}
                                help={__('Provide a text label or use the default.')}
                                value={label}
                                onChange={(value) => setAttributes({ label: value })}
                                placeholder={socialLinkName}
                            />
                        )}
                    />
                </BlockControls>
            )}
            <InspectorControls>
                <ToolsPanel
                    label={__('Settings')}
                    resetAll={() => setAttributes({ label: undefined })}
                    dropdownMenuProps={dropdownMenuProps}
                >
                    <ToolsPanelItem
                        isShownByDefault
                        label={__('Text')}
                        hasValue={() => !!label}
                        onDeselect={() => setAttributes({ label: undefined })}
                    >
                        <TextControl
                            label={__('Text')}
                            help={__('The text is visible when enabled from the parent Social Icons block.')}
                            value={label}
                            onChange={(value) => setAttributes({ label: value })}
                            placeholder={socialLinkName}
                        />
                    </ToolsPanelItem>
                </ToolsPanel>
            </InspectorControls>
            <InspectorControls group="advanced">
                <TextControl
                    label={__('Link relation')}
                    help={createInterpolateElement(
                        __('The <a>Link Relation</a> attribute defines the relationship between a linked resource and the current document.'),
                        { a: <ExternalLink href="https://developer.mozilla.org/docs/Web/HTML/Attributes/rel" /> }
                    )}
                    value={rel || ''}
                    onChange={(value) => setAttributes({ rel: value })}
                />
            </InspectorControls>
            <li role="presentation" className={wrapperClasses}>
                <span aria-haspopup="dialog" {...blockProps} style={{ background: iconBackgroundColorValue, color: iconColorValue }}>
                    {iconUrl && <img src={iconUrl} alt={service} width="18" height="18" style={{ display: 'block' }} />}
                    <span className={clsx('cps-social-link-label', { 'screen-reader-text': (!showLabels && iconUrl) })}>
                        {socialLinkText}
                    </span>
                </span>
                {isSelected && showURLPopover && (
                    <SocialLinkURLPopover
                        url={url}
                        setAttributes={setAttributes}
                        setPopover={setPopover}
                        popoverAnchor={popoverAnchor}
                        clientId={clientId}
                    />
                )}
            </li>
        </>
    );
};

export default SocialEdit;