import { __ } from '@wordpress/i18n';
import { useRef, useState } from '@wordpress/element';
import { Button, __experimentalText as Text } from '@wordpress/components';
import ConfirmActionDialog from './ConfirmActionDialog';
import useNotice from './useNotice';

/**
 * ImportExport
 *
 * Renders Export and Import buttons and owns all the file I/O logic.
 * The parent only needs to supply what data to export and what to do
 * with successfully parsed import data.
 *
 * @param {Object}   props
 * @param {string}   props.filename       - Base filename for the exported file, e.g. 'cps-bloom-export'
 * @param {Function} props.getExportData  - Called on export; should return the object to serialize
 * @param {Function} props.onImport       - Called with the parsed JSON object after the user confirms import
 * @param {Function} props.validateImport - Optional. Called with parsed data; should throw if invalid
 */
const ImportExport = ({ filename = 'cps-export', getExportData, onImport, validateImport }) => {
    const notify = useNotice();
    const fileInputRef = useRef(null);
    const [pendingImport, setPendingImport] = useState(null);

    const handleExport = () => {
        try {
            const data = {
                version: '1.0.0',
                exported_at: new Date().toISOString(),
                ...getExportData(),
            };

            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `${filename}-${Date.now()}.json`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

            notify(__('Exported successfully!', 'cps-bloom-mailer'), 'success');
        } catch (error) {
            console.error('Export error:', error);
            notify(__('Failed to export. Please try again.', 'cps-bloom-mailer'));
        }
    };

    const handleFileChange = (event) => {
        const file = event.target.files[0];
        if (!file) return;

        if (!file.name.endsWith('.json')) {
            notify(__('Please select a valid JSON file.', 'cps-bloom-mailer'));
            return;
        }

        const reader = new FileReader();

        reader.onload = (e) => {
            try {
                const parsed = JSON.parse(e.target.result);

                if (validateImport) {
                    validateImport(parsed); // throws if invalid
                }

                setPendingImport(parsed);
            } catch (error) {
                console.error('Import parse error:', error);
                notify(__('Failed to import. Please check the file format.', 'cps-bloom-mailer'));
            }

            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        };

        reader.onerror = () => {
            notify(__('Failed to read file. Please try again.', 'cps-bloom-mailer'));
        };

        reader.readAsText(file);
    };

    const handleConfirmImport = () => {
        if (pendingImport) {
            onImport(pendingImport);
        }
        setPendingImport(null);
    };

    return (
        <>
            <div style={{ marginBottom: '15px' }}>
                <Button variant="secondary" onClick={handleExport}>
                    {__('Export Settings', 'cps-bloom-mailer')}
                </Button>
                <Text variant="muted" style={{ display: 'block', marginTop: '8px' }}>
                    {__('Download your settings as a JSON file', 'cps-bloom-mailer')}
                </Text>
            </div>

            <div>
                <input
                    type="file"
                    accept=".json"
                    onChange={handleFileChange}
                    style={{ display: 'none' }}
                    ref={fileInputRef}
                />
                <Button variant="secondary" onClick={() => fileInputRef.current?.click()}>
                    {__('Import Settings', 'cps-bloom-mailer')}
                </Button>
                <Text variant="muted" style={{ display: 'block', marginTop: '8px' }}>
                    {__('Upload a previously exported settings file', 'cps-bloom-mailer')}
                </Text>
            </div>

            <ConfirmActionDialog
                isOpen={!!pendingImport}
                message={__('Are you sure you want to import these settings? This will overwrite your current settings.', 'cps-bloom-mailer')}
                confirmLabel={__('Import', 'cps-bloom-mailer')}
                onConfirm={handleConfirmImport}
                onCancel={() => setPendingImport(null)}
            />
        </>
    );
};

export default ImportExport;