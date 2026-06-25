import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button, PanelBody, __experimentalNumberControl as NumberControl, TextControl, SelectControl, DateTimePicker, RangeControl, ToggleControl, Flex, BaseControl, TextareaControl } from '@wordpress/components';
import { upload } from '@wordpress/icons';
import {
    FontSizePicker,
    ContrastChecker,
    PanelColorSettings,
    __experimentalSpacingSizesControl as SpacingSizesControl
} from '@wordpress/block-editor';

import { openMediaLibrary, defaultSpacing } from '../../utils';
import Templates from '../../components/Templates';
const fontFamilies = window.cbmData?.fontFamilies ?? [];

export const HeaderPanel = ({ header, setHeaderContent }) => {
    const setBlockSetting = (setting, value) => {
        setHeaderContent((prev) => ({
            ...prev,
            settings: { ...prev.settings, [setting]: value },
        }))
    };

    return (
        <>
            <PanelBody title={__('Header Settings', 'cps-bloom-mailer')} initialOpen={true}>
                <ToggleControl
                    label={__('Distinct Header', 'cps-bloom-mailer')}
                    checked={header?.enabled ?? false}
                    onChange={(value) => setHeaderContent(prev => ({ ...prev, enabled: value }))}
                />

                <ToggleControl
                    label={__('Use Logo', 'cps-bloom-mailer')}
                    checked={header?.logo ?? false}
                    onChange={(value) => setHeaderContent(prev => ({ ...prev, logo: value }))}
                />
                {header?.logo === true && (
                    <>
                        <Flex gap={2} style={{ marginBottom: '10px' }}>
                            <Button
                                variant="secondary"
                                onClick={() => openMediaLibrary((media) => setHeaderContent(prev => ({
                                    ...prev,
                                    logoUrl: media.url
                                })))}
                                icon={upload}
                            >
                                {header?.logoUrl
                                    ? __('Change', 'cps-bloom-mailer')
                                    : __('Upload Image', 'cps-bloom-mailer')}
                            </Button>
                            {header?.logoUrl && (
                                <Button
                                    variant="secondary"
                                    isDestructive
                                    onClick={() => setHeaderContent(prev => ({ ...prev, logoUrl: '' }))}
                                >
                                    {__('Remove', 'cps-bloom-mailer')}
                                </Button>
                            )}
                        </Flex>
                        <RangeControl
                            __next40pxDefaultSize={true}
                            __nextHasNoMarginBottom={true}
                            label={__('Logo Width [%]', 'cps-bloom-mailer')}
                            value={header?.logoWidth}
                            onChange={(value) => setHeaderContent(prev => ({ ...prev, logoWidth: value }))}
                            min={40}
                            max={100}
                        />
                    </>
                )}
                <BaseControl>
                    <SpacingSizesControl
                        label={__('Padding')}
                        units={[ 'px' ]}
                        values={header?.settings?.padding || defaultSpacing('header')}
                        onChange={(value) => setBlockSetting('padding', value)}
                    />
                </BaseControl>

                {header?.logo === false && (
                    <>
                        <BaseControl>
                            <FontSizePicker
                                units={[ 'px' ]}
                                value={header?.settings?.titleSize || '28px'}
                                withSlider={true}
                                onChange={(value) => setBlockSetting('titleSize', value)}
                            />
                        </BaseControl>
                        <SelectControl
                            __next40pxDefaultSize={true}
                            label={__('Font Family', 'cps-bloom-mailer')}
                            value={header?.settings?.fontFamily}
                            onChange={(value) => setBlockSetting('fontFamily', value)}
                            options={fontFamilies.map(({ value, label }) => ({ value, label }))}
                        />
                    </>
                )}
                {!header?.logoUrl && (
                    <>
                        <ToggleControl
                            label={__('Show Description', 'cps-bloom-mailer')}
                            checked={header?.settings?.showDescription ?? false}
                            onChange={(value) => setBlockSetting('showDescription', value)}
                        />

                        {header?.settings?.showDescription === true && (
                            <BaseControl label={__('Description Font Size', 'cps-bloom-mailer')}>
                                <FontSizePicker
                                    units={[ 'px' ]}
                                    value={header?.settings?.fontSize || '28px'}
                                    withSlider={true}
                                    onChange={(value) => setBlockSetting('fontSize', value)}

                                />
                            </BaseControl>
                        )}
                    </>
                )}
                <>
                    <PanelColorSettings
                        __experimentalIsRenderedInSidebar
                        title={__('Color')}
                        colorSettings={[
                            {
                                value: header?.settings?.textColor,
                                onChange: (value) => setBlockSetting('textColor', value),
                                label: __('Text'),
                            },
                            {
                                value: header?.settings?.background,
                                onChange: (value) => setBlockSetting('background', value),
                                label: __('Background'),
                            }
                        ]}
                    />
                    <ContrastChecker
                        {...{
                            textColor: header?.settings?.textColor,
                            backgroundColor: header?.settings?.background
                        }}
                        isLargeText={false}
                    />
                </>
            </PanelBody>
        </>
    );
}

export const FooterPanel = ({ footer, setFooterContent }) => {
    const setBlockSetting = (setting, value) => {
        setFooterContent((prev) => ({
            ...prev,
            settings: { ...prev.settings, [setting]: value },
        }));
    };

    return (
        <>
            <PanelBody title={__('Footer Settings', 'cps-bloom-mailer')} initialOpen={true}>
                <ToggleControl
                    label={__('Distinct Footer', 'cps-bloom-mailer')}
                    checked={footer?.enabled ?? false}
                    onChange={(value) => setFooterContent(prev => ({ ...prev, enabled: value }))}
                />
                <BaseControl>
                    <SpacingSizesControl
                        label={__('Padding')}
                        units={[ 'px' ]}
                        values={footer?.settings?.padding || defaultSpacing('footer')}
                        onChange={(value) => setBlockSetting('padding', value)}
                    />
                </BaseControl>
                <BaseControl>
                    <FontSizePicker
                        units={[ 'px' ]}
                        value={footer?.settings?.fontSize || '12px'}
                        withSlider={true}
                        fontSizes={[
                            { name: 'Small', size: 12, slug: 'small' },
                            { name: 'Normal', size: 16, slug: 'normal' },
                            { name: 'Big', size: 26, slug: 'big' }
                        ]}
                        onChange={(value) => setBlockSetting('fontSize', value)}
                    />
                </BaseControl>
                <>
                    <PanelColorSettings
                        __experimentalIsRenderedInSidebar
                        title={__('Color')}
                        colorSettings={[
                            {
                                value: footer?.settings?.textColor,
                                onChange: (value) => setBlockSetting('textColor', value),
                                label: __('Text'),
                            },
                            {
                                value: footer?.settings?.background,
                                onChange: (value) => setBlockSetting('background', value),
                                label: __('Background'),
                            }
                        ]}
                    />
                    <ContrastChecker
                        {...{
                            textColor: footer?.settings?.textColor,
                            backgroundColor: footer?.settings?.background
                        }}
                        isLargeText={false}
                    />
                </>
            </PanelBody>
        </>
    );
}

export const EmailPanel = ({ loading, templates, isTemplate, states, setStates }) => {
    const { header, design, footer, emailData } = states;
    const { setDesign, setEmailData, onBlockChange, setHeaderContent, setFooterContent } = setStates;
    const [showTemplates, setShowTemplates] = useState(false);

    const loadDesign = (id) => {
        const loadedDesign = templates[id];
        setDesign(loadedDesign?.design || {});
        onBlockChange(loadedDesign?.blocks || '');
        setEmailData({
            title: loadedDesign?.title,
            description: loadedDesign?.description,
            subject: loadedDesign?.subject,
            previewText: loadedDesign?.previewText,
            replyTo: loadedDesign?.replyTo,
            fromName: loadedDesign?.fromName,
            fromEmail: loadedDesign?.fromEmail,
        });
        setHeaderContent(loadedDesign?.header || {});
        setFooterContent(loadedDesign?.footer || {});
    }

    return (
        <>
            {!isTemplate && (
                <div style={{ padding: 20 }}>
                    <Button
                        variant="primary"
                        onClick={() => setShowTemplates(true)}
                    >
                        {__('Start with A Design', 'cps-bloom-mailer')}
                    </Button>

                    {showTemplates && (
                        <Templates
                            loadDesign={loadDesign}
                            loading={loading}
                            templates={templates}
                            closeModal={() => setShowTemplates(false)}
                        />
                    )}
                </div>
            )}
            <PanelBody title={__('Details', 'cps-bloom-mailer')} initialOpen={true}>
                <TextControl
                    __next40pxDefaultSize={true}
                    label={__('Subject line', 'cps-bloom-mailer')}
                    value={emailData?.subject}
                    onChange={(subject) => setEmailData({ ...emailData, subject })}
                />
                <TextControl
                    __next40pxDefaultSize={true}
                    label={__('Preview text', 'cps-bloom-mailer')}
                    help={__('Shown in the inbox before opening.', 'cps-bloom-mailer')}
                    value={emailData?.previewText}
                    onChange={(previewText) => setEmailData({ ...emailData, previewText })}
                />
                {isTemplate ? (
                    <TextareaControl
                        label={__('Description', 'cps-bloom-mailer')}
                        value={emailData?.description || ''}
                        onChange={(description) => setEmailData({ ...emailData, description })}
                        rows={2}
                    />
                ) : (
                    <>
                        <SelectControl
                            __next40pxDefaultSize={true}
                            label={__('Status', 'cps-bloom-mailer')}
                            value={emailData?.status}
                            options={[
                                { label: __('Draft', 'cps-bloom-mailer'), value: 'draft' },
                                { label: __('Scheduled', 'cps-bloom-mailer'), value: 'scheduled' },
                                { label: __('Sent', 'cps-bloom-mailer'), value: 'sent' },
                            ]}
                            onChange={(status) => setEmailData({ ...emailData, status })}
                        />
                        {emailData?.status === 'scheduled' && (
                            <DateTimePicker
                                currentDate={emailData?.scheduledAt}
                                onChange={(scheduledAt) => setEmailData({ ...emailData, scheduledAt })}
                                is12Hour
                            />
                        )}
                        <TextControl
                            __next40pxDefaultSize={true}
                            label={__('From name', 'cps-bloom-mailer')}
                            value={emailData?.fromName}
                            onChange={(fromName) => setEmailData({ ...emailData, fromName })}
                        />
                        <TextControl
                            __next40pxDefaultSize={true}
                            label={__('From email', 'cps-bloom-mailer')}
                            type="email"
                            value={emailData?.fromEmail}
                            onChange={(fromEmail) => setEmailData({ ...emailData, fromEmail })}
                        />
                        <TextControl
                            __next40pxDefaultSize={true}
                            label={__('Reply to email', 'cps-bloom-mailer')}
                            type="email"
                            value={emailData?.replyTo}
                            onChange={(replyTo) => setEmailData({ ...emailData, replyTo })}
                        />
                    </>
                )}
            </PanelBody>
            <PanelBody
                title={__('Design', 'cps-bloom-mailer')}
                initialOpen={true}
            >
                <PanelColorSettings
                    __experimentalIsRenderedInSidebar
                    title={__('Color')}
                    colorSettings={[
                        {
                            value: design?.bodyBg,
                            onChange: (value) => setDesign(prev => ({ ...prev, bodyBg: value })),
                            label: __('Body Background'),
                        },
                        {
                            value: design?.containerBg,
                            onChange: (value) => setDesign(prev => ({ ...prev, containerBg: value })),
                            label: __('Content Background'),
                        },
                        {
                            value: design?.textColor,
                            onChange: (value) => setDesign(prev => ({ ...prev, textColor: value })),
                            label: __('Text Color'),
                        }
                    ]}
                />

                <RangeControl
                    __next40pxDefaultSize={true}
                    label={__('Max Width [px]', 'cps-bloom-mailer')}
                    value={design?.containerWidth}
                    onChange={(value) => setDesign(prev => ({ ...prev, containerWidth: value }))}
                    min={400}
                    max={700}
                />
                <BaseControl>
                    <SpacingSizesControl
                        label={__('Padding')}
                        sides={['horizontal', 'vertical']}
                        splitOnAxis={true}
                        units={[ 'px' ]}
                        values={design?.padding}
                        onChange={(value) => {
                            setDesign(prev => ({ ...prev, padding: value }))
                        }}
                    />
                </BaseControl>

                <RangeControl
                    __next40pxDefaultSize={true}
                    label={__('Border Radius [px]', 'cps-bloom-mailer')}
                    value={design?.borderRadius}
                    onChange={(value) => setDesign(prev => ({ ...prev, borderRadius: value }))}
                    min={0}
                    max={20}
                />
            </PanelBody>
            <PanelBody
                title={__('Typography', 'cps-bloom-mailer')}
                initialOpen={true}
            >
                <SelectControl
                    __next40pxDefaultSize={true}
                    label={__('Font Family', 'cps-bloom-mailer')}
                    value={design?.fontFamily}
                    onChange={(value) => setDesign(prev => ({ ...prev, fontFamily: value }))}
                    options={fontFamilies.map(({ value, label }) => ({ value, label }))}
                />

                <NumberControl
                    __next40pxDefaultSize={true}
                    label={__('Line Height', 'cps-bloom-mailer')}
                    value={design?.lineHeight}
                    onChange={(value) => setDesign(prev => ({ ...prev, lineHeight: value }))}
                    spinControls="custom"
                    step={0.1}
                    min={0}
                    max={5}
                />

                <BaseControl>
                    <FontSizePicker
                        value={design?.fontSize}
                        withSlider={true}
                        onChange={(value) => setDesign(prev => ({ ...prev, fontSize: value }))}
                    />
                </BaseControl>
            </PanelBody>
            <PanelBody
                title={__('Header and Footer', 'cps-bloom-mailer')}
                initialOpen={true}
            >
                <ToggleControl
                    label={__('Enable Distinct Header', 'cps-bloom-mailer')}
                    checked={header?.enabled ?? false}
                    onChange={(value) => setHeaderContent(prev => ({ ...prev, enabled: value }))}
                />
                <ToggleControl
                    label={__('Enable Distinct Footer', 'cps-bloom-mailer')}
                    checked={footer?.enabled ?? false}
                    onChange={(value) => setFooterContent(prev => ({ ...prev, enabled: value }))}
                />
            </PanelBody>
        </>
    );
}