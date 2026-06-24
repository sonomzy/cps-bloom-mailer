import { __ } from '@wordpress/i18n';
import {  useState} from '@wordpress/element';
import { Button, Modal, TextControl } from '@wordpress/components';

export default function TestModal({ emailData, blocks, design, header, footer, sendDemo, loading, onClose }) {
    const [demoEmail, setDemoEmail] = useState(window.wscData?.currentEmail || '');

    return (
        <Modal title={__('Send a Test Email', 'cps-bloom-mailer')} onRequestClose={onClose} size="small">
            <TextControl
                __next40pxDefaultSize={true}
                label={__('Send to', 'cps-bloom-mailer')}
                value={demoEmail}
                onChange={setDemoEmail}
                placeholder="your@email.com"
                type="email"
                help={__('Send yourself a test email to check how it looks across email clients.', 'cps-bloom-mailer')}
                style={{ margin: 0 }}
            />
            <Button
                variant="primary"
                onClick={async () => {
                    if (!demoEmail) return;
                    await sendDemo({
                        to: demoEmail,
                        campaign: { subject: emailData?.subject, preview_text: emailData?.previewText, blocks, design, header, footer },
                    });
                    onClose();
                }}
                isBusy={loading === 'demo'}
                disabled={!demoEmail || !!loading}
                style={{ marginTop: '10px' }}
            >
                {__('Send email', 'cps-bloom-mailer')}
            </Button>
        </Modal>
    );
}

