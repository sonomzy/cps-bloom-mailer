import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button, Notice, Spinner, Modal, TextControl, Dropdown, MenuGroup, MenuItem, TextareaControl } from '@wordpress/components';
import { plus, moreVertical, trash } from '@wordpress/icons';

const canDo = window.cpsBloom.mailer;
// ---------------------------------------------------------------------------
// RowActions dropdown
// ---------------------------------------------------------------------------
function RowActions({ tag, onDelete }) {
    if (!canDo) return;
    return (
        <Dropdown
            popoverProps={{ placement: 'bottom-end' }}
            renderToggle={({ isOpen, onToggle: onDropdownToggle }) => (
                <Button
                    icon={moreVertical}
                    label={__('Actions', 'cps-bloom')}
                    onClick={onDropdownToggle}
                    aria-expanded={isOpen}
                    size="small"
                />
            )}
            renderContent={({ onClose }) => (
                <MenuGroup>
                    <MenuItem
                        icon={trash}
                        isDestructive
                        onClick={() => { onDelete(tag.id); onClose(); }}
                    >
                        {__('Delete', 'cps-bloom')}
                    </MenuItem>
                </MenuGroup>
            )}
        />
    );
}

// ---------------------------------------------------------------------------
// TagRow — desktop table row
// ---------------------------------------------------------------------------

function TagRow({ tag, onDelete }) {
    return (
        <tr className={`cps-bloom-table__row cps-bloom-table__row--tag`}>
            <td className="cps-bloom-table__id">{tag.id}</td>
            <td className="cps-bloom-table__name">{tag.name}</td>

            <td className="cps-bloom-table__slug">
                {tag.slug}
            </td>
            <td className="cps-bloom-table__subscribers">
                {tag?.subscribers}
            </td>
            <td className="cps-bloom-table__created">
                {tag.created_at}
            </td>
            {canDo && <td className="cps-bloom-table__actions">
                <RowActions tag={tag} onDelete={onDelete} />
            </td>}
        </tr>
    );
}

// ---------------------------------------------------------------------------
// TagCard — mobile stacked card
// ---------------------------------------------------------------------------

function TagCard({ tag, onDelete }) {
    return (
        <div className={`cps-bloom-card cps-bloom-card--tag`}>
            <div className="cps-bloom-card__header">
                <div className="cps-bloom-card__heading">
                    <div className="cps-bloom-card__title">{tag.name}</div>
                    <span className="cps-bloom-card__created">
                        {tag.created_at}
                    </span>
                </div>
                {canDo && <RowActions tag={tag} onDelete={onDelete} />}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// CreateModal
// ---------------------------------------------------------------------------

function CreateModal({ onClose, onCreated }) {
    const [name, setName] = useState('');
    const [slug, setSlug] = useState('');
    const [description, setDescription] = useState('');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);

    if (!canDo) return;
    
    async function handleSave() {
        setError(null);

        if (!name.trim()) {
            setError(__('Please enter a name for this tag.', 'cps-bloom'));
            return;
        }

        setSaving(true);
        try {
            await apiFetch({
                path: '/cps/v1/bloom/tags/create',
                method: 'POST',
                data: { name: name.trim(), slug: slug.trim(), description: description.trim() },
            });
            onCreated();
            onClose();
        } catch (err) {
            setError(err?.message ?? __('Something went wrong. Please try again.', 'cps-bloom'));
        } finally {
            setSaving(false);
        }
    }

    return (
        <Modal
            title={__('New Tag', 'cps-bloom')}
            onRequestClose={onClose}
            className="cps-modal"
        >
            {error && (
                <Notice status="error" isDismissible={false} className="cps-modal-notice">
                    {error}
                </Notice>
            )}

            <TextControl
                label={__('Name', 'cps-bloom')}
                value={name}
                onChange={setName}
                required={true}
                __nextHasNoMarginBottom
            />
            <TextControl
                label={__('Slug', 'cps-bloom')}
                value={slug || name}
                onChange={setSlug}
                __nextHasNoMarginBottom
            />
            <TextareaControl
                label={__('Description', 'cps-bloom')}
                value={description}
                onChange={setDescription}
                rows={2}
            />

            <div className="cps-modal__footer">
                <Button variant="tertiary" onClick={onClose} disabled={saving}>
                    {__('Cancel', 'cps-bloom')}
                </Button>
                <Button variant="primary" onClick={handleSave} isBusy={saving} disabled={saving}>
                    {__('Create Tag', 'cps-bloom')}
                </Button>
            </div>
        </Modal>
    );
}

// ---------------------------------------------------------------------------
// Tags
// ---------------------------------------------------------------------------

export default function Tags({ props }) {
    const { tags, setTags } = props;
    const [loading, setLoading] = useState(true);
    const [showCreate, setShowCreate] = useState(false);
    const [page, setPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [totalPages, setTotalPages] = useState(1);

    const PER_PAGE = 20;

    useEffect(() => {
        fetchTags();
    }, [page]);

    const fetchTags = () => {
        setLoading(true);
        const query = new URLSearchParams({
            page: String(page),
            per_page: String(PER_PAGE),
        });
        apiFetch({ path: `/cps/v1/bloom/tags?${query.toString()}` })
            .then((data) => {
                setTags(data.items ?? []);
                setTotal(data.total ?? 0);
                setTotalPages(data.total_pages ?? 1);
            })
            .catch((err) => { console.error(err?.message ?? __('Failed to load tags.', 'cps-bloom')) })
            .finally(() => setLoading(false));
    };

    async function handleDelete(id) {
        if (!canDo) return;
        try {
            await apiFetch({
                path: `/cps/v1/bloom/tags/${id}/delete`,
                method: 'DELETE',
            });
            // If we just deleted the last item on a page beyond page 1, go back
            const remaining = tags.filter((a) => a.id !== id);
            if (remaining.length === 0 && page > 1) {
                setPage((p) => p - 1);
            } else {
                setTags(remaining);
                setTotal((t) => t - 1);
            }
        } catch {
            // silently fail
        }
    }

    const rowProps = { onDelete: handleDelete };

    return (
        <div className="cps-bloom-page cps-tags">
            <div className="cps-bloom-page__header">
                <div>
                    <h1 className="cps-bloom-page__title">
                        {__('Tags', 'cps-bloom')}
                    </h1>
                    <p className="cps-bloom-page__subtitle"></p>
                </div>
                {canDo &&
                    <Button variant="primary" icon={plus} onClick={() => setShowCreate(true)}>
                        {__('New Tag', 'cps-bloom')}
                    </Button>
                }
            </div>

            {loading && (
                <div className="cps-bloom-loading">
                    <Spinner style={{ height: 50, width: 50, color: '#000000' }} />
                    <span>{__('Loading tags…', 'cps-bloom')}</span>
                </div>
            )}

            {!loading && tags.length === 0 && (
                <div className="cps-bloom-page__empty">
                    <h2>{__('No tags yet', 'cps-bloom')}</h2>

                    {canDo && (
                        <>
                            <p>{__('Create your first tag.', 'cps-bloom')}</p>
                            <Button variant="primary" icon={plus} onClick={() => setShowCreate(true)}>
                                {__('New Tag', 'cps-bloom')}
                            </Button>
                        </>
                    )}
                </div>
            )}

            {!loading && tags.length > 0 && (
                <>
                    {/* Desktop table */}
                    <table className="cps-bloom-table">
                        <thead>
                            <tr>
                                <th>{__('ID', 'cps-bloom')}</th>
                                <th>{__('Name', 'cps-bloom')}</th>
                                <th>{__('Slug', 'cps-bloom')}</th>
                                <th>{__('Subscribers', 'cps-bloom')}</th>
                                <th>{__('Created', 'cps-bloom')}</th>
                                {canDo && <th></th>}
                            </tr>
                        </thead>
                        <tbody>
                            {tags.map((tag) => (
                                <TagRow
                                    key={tag.id}
                                    tag={tag}
                                    {...rowProps}
                                />
                            ))}
                        </tbody>
                    </table>

                    {/* Mobile cards */}
                    <div className="cps-bloom-cards">
                        {tags.map((tag) => (
                            <TagCard
                                key={tag.id}
                                tag={tag}
                                {...rowProps}
                            />
                        ))}
                    </div>

                    {/* Pagination */}
                    {totalPages > 1 && (
                        <div className="cps-bloom-page__pagination">
                            <Button
                                variant="tertiary"
                                onClick={() => setPage((p) => Math.max(1, p - 1))}
                                disabled={page <= 1}
                            >
                                {__('Previous', 'cps-bloom')}
                            </Button>
                            <span className="cps-bloom-page__pagination-label">
                                {__('Page', 'cps-bloom')} {page} {__('of', 'cps-bloom')} {totalPages}
                                {' · '}
                                {total} {__('total', 'cps-bloom')}
                            </span>
                            <Button
                                variant="tertiary"
                                onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                                disabled={page >= totalPages}
                            >
                                {__('Next', 'cps-bloom')}
                            </Button>
                        </div>
                    )}
                </>
            )}

            {showCreate && (
                <CreateModal
                    onClose={() => setShowCreate(false)}
                    onCreated={fetchTags}
                />
            )}
        </div>
    );
}