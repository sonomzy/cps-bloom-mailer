import { useState, useRef, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { Button, PanelBody, TextControl, __experimentalNumberControl as NumberControl, SelectControl, __experimentalConfirmDialog as ConfirmDialog, BaseControl } from "@wordpress/components";
import { renderNotice, resetData, importJSON, exportJSON } from '../../utils';
import './style.scss';

const SendSettings = ({ settings, handleChange }) => {
    return (
        <div className="settings-card">
            <div className="settings-card__section">
                <p className="settings-card__label">{__('Sender identity', 'cps-bloom-mailer')}</p>
                <div className="fields-row fields-row--2">
                    <TextControl
                        label={__('From Name', 'cps-bloom-mailer')}
                        value={settings.from_name || ''}
                        onChange={(value) => handleChange('from_name', value)}
                    />

                    <TextControl
                        label={__('From Email', 'cps-bloom-mailer')}
                        value={settings.from_email || ''}
                        onChange={(value) => handleChange('from_email', value)}
                        help={__('Use the email set in your domain/SMTP. Mismatches may cause delivery issues.', 'cps-bloom-mailer')}
                    />
                </div>
                <TextControl
                    label={__('Reply-To Email', 'cps-bloom-mailer')}
                    type="email"
                    value={settings.reply_to || ''}
                    onChange={(value) => handleChange('reply_to', value)}
                    placeholder="support@yoursite.com"
                />  </div>
            <div className="settings-card__section">
                <p className="settings-card__label">{__('Queue settings', 'cps-bloom-mailer')}</p>
                <div className="fields-row fields-row--3">
                    <NumberControl
                        label={__('Batch Size', 'cps-bloom-mailer')}
                        help={__('Number of emails to send per cron run to prevent server overload and comply with SMTP restrictions.', 'cps-bloom-mailer')}
                        value={settings.batch_size || 50}
                        onChange={(value) => handleChange('batch_size', value)}
                        spinControls="custom"
                        min={1}
                        max={1000}
                    />
                </div>
            </div >
        </div >
    )
}

const MailerSettings = ({ settings, handleChange }) => {
    return (
        <div className="settings-card">
            <SelectControl
                className='settings-card__section'
                __next40pxDefaultSize={true}
                label={__('Mailer', 'chicpixies-subscriptions')}
                value={settings.mailer ?? 'smtp'}
                options={[
                    { label: __('SMTP', 'chicpixies-subscriptions'), value: 'smtp' },
                    { label: __('Amazon SES', 'chicpixies-subscriptions'), value: 'ses' },
                ]}
                onChange={(value) => handleChange('mailer', value)}
            />
            {(settings.mailer || 'smtp') === 'smtp' ? (
                <div className="settings-card__section">
                    <p className="settings-card__label">{__('SMTP settings', 'cps-bloom-mailer')}</p>
                    <TextControl
                        label={__('Host', 'cps-bloom-mailer')}
                        type="text"
                        value={settings.smtp_host || ''}
                        onChange={(value) => handleChange('smtp_host', value)}
                    />
                    <div className="fields-row fields-row--2">
                        <TextControl
                            label={__('Port', 'cps-bloom-mailer')}
                            type="text"
                            value={settings.smtp_port || ''}
                            onChange={(value) => handleChange('smtp_port', value)}
                        />

                        <SelectControl
                            __next40pxDefaultSize={true}
                            label={__('Encryption', 'chicpixies-subscriptions')}
                            value={settings.smtp_encryption || 'smtp'}
                            options={[
                                { label: __('TLS', 'chicpixies-subscriptions'), value: 'tls' },
                                { label: __('SSL', 'chicpixies-subscriptions'), value: 'ssl' },
                                { label: __('None', 'chicpixies-subscriptions'), value: 'none' },
                            ]}
                            onChange={(value) => handleChange('smtp_encryption', value)}
                        />
                    </div>
                    <div className="fields-row fields-row--2">
                        <TextControl
                            label={__('Username', 'cps-bloom-mailer')}
                            type="text"
                            value={settings.smtp_username || ''}
                            onChange={(value) => handleChange('smtp_username', value)}
                        />

                        <TextControl
                            label={__('Password', 'cps-bloom-mailer')}
                            type="password"
                            value={settings.smtp_password || ''}
                            onChange={(value) => handleChange('smtp_password', value)}
                        />

                    </div>
                </div>
            ) : (
                <div className="settings-card__section">
                    <p className="settings-card__label">{__('Amazon SES settings', 'cps-bloom-mailer')}</p>
                    <TextControl
                        label={__('Access Key', 'cps-bloom-mailer')}
                        type="text"
                        value={settings.ses_key || ''}
                        onChange={(value) => handleChange('ses_key', value)}
                    />
                    <TextControl
                        label={__('Secret Key', 'cps-bloom-mailer')}
                        type="password"
                        value={settings.ses_secret || ''}
                        onChange={(value) => handleChange('ses_secret', value)}
                    />
                    <SelectControl
                        __next40pxDefaultSize={true}
                        label={__('Encryption', 'chicpixies-subscriptions')}
                        value={settings.ses_region || 'us-east-1'}
                        options={[
                            { value: 'us-east-1', label: 'US East (N. Virginia)' },
                            { value: 'us-west-2', label: 'US West (Oregon)' },
                            { value: 'eu-west-1', label: 'Europe (Ireland)' },
                            { value: 'eu-central-1', label: 'Europe (Frankfurt)' },
                            { value: 'ap-southeast-1', label: 'Asia Pacific (Singapore)' },
                            { value: 'ap-southeast-2', label: 'Asia Pacific (Sydney)' }
                        ]}
                        onChange={(value) => handleChange('ses_region', value)}
                    />
                    <div className="fields-row">
                        <TextControl
                            label={__('Bounce Handler URL', 'cps-bloom-mailer')}
                            help={__('Please use this bounce handler url in your Amazon SES + SNS settings', 'cps-bloom-mailer')}
                            value={window.cbmData.hook || ''}
                            readonly={true}
                        />
                    </div>
                </div>
            )}

        </div>
    );
}

const GeneralSettings = ({ settings, handleChange }) => {
    return (<></>);
}

const AdvancedSettings = ({ settings, setSettings, activeNotice, loading, setLoading }) => {
    const fileInputRef = useRef(null);
    const [dialogConfig, setDialogConfig] = useState(null);

    const closeDialog = () => {
        setDialogConfig(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleReset = async () => {
        setLoading('reset');
        try {
            const response = await resetData('settings')
            setSettings(response?.settings);
            activeNotice(response.message || __('Reset successfully!', 'cps-bloom-mailer'), 'success');
        } catch (error) {
            console.error(error);
            activeNotice(__('Unable to reset settings', 'cps-bloom-mailer'));
        } finally {
            setLoading(null);
        }
    };

    /**
    * Export settings as JSON file
    */
    const handleExport = () => {
        try {
            exportJSON({
                filename: `cps-bloom-mailer-settings-${Date.now()}`,
                data: {
                    version: '1.0.0',
                    exported_at: new Date().toISOString(),
                    settings: settings,
                },
            });
            activeNotice(__('Settings exported successfully!', 'cps-bloom-mailer'), 'success');
        } catch (error) {
            console.error('Export error:', error);
            activeNotice(__('Failed to export settings. Please try again.', 'cps-bloom-mailer'))
        }
    };

    /**
    * Import settings from JSON file
    */
    const handleImport = async (event) => {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();

        try {
            const importedData = await importJSON({
                file,
                type: 'settings',
            });

            setDialogConfig({
                type: 'import',
                payload: importedData,
                message: __('Are you sure you want to import these settings? This will overwrite your current settings.', 'cps-bloom-mailer')
            });
        } catch (error) {
            console.error(error);
            activeNotice(__('Failed to import settings. Please check the file format.', 'cps-bloom-mailer'));
        } finally {
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        }
    };

    const handleDialogConfirm = async () => {
        if (!dialogConfig) return;

        if (dialogConfig.type === 'reset') {
            handleReset();
        }

        if (dialogConfig.type === 'import') {
            setSettings(dialogConfig.payload.settings);
            activeNotice(__("Settings imported successfully! Don't forget to save your changes.", 'cps-bloom-mailer'), 'success');
        }

        closeDialog();
    };

    return (
        <div className="settings-card">
            <div className="settings-card__section">
                <p className="settings-card__label">{__('Import/Export/Reset', 'cps-bloom-mailer')}</p>
                <div className="fields-row">
                    <div>
                        <Button variant="secondary" onClick={handleExport}>
                            {__('Export Settings', 'cps-bloom-mailer')}
                        </Button>
                        <p style={{ display: 'block', marginTop: '8px' }}>
                            {__('Download your settings as JSON file', 'cps-bloom-mailer')}
                        </p>
                    </div>

                    <div>
                        <input
                            type="file"
                            accept=".json"
                            onChange={handleImport}
                            style={{ display: 'none' }}
                            ref={fileInputRef}
                        />
                        <Button variant="secondary" onClick={() => fileInputRef.current?.click()}>
                            {__('Import Settings', 'cps-bloom-mailer')}
                        </Button>
                        <p style={{ display: 'block', marginTop: '8px' }}>
                            {__('Upload previously exported settings file', 'cps-bloom-mailer')}
                        </p>
                    </div>
                </div>
            </div>
            <div className="settings-card__section">
                <p className="settings-card__label">{__('Danger Zone', 'cps-bloom-mailer')}</p>
                <div>
                    <Button
                        variant="secondary"
                        isDestructive
                        onClick={() => {
                            setDialogConfig({
                                type: 'reset',
                                message: __(`⚠️ WARNING: This will reset ALL settingss to their default values'. This action cannot be undone! Are you sure you want to continue?`, 'cps-bloom-mailer')
                            });
                        }}
                        isBusy={loading === 'reset'}
                        disabled={loading}
                    >
                        {__('Reset Settings', 'cps-bloom-mailer')}
                    </Button>
                </div>
            </div>
            {dialogConfig && (
                <ConfirmDialog
                    isOpen={!!dialogConfig}
                    confirmButtonText={dialogConfig.type === 'reset' ? 'Reset' : 'Import'}
                    onConfirm={handleDialogConfirm}
                    onCancel={closeDialog}
                >
                    <p>{dialogConfig.message}</p>
                </ConfirmDialog>
            )}
        </div>
    );
}

const Settigs = () => {
    const [activeTab, setActiveTab] = useState('sending');
    const [notice, setNotice] = useState([]);
    const [noticeOpen, setNoticeOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [settings, setSettings] = useState(window.cbmData?.settings || {});

    useEffect(() => {
        if (noticeOpen) {
            const timer = setTimeout(() => {
                setNoticeOpen(false);
            }, 5000);
            return () => clearTimeout(timer);
        }
    }, [noticeOpen]);

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get("t");

        if (tab) {
            setActiveTab(tab);
        }
    }, []);

    useEffect(() => {
        const handlePopState = () => {
            const params = new URLSearchParams(window.location.search);
            setActiveTab(params.get('t') || 'sending');
        };

        window.addEventListener('popstate', handlePopState);

        return () => {
            window.removeEventListener('popstate', handlePopState);
        };
    }, []);

    const activeNotice = (msg, type = 'error') => {
        setNotice([msg, type]);
        setNoticeOpen(true);
    }

    const saveSettings = async (newSettings) => {
        setLoading('save');
        try {
            const response = await apiFetch({
                path: '/cps/v1/mailer/settings',
                method: 'POST',
                data: newSettings,
            });

            if (response?.success) {
                setSettings(newSettings);
                activeNotice(response.message || __('Settings saved successfully', 'cps-bloom-mailer'), 'success');
            } else {
                console.warn('Save settings failed:', response);
                activeNotice(response?.message || 'Failed to save settings');
            }
        } catch (error) {
            console.error('Error saving settings:', error);
            activeNotice(__('Error saving settings', 'cps-bloom-mailer'));
        } finally {
            setLoading(null);
        }
    };

    const handleChange = (key, value) => {
        setSettings({ ...settings, [key]: value });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        saveSettings(settings);
    };

    const tabs = [
        {
            name: 'sending',
            title: __('Sending Options', 'cps-bloom-mailer'),
        },
        {
            name: 'mailer',
            title: __('Mailer Settings', 'cps-bloom-mailer'),
        },
        {
            name: 'general',
            title: __('General', 'cps-bloom-mailer'),
        },
        {
            name: 'advanced',
            title: __('Advanced', 'cps-bloom-mailer'),
        },
    ];

    const handleTabChange = (tab) => {
        setActiveTab(tab);

        const params = new URLSearchParams(window.location.search);
        params.set("t", tab);
        params.delete('a');
        params.delete('c');

        window.history.pushState({}, "", `${window.location.pathname}?${params.toString()}`);
    };

    const TAB_COMPONENTS = {
        general: GeneralSettings,
        sending: SendSettings,
        mailer: MailerSettings,
        advanced: AdvancedSettings,
    };

    const ActiveComponent = TAB_COMPONENTS[activeTab] || SendSettings;

    return (
        <>
            <div className="tab-header">
                {tabs.map((tab) => (
                    <button
                        key={tab.name}
                        onClick={() => handleTabChange(tab.name)}
                        className={`tab-button${activeTab === tab.name ? " active" : ""}`}
                    >
                        {tab.title}
                    </button>
                ))}
                <Button
                    type="submit"
                    variant="primary"
                    onClick={handleSubmit}
                    isBusy={loading === 'save'}
                    disabled={loading}
                >
                    {__('Save Settings', 'cps-bloom-mailer')}
                </Button>
            </div>
            {renderNotice({ noticeOpen, notice, setNoticeOpen })}
            <div className="cps-bloom-mailer-page cps-campaigns">
                <div className="cps-bloom-mailer-page__header">
                    <div>
                        <h1 className="cps-bloom-mailer-page__title">
                            {__(`${activeTab} Settings`, 'cps-bloom-mailer')}
                        </h1>
                    </div>
                </div>
                <ActiveComponent
                    settings={settings}
                    handleChange={handleChange}
                    {...(activeTab === 'advanced' ? { setSettings, activeNotice, loading, setLoading } : {})}
                />
            </div>
        </>
    );
};

export default Settigs;