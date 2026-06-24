import { __ } from '@wordpress/i18n';
import { TextControl, TextareaControl, ToggleControl } from '@wordpress/components';

export default function Settings({ emailData, setEmailData }) {
    function set(key, value) {
        setEmailData(prev => ({ ...prev, [key]: value }));
    }

    return (
        <div className="cps-wizard-step cps-wizard-step--settings">
            <div className="cps-wizard-step__section">
                <h3 className="cps-wizard-step__section-title">
                    {__('Email Content', 'cps-bloom-mailer')}
                </h3>

                <TextControl
                    label={__('Subject Line', 'cps-bloom-mailer')}
                    help={__('The subject recipients will see in their inbox.', 'cps-bloom-mailer')}
                    value={emailData.subject ?? ''}
                    onChange={(val) => set('subject', val)}
                    className="cps-wizard-field"
                />

                <TextControl
                    label={__('Preview Text', 'cps-bloom-mailer')}
                    help={__('Short summary shown after the subject in most email clients.', 'cps-bloom-mailer')}
                    value={emailData.previewText ?? ''}
                    onChange={(val) => set('previewText', val)}
                    className="cps-wizard-field"
                />
            </div>

            <div className="cps-wizard-step__section">
                <h3 className="cps-wizard-step__section-title">
                    {__('Sender Details', 'cps-bloom-mailer')}
                </h3>

                <div className="cps-wizard-row">
                    <TextControl
                        label={__('From Name', 'cps-bloom-mailer')}
                        value={emailData.fromName ?? ''}
                        placeholder={window.cbmData?.defaultFromName ?? ''}
                        onChange={(val) => set('fromName', val)}
                        className="cps-wizard-field"
                    />
                    <TextControl
                        label={__('From Email', 'cps-bloom-mailer')}
                        type="email"
                        value={emailData.fromEmail ?? ''}
                        placeholder={window.cbmData?.defaultFromEmail ?? ''}
                        onChange={(val) => set('fromEmail', val)}
                        className="cps-wizard-field"
                    />
                </div>

                <TextControl
                    label={__('Reply-To Email', 'cps-bloom-mailer')}
                    type="email"
                    help={__('Leave blank to use the From address.', 'cps-bloom-mailer')}
                    value={emailData.replyTo ?? ''}
                    onChange={(val) => set('replyTo', val)}
                    className="cps-wizard-field"
                />
            </div>

            <div className="cps-wizard-step__section">
                <h3 className="cps-wizard-step__section-title">
                    {__('Scheduling', 'cps-bloom-mailer')}
                </h3>

                <ToggleControl
                    label={__('Schedule for later', 'cps-bloom-mailer')}
                    checked={!!emailData.scheduledAt}
                    onChange={(checked) => set('scheduledAt', checked ? new Date().toISOString() : null)}
                />

                {!!emailData.scheduledAt && (
                    <TextControl
                        label={__('Send at', 'cps-bloom-mailer')}
                        type="datetime-local"
                        value={emailData.scheduledAt
                            ? new Date(emailData.scheduledAt).toISOString().slice(0, 16)
                            : ''}
                        onChange={(val) => set('scheduledAt', val ? new Date(val).toISOString() : null)}
                        className="cps-wizard-field"
                    />
                )}
            </div>
        </div>
    );
}