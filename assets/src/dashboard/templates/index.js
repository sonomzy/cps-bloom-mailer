import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { useState, useRef, useEffect } from '@wordpress/element';
import { Button, Card, CardBody, __experimentalConfirmDialog as ConfirmDialog, __experimentalHeading as Heading, __experimentalText as Text } from "@wordpress/components";
import Editor from '../../gutenberg';
import { formatCampaign, resetData, importJSON, exportJSON, loadTemplates } from '../../utils';

const Templates = ({ props }) => {
    const { activeNotice, templates, setTemplates } = props;
    const fileInputRef = useRef(null);
    const { createNotice } = useDispatch('core/notices');
    const [loading, setLoading] = useState(null);
    const [isEditorOpen, setIsEditorOpen] = useState(false);
    const [currentTemplate, setCurrentTemplate] = useState(null);
    const [dialogConfig, setDialogConfig] = useState();

    useEffect(() => {
        getTemplates();
    }, []);

    const getTemplates = async () => {
        try {
            const response = await loadTemplates();
            setTemplates(response);
        } catch (error) {
            console.error(error);
            activeNotice(__('Error fetching templates', 'cps-bloom-mailer'));
        }
    };

    const editorNotice = (msg, type = 'error') => {
        createNotice(type, msg, {
            type: 'snackbar',
            isDismissible: true,
        });
    }

    const saveTemplate = async (templateData) => {
        setLoading('save');
        try {
            const response = await apiFetch({
                path: '/cps/v1/mailer/save',
                method: 'POST',
                data: {
                    id: templateData?.id,
                    data: templateData,
                    template: 'template'
                },
            });

            if (response?.success) {
                setTemplates(prev => ({
                    ...prev,
                    [response.data.id]: response.data
                }));
                setCurrentTemplate(response.data);
                editorNotice(response?.message || __('Template saved successfully', 'cps-bloom-mailer'), 'success');
                return true;
            } else {
                editorNotice(response?.message);
                return false;
            }
        } catch (error) {
            console.error('Error saving template:', error);
            editorNotice(error?.message || __('Error saving template.', 'cps-bloom-mailer'));
            return false;
        } finally {
            setLoading(null);
        }
    };

    const handleReset = async () => {
        setLoading(`reset`);
        try {
            const response = await resetData('templates')
            setTemplates(response?.templates);
            activeNotice(response.message || __('Reset successfully!', 'cps-bloom-mailer'), 'success');
        } catch (error) {
            console.error(error);
            activeNotice(__('Unable to reset templates', 'cps-bloom-mailer'));
        } finally {
            setLoading(null);
        }
    };

    const handleExport = () => {
        try {
            exportJSON({
                filename: `cps-bloom-mailer-templates-${Date.now()}`,
                data: {
                    version: '1.0.0',
                    exported_at: new Date().toISOString(),
                    templates: templates,
                },
            });
            activeNotice(__('Templates exported successfully!', 'cps-bloom-mailer'), 'success');
        } catch (error) {
            console.error('Export error:', error);
            activeNotice(__('Failed to export templates. Please try again.', 'cps-bloom-mailer'));
        }
    };

    const handleImport = async (event) => {
        const file = event.target.files[0];
        if (!file) return;

        try {
            const importedData = await importJSON({
                file,
                type: 'templates',
            });

            setDialogConfig({
                type: 'import',
                payload: importedData,
                message: __(
                    'Are you sure you want to import these templates? This will overwrite your current templates.',
                    'cps-bloom-mailer'
                ),
            });
        } catch (error) {
            activeNotice(error.message);
        } finally {
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        }
    };

    const closeDialog = () => {
        setDialogConfig(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleDialogConfirm = async () => {
        if (!dialogConfig) return;

        if (dialogConfig.type === 'reset') {
            handleReset();
        }

        if (dialogConfig.type === 'import') {
            setTemplates(dialogConfig.payload.templates);
            activeNotice(__("Templates imported successfully! Don't forget to save your changes.", 'cps-bloom-mailer'), 'success');
        }

        closeDialog();
    };

    const handleEditTemplate = (selected = {}) => {
        const formatted = formatCampaign({ campaign: selected, isTemplate: true });
        setCurrentTemplate(formatted);
        setIsEditorOpen(true);
    };

    const handleSaveTemplate = async (templateData, silent = false) => {
        if (!currentTemplate) return;

        await saveTemplate(templateData);
    };

    const handleCloseEditor = () => {
        setIsEditorOpen(false);
        setCurrentTemplate(null);
    };

    return (
        !isEditorOpen && !currentTemplate ? (
            <div className="cps-bloom-mailer-page cps-templates">
                <div className="cps-bloom-mailer-page__header">
                    <div>
                        <h1 className="cps-bloom-mailer-page__title">
                            {__('Templates', 'cps-bloom-mailer')}
                        </h1>
                        <p className="cps-bloom-mailer-page__subtitle">
                            {__('Manage and create campaign templates', 'cps-bloom-mailer')}
                        </p>
                    </div>
                    <div style={{ display: "inline-flex", justifyContent: "flex-end", gap: 10 }}>
                        <div>
                            <input
                                type="file"
                                accept=".json"
                                onChange={handleImport}
                                style={{ display: 'none' }}
                                ref={fileInputRef}
                            />
                            <Button variant="primary" onClick={() => fileInputRef.current?.click()}>
                                {__('Import', 'cps-bloom-mailer')}
                            </Button>
                        </div>
                        <Button variant="secondary" onClick={handleExport}>
                            {__('Export', 'cps-bloom-mailer')}
                        </Button>
                        <Button
                            variant="secondary"
                            isDestructive
                            onClick={() => {
                                setDialogConfig({
                                    type: 'reset',
                                    message: __(`⚠️ WARNING: This will reset ALL email templates to their default values'. This action cannot be undone! Are you sure you want to continue?`, 'cps-bloom-mailer')
                                });
                            }}
                            isBusy={loading === 'reset'}
                            disabled={loading}
                        >
                            {__('Reset', 'cps-bloom-mailer')}
                        </Button>
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
                <div
                    style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(auto-fill, minmax(250px, 1fr))',
                        gap: '12px',
                        margin: '20px 0'
                    }}
                >
                    <Card>
                        <CardBody
                            style={{
                                display: 'grid',
                                gridTemplateRows: '1fr auto',
                                alignItems: 'start',
                                justifyItems: 'center',
                                textAlign: 'center',
                                rowGap: '12px',
                            }}
                        >
                            <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '5px' }}>
                                <Heading level={4}>
                                    {__('Add New Template', 'cps-bloom-mailer')}
                                </Heading>
                                <Text variant="muted" style={{ fontSize: '13px' }}>
                                    {__('Create a new custom email template', 'cps-bloom-mailer')}
                                </Text>
                            </div>
                            <Button
                                variant="secondary"
                                onClick={() => handleEditTemplate()}
                            >
                                {__('New Template', 'cps-bloom-mailer')}
                            </Button>
                        </CardBody>
                    </Card>

                    {typeof templates === 'object' &&
                        Object.values(templates).map((template) => {
                            return (
                                <Card key={template.id}>
                                    <CardBody
                                        style={{
                                            display: 'grid',
                                            gridTemplateRows: '1fr auto',
                                            alignItems: 'start',
                                            justifyItems: 'center',
                                            textAlign: 'center',
                                            rowGap: '12px',
                                        }}
                                    >
                                        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '5px' }}>
                                            <Heading level={4}>
                                                {template?.title}
                                            </Heading>
                                            <Text variant="muted" style={{ fontSize: '13px' }}>
                                                {template?.description}
                                            </Text>
                                        </div>
                                        <Button
                                            variant="secondary"
                                            onClick={() => handleEditTemplate(template)}
                                        >
                                            {__('Edit Template', 'cps-bloom-mailer')}
                                        </Button>
                                    </CardBody>
                                </Card>
                            );
                        })}
                </div>

            </div>
        ) : (
            <Editor
                campaign={currentTemplate}
                onSave={handleSaveTemplate}
                onClose={handleCloseEditor}
                loading={loading}
                isTemplate={true}
            />
        )
    );
};
export default Templates;