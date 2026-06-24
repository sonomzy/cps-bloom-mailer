import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Modal, Spinner, Button } from '@wordpress/components';
import { desktop, tablet, mobile } from '@wordpress/icons';

const PreviewModal = ({ subject, header, blocks, footer, design, close }) => {
    const [previewHtml, setPreviewHtml] = useState('');
    const [loading, setLoading] = useState(false);
    const [previewDevice, setPreviewDevice] = useState('desktop');

    const fetchPreview = async () => {
        setLoading(true);
        try {
            const response = await apiFetch({
                path: `/cps/v1/mailer/preview`,
                method: 'POST',
                data: { subject, header, blocks, footer, design }
            });
            if (response) {
                setPreviewHtml(response.html);
            } else {
                setPreviewHtml(__('Failed to fetch preview', 'cps-bloom-mailer'));
            }
        } catch (error) {
            console.error('Error fetching preview:', error);
            setPreviewHtml('Error fetching preview: ' + error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchPreview();
    }, []);

    const previewButtons = (
        <div
            className="device-preview-buttons"
            style={{ display: 'flex', gap: '5px' }}
        >
            <Button
                icon={desktop}
                variant={previewDevice === 'desktop' ? 'primary' : 'tertiary'}
                onClick={() => setPreviewDevice('desktop')}
                title={__('Desktop Preview', 'cps-bloom-mailer')}
            />

            <Button
                icon={tablet}
                variant={previewDevice === 'tablet' ? 'primary' : 'tertiary'}
                onClick={() => setPreviewDevice('tablet')}
                title={__('Tablet Preview', 'cps-bloom-mailer')}
            />

            <Button
                icon={mobile}
                variant={previewDevice === 'mobile' ? 'primary' : 'tertiary'}
                onClick={() => setPreviewDevice('mobile')}
                title={__('Mobile Preview', 'cps-bloom-mailer')}
            />
        </div>
    );

    return (
        <Modal
            title="Email Preview"
            size="large"
            isScrollable={false}
            isFullScreen={true}
            onRequestClose={close}
            headerActions={previewButtons}
        >
            {loading ? (
                <Spinner>Loading preview...</Spinner>
            ) : (
                <div
                    style={{
                        maxWidth: previewDevice === 'mobile' ? '375px' :
                            previewDevice === 'tablet' ? '768px' : '100%',
                        margin: '0 auto',
                        height: '100%',
                        overflow: 'auto',
                        transition: 'all 0.3s ease'
                    }}
                >
                    <iframe
                        srcDoc={previewHtml}
                        className="preview-iframe"
                        style={{ pointerEvents: 'none' }}
                        allowScripts={false}
                        title="Email Preview"
                        onLoad={(e) => {
                            // resize iframe to its content height so the wrapper div scrolls
                            const doc = e.target.contentDocument;
                            e.target.style.height = doc.documentElement.scrollHeight + 'px';
                        }}
                        sandbox="allow-same-origin"
                    />
                </div>
            )}
        </Modal>
    );
};
export default PreviewModal;