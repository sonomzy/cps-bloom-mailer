import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

export default function CodeEditor({ value, onChange, onClose }) {
    return (
        <div className="cps-bloom-mailer__code-editor">
            <div className="editor-text-editor__toolbar" style={{ display: 'flex', justifyContent: 'space-between' }}>
                <h2>{__('Editing code')}</h2>
                <Button
                    variant="tertiary"
                    onClick={onClose}
                >
                    {__('Exit Code Editor', 'cps-bloom-mailer')}
                </Button>
            </div>
            <textarea
                className="cps-bloom-mailer__code-editor-textarea"
                value={value}
                autocomplete="off"
                dir="auto"
                id="mail-content"
                placeholder="Start writing with text or HTML"
                onChange={(e) => onChange(e.target.value)}
                spellCheck={false}
                aria-label={__('Email block markup', 'cps-bloom-mailer')}
            />
        </div>
    );
}