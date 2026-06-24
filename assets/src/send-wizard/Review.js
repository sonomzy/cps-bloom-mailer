import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Spinner, Notice } from '@wordpress/components';
import { serialize } from '@wordpress/blocks';
import apiFetch from '@wordpress/api-fetch';

function ReviewRow({ label, value, missing }) {
    return (
        <div className={`cps-review-row ${missing ? 'is-missing' : ''}`}>
            <span className="cps-review-row__label">{label}</span>
            <span className="cps-review-row__value">
                {missing
                    ? <em className="cps-review-row__empty">{__('Not set', 'cps-bloom-mailer')}</em>
                    : value}
            </span>
        </div>
    );
}

function RecipientSummary({ recipients }) {
    const format = (rows) => {
        const active = rows.filter(r => r.list !== null || r.tag !== null);
        if (!active.length) return __('All subscribers', 'cps-bloom-mailer');
        return active.map(r => {
            const parts = [];
            if (r.listLabel) parts.push(r.listLabel);
            if (r.tagLabel)  parts.push(`#${r.tagLabel}`);
            return parts.join(' / ') || __('All Lists', 'cps-bloom-mailer');
        }).join(', ');
    };

    return (
        <>
            <ReviewRow
                label={__('Sending to', 'cps-bloom-mailer')}
                value={format(recipients.included)}
            />
            {recipients.excluded?.some(r => r.list || r.tag) && (
                <ReviewRow
                    label={__('Excluding', 'cps-bloom-mailer')}
                    value={format(recipients.excluded)}
                />
            )}
        </>
    );
}

export default function Review({ emailData, recipients, blocksRef, design, header, footer }) {
    const [estimate, setEstimate] = useState(null);
    const [estimating, setEstimating] = useState(false);
    const [estimateError, setEstimateError] = useState(null);

    useEffect(() => {
        let cancelled = false;
        setEstimating(true);

        apiFetch({
            path: '/cps/v1/mailer/recipients/count',
            method: 'POST',
            data: { recipients },
        })
            .then((res) => {
                if (!cancelled) {
                    setEstimate(res?.count ?? null);
                    setEstimateError(null);
                }
            })
            .catch(() => {
                if (!cancelled) setEstimateError(__('Could not estimate recipient count.', 'cps-bloom-mailer'));
            })
            .finally(() => { if (!cancelled) setEstimating(false); });

        return () => { cancelled = true; };
    }, [recipients]);

    const fromDisplay = [emailData.fromName, emailData.fromEmail ? `<${emailData.fromEmail}>` : null]
        .filter(Boolean).join(' ') || null;

    const scheduledDisplay = emailData.scheduledAt
        ? new Date(emailData.scheduledAt).toLocaleString()
        : __('Send immediately', 'cps-bloom-mailer');

    const blockCount = blocksRef.current?.length ?? 0;

    return (
        <div className="cps-wizard-step cps-wizard-step--review">
            <div className="cps-wizard-step__section">
                <h3 className="cps-wizard-step__section-title">
                    {__('Email', 'cps-bloom-mailer')}
                </h3>

                <div className="cps-review-table">
                    <ReviewRow
                        label={__('Subject', 'cps-bloom-mailer')}
                        value={emailData.subject}
                        missing={!emailData.subject}
                    />
                    <ReviewRow
                        label={__('Preview text', 'cps-bloom-mailer')}
                        value={emailData.previewText}
                        missing={!emailData.previewText}
                    />
                    <ReviewRow
                        label={__('From', 'cps-bloom-mailer')}
                        value={fromDisplay}
                        missing={!fromDisplay}
                    />
                    {emailData.replyTo && (
                        <ReviewRow
                            label={__('Reply-To', 'cps-bloom-mailer')}
                            value={emailData.replyTo}
                        />
                    )}
                    <ReviewRow
                        label={__('Content', 'cps-bloom-mailer')}
                        value={__(`${blockCount} block${blockCount !== 1 ? 's' : ''}`, 'cps-bloom-mailer')}
                    />
                </div>
            </div>

            <div className="cps-wizard-step__section">
                <h3 className="cps-wizard-step__section-title">
                    {__('Recipients', 'cps-bloom-mailer')}
                </h3>

                <div className="cps-review-table">
                    <RecipientSummary recipients={recipients} />

                    <div className="cps-review-row cps-review-row--estimate">
                        <span className="cps-review-row__label">
                            {__('Estimated recipients', 'cps-bloom-mailer')}
                        </span>
                        <span className="cps-review-row__value">
                            {estimating && <Spinner />}
                            {!estimating && estimateError && (
                                <em className="cps-review-row__empty">{estimateError}</em>
                            )}
                            {!estimating && estimate !== null && (
                                <strong>{estimate.toLocaleString()}</strong>
                            )}
                        </span>
                    </div>
                </div>
            </div>

            <div className="cps-wizard-step__section">
                <h3 className="cps-wizard-step__section-title">
                    {__('Timing', 'cps-bloom-mailer')}
                </h3>

                <div className="cps-review-table">
                    <ReviewRow
                        label={__('Scheduled for', 'cps-bloom-mailer')}
                        value={scheduledDisplay}
                    />
                </div>
            </div>

            {!emailData.subject && (
                <Notice status="warning" isDismissible={false}>
                    {__('You haven\'t set a subject line. Go back to Settings to add one before sending.', 'cps-bloom-mailer')}
                </Notice>
            )}
        </div>
    );
}