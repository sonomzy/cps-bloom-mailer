import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button, Modal } from '@wordpress/components';
import Settings from './Settings';
import Recipients from './Recipients';
import Review from './Review';
import './style.scss';
const STEPS = [
    { key: 'settings', label: __('Settings', 'cps-bloom-mailer') },
    { key: 'recipients', label: __('Recipients', 'cps-bloom-mailer') },
    { key: 'review', label: __('Review', 'cps-bloom-mailer') },
];

export default function SendWizard({ emailData, setEmailData, blocksRef, design, header, footer, loading, onSend, onClose }) {
    const [step, setStep] = useState(0);
    const [recipients, setRecipients] = useState({ included: [{ list: null, tag: null }], excluded: [] });
    const isLast = step === STEPS.length - 1;

    function next() { setStep(s => Math.min(s + 1, STEPS.length - 1)); }
    function back() { setStep(s => Math.max(s - 1, 0)); }

    function handleSend() {
        onSend({ recipients });
    }

    const steps = (
        <div className="cps-bloom-mailer__send-wizard-steps" >
            {
                STEPS.map((s, i) => (
                    <div
                        key={s.key}
                        className={[
                            'cps-bloom-mailer__send-wizard-step',
                            i === step ? 'is-active' : '',
                            i < step ? 'is-complete' : '',
                        ].join(' ')}
                    >
                        <span className="step-number">{i + 1}</span>
                        <span className="step-label">{s.label}</span>
                    </div>
                ))
            }
        </div >
    );

    return (
        <Modal
            title={__('Send Campaign', 'cps-bloom-mailer')}
            size="large"
            onRequestClose={onClose}
            className="cps-bloom-mailer__send-wizard"
            headerActions={steps}
        >

            {/* Step content */}
            <div className="cps-bloom-mailer__send-wizard-body">
                {step === 0 && (
                    <Settings
                        emailData={emailData}
                        setEmailData={setEmailData}
                    />
                )}
                {step === 1 && (
                    <Recipients
                        recipients={recipients}
                        setRecipients={setRecipients}
                    />
                )}
                {step === 2 && (
                    <Review
                        emailData={emailData}
                        recipients={recipients}
                        blocksRef={blocksRef}
                        design={design}
                        header={header}
                        footer={footer}
                    />
                )}
            </div>

            {/* Footer nav */}
            <div className="cps-bloom-mailer__send-wizard-footer">
                <Button variant="tertiary" onClick={onClose}>
                    {__('Cancel', 'cps-bloom-mailer')}
                </Button>

                <div className="cps-bloom-mailer__send-wizard-footer-nav">
                    {step > 0 && (
                        <Button variant="secondary" onClick={back}>
                            {__('Back', 'cps-bloom-mailer')}
                        </Button>
                    )}

                    {!isLast ? (
                        <Button variant="primary" onClick={next}>
                            {__('Continue', 'cps-bloom-mailer')}
                        </Button>
                    ) : (
                        <Button
                            variant="primary"
                            isBusy={loading === 'send'}
                            disabled={!!loading}
                            onClick={handleSend}
                        >
                            {__('Send Campaign', 'cps-bloom-mailer')}
                        </Button>
                    )}
                </div>
            </div>
        </Modal>
    );
}