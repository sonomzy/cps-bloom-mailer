import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Button, SelectControl, Spinner, Notice } from '@wordpress/components';
import { trash, plus } from '@wordpress/icons';
import apiFetch from '@wordpress/api-fetch';

const ALL_LISTS = '';
const ALL_TAGS  = '';

function RecipientRow({ row, lists, tags, onChange, onRemove, removable }) {
    const tagOptions = [
        { label: __('All contacts on selected list', 'cps-bloom-mailer'), value: '' },
        ...tags.map(t => ({ label: t.title, value: String(t.id) })),
    ];

    const listOptions = [
        { label: __('All Lists', 'cps-bloom-mailer'), value: '' },
        ...lists.map(l => ({ label: l.title, value: String(l.id) })),
    ];

    return (
        <div className="cps-recipient-row">
            <SelectControl
                label={__('List', 'cps-bloom-mailer')}
                hideLabelFromVision
                value={row.list ?? ALL_LISTS}
                options={listOptions}
                onChange={(val) => onChange({ ...row, list: val || null })}
                className="cps-recipient-row__list"
            />
            <SelectControl
                label={__('Tag', 'cps-bloom-mailer')}
                hideLabelFromVision
                value={row.tag ?? ALL_TAGS}
                options={tagOptions}
                onChange={(val) => onChange({ ...row, tag: val || null })}
                className="cps-recipient-row__tag"
            />
            {removable && (
                <Button
                    icon={trash}
                    isDestructive
                    variant="tertiary"
                    label={__('Remove row', 'cps-bloom-mailer')}
                    onClick={onRemove}
                    className="cps-recipient-row__remove"
                />
            )}
        </div>
    );
}

function RecipientGroup({ title, description, rows, lists, tags, onChange }) {
    function updateRow(i, updated) {
        const next = [...rows];
        next[i] = updated;
        onChange(next);
    }

    function addRow() {
        onChange([...rows, { list: null, tag: null }]);
    }

    function removeRow(i) {
        onChange(rows.filter((_, idx) => idx !== i));
    }

    return (
        <div className="cps-wizard-step__section">
            <h3 className="cps-wizard-step__section-title">{title}</h3>
            {description && (
                <p className="cps-wizard-step__section-description">{description}</p>
            )}

            <div className="cps-recipient-rows__header">
                <span>{__('Select A List', 'cps-bloom-mailer')}</span>
                <span>{__('Select Tag', 'cps-bloom-mailer')}</span>
            </div>

            {rows.map((row, i) => (
                <RecipientRow
                    key={i}
                    row={row}
                    lists={lists}
                    tags={tags}
                    onChange={(updated) => updateRow(i, updated)}
                    onRemove={() => removeRow(i)}
                    removable={rows.length > 1 || i > 0}
                />
            ))}

            <Button
                variant="tertiary"
                icon={plus}
                onClick={addRow}
                className="cps-recipient-add-row"
            >
                {__('Add More', 'cps-bloom-mailer')}
            </Button>
        </div>
    );
}

export default function Recipients({ recipients, setRecipients }) {
    const [lists, setLists] = useState([]);
    const [tags,  setTags]  = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        let cancelled = false;
        setLoading(true);

        Promise.all([
            apiFetch({ path: '/cps/v1/mailer/lists' }),
            apiFetch({ path: '/cps/v1/mailer/tags' }),
        ])
            .then(([listsData, tagsData]) => {
                if (cancelled) return;
                setLists(listsData ?? []);
                setTags(tagsData ?? []);
                setError(null);
            })
            .catch((err) => {
                if (cancelled) return;
                setError(err?.message ?? __('Failed to load lists and tags.', 'cps-bloom-mailer'));
            })
            .finally(() => {
                if (!cancelled) setLoading(false);
            });

        return () => { cancelled = true; };
    }, []);

    if (loading) {
        return (
            <div className="cps-wizard-loading">
                <Spinner />
                <span>{__('Loading lists and tags…', 'cps-bloom-mailer')}</span>
            </div>
        );
    }

    if (error) {
        return (
            <Notice status="error" isDismissible={false}>
                {error}
            </Notice>
        );
    }

    return (
        <div className="cps-wizard-step cps-wizard-step--recipients">
            <RecipientGroup
                title={__('Included Contacts', 'cps-bloom-mailer')}
                description={__('Select the lists and tags to send this campaign to. Add multiple rows to combine segments.', 'cps-bloom-mailer')}
                rows={recipients.included.length ? recipients.included : [{ list: null, tag: null }]}
                lists={lists}
                tags={tags}
                onChange={(rows) => setRecipients(r => ({ ...r, included: rows }))}
            />

            <RecipientGroup
                title={__('Excluded Contacts', 'cps-bloom-mailer')}
                description={__('Contacts matching these lists or tags will be removed from the send list.', 'cps-bloom-mailer')}
                rows={recipients.excluded.length ? recipients.excluded : [{ list: null, tag: null }]}
                lists={lists}
                tags={tags}
                onChange={(rows) => setRecipients(r => ({ ...r, excluded: rows }))}
            />
        </div>
    );
}