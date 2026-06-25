/**
 * WordPress dependencies
 */
import { useViewportMatch } from '@wordpress/compose';
import { useEffect } from '@wordpress/element';
import {
    useInnerBlocksProps,
    useBlockProps,
    InspectorControls,
    ContrastChecker,
    InnerBlocks,
    PanelColorSettings,
    store as blockEditorStore,
} from '@wordpress/block-editor';
import {
    ToggleControl,
    SelectControl,
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem,
    BaseControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

const sizeOptions = [
    { label: __('Default'), value: '' },
    { label: __('Small'), value: 'small' },
    { label: __('Normal'), value: 'normal' },
    { label: __('Large'), value: 'large' },
    { label: __('Huge'), value: 'huge' },
];

function useToolsPanelDropdownMenuProps() {
    const isMobile = useViewportMatch('medium', '<');
    return !isMobile
        ? {
            popoverProps: {
                placement: 'left-start',
                // For non-mobile, inner sidebar width (248px) - button width (24px) - border (1px) + padding (16px) + spacing (20px)
                offset: 259,
            },
        }
        : {};
}

export function SocialLinksEdit(props) {
    const {
        clientId,
        attributes,
        iconBackgroundColor,
        isSelected,
        setAttributes,
        setIconBackgroundColor,
    } = props;

    const {
        background,
        iconBackgroundColorValue,
        iconColorValue,
        showLabels,
        size,
        iconColor,
    } = attributes;

    const { hasSocialIcons, hasSelectedChild } = useSelect(
        (select) => {
            const { getBlockCount, hasSelectedInnerBlock } =
                select(blockEditorStore);
            return {
                hasSocialIcons: getBlockCount(clientId) > 0,
                hasSelectedChild: hasSelectedInnerBlock(clientId),
            };
        },
        [clientId]
    );

    const hasAnySelected = isSelected || hasSelectedChild;
    const logosOnly = attributes.className?.includes('is-style-logos-only');
    const dropdownMenuProps = useToolsPanelDropdownMenuProps();
    const sizes = { small: 12, normal: 16, large: 24, huge: 32 };

    // Remove icon background color when logos only style is selected or
    // restore it when any other style is selected.
    useEffect(() => {
        if (logosOnly) {
            let restore;
            setAttributes((prev) => {
                restore = {
                    iconColor: prev.iconColor,
                    iconColorValue: prev.iconColorValue,
                    iconBackgroundColor: prev.iconBackgroundColor,
                    iconBackgroundColorValue: prev.iconBackgroundColorValue,
                    customIconBackgroundColor: prev.customIconBackgroundColor,
                };
                return {
                    iconColor: 'black',
                    iconColorValue: undefined,
                    iconBackgroundColor: undefined,
                    iconBackgroundColorValue: undefined,
                    customIconBackgroundColor: undefined,
                };
            });

            return () => setAttributes({ ...restore });
        }
    }, [logosOnly, setAttributes]);

    const blockProps = useBlockProps({ style: { fontSize: (size === 'huge' || size === 'large') ? 14 : 11, '--cps--socials-size': `${sizes[size] ?? 16}px` } });
    const innerBlocksProps = useInnerBlocksProps(blockProps, {
        templateLock: false,
        orientation: attributes.layout?.orientation ?? 'horizontal',
        __experimentalAppenderTagName: 'li',
        renderAppender:
            !hasSocialIcons || hasAnySelected
                ? InnerBlocks.ButtonBlockAppender
                : undefined,
    });
    const ICON_COLORS = [
        { label: 'Black', value: 'black', hex: '#000000' },
        { label: 'White', value: 'white', hex: '#ffffff' }
    ];

    const colorSettings = [
        {
            value: background,
            onChange: (colorValue) =>
                setAttributes({ background: colorValue }),
            label: __('Background'),
        },
    ];

    if (!logosOnly) {
        colorSettings.unshift({
            value: iconBackgroundColorValue,
            onChange: (colorValue) => {
                setIconBackgroundColor(colorValue);
                setAttributes({
                    iconBackgroundColorValue: colorValue,
                });
            },
            label: __('Text'),
        });
    }

    return (
        <>
            <InspectorControls>
                <ToolsPanel
                    label={__('Settings')}
                    resetAll={() => {
                        setAttributes({
                            showLabels: false,
                            size: undefined,
                        });
                    }}
                    dropdownMenuProps={dropdownMenuProps}
                >
                    <ToolsPanelItem
                        isShownByDefault
                        hasValue={() => !!size}
                        label={__('Icon size')}
                        onDeselect={() =>
                            setAttributes({ size: undefined })
                        }
                    >
                        <SelectControl
                            __next40pxDefaultSize
                            label={__('Icon size')}
                            onChange={(newSize) => {
                                setAttributes({
                                    size: newSize === '' ? undefined : newSize,
                                });
                            }}
                            value={size ?? ''}
                            options={sizeOptions}
                        />
                    </ToolsPanelItem>
                    <ToolsPanelItem
                        isShownByDefault
                        label={__('Show text')}
                        hasValue={() => !!showLabels}
                        onDeselect={() =>
                            setAttributes({ showLabels: false })
                        }
                    >
                        <ToggleControl
                            label={__('Show text')}
                            checked={showLabels}
                            onChange={() =>
                                setAttributes({ showLabels: !showLabels })
                            }
                        />
                    </ToolsPanelItem>
                </ToolsPanel>
            </InspectorControls>
            <InspectorControls group="color">
                <div className="cps-socials-color" style={{ gridColumn: 'span 2' }}>
                    <SelectControl
                        label="Icon Color"
                        value={iconColor}
                        options={ICON_COLORS.map((c) => ({
                            label: c.label,
                            value: c.value
                        }))}
                        onChange={(value) => {
                            const selected = ICON_COLORS.find((c) => c.value === value);
                            setAttributes({ iconColor: value });
                            setAttributes({ iconColorValue: selected.hex });
                        }}
                    />

                    <>
                        <PanelColorSettings
                            __experimentalIsRenderedInSidebar
                            colorSettings={colorSettings}
                        />
                        <ContrastChecker
                            {...{
                                textColor: iconColorValue,
                                backgroundColor: iconBackgroundColorValue
                            }}
                            isLargeText={false}
                        />
                    </>
                </div>
            </InspectorControls>

            <ul {...innerBlocksProps} />
        </>
    );
}

export default SocialLinksEdit;