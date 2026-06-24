import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { formatDistance } from 'date-fns';

export default function Actions({ onSave, lastSaved, loading }) {
    return (
        <div className="editor-actions">
            {lastSaved && (
                <span className="editor-actions__saved-time">
                    {__('Saved', 'cps-bloom-mailer')}{' '}
                    {formatDistance(lastSaved, new Date(), { addSuffix: true })}
                </span>
            )}
            <Button
                variant="primary"
                onClick={() => onSave()}
                isBusy={loading === 'save'}
                disabled={!!loading}
            >
                {__('Save', 'cps-bloom-mailer')}
            </Button>
        </div>
    );
}