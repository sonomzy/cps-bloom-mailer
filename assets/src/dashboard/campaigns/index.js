import { useRef, useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button, Spinner, Modal, TextControl, SelectControl, CheckboxControl, Dropdown, MenuGroup, MenuItem } from '@wordpress/components';
import { plus, moreVertical, copy, inbox, external, trash } from '@wordpress/icons';
import Campaign from '../campaign';
import './style.scss';
// ---------------------------------------------------------------------------
// Config
// ---------------------------------------------------------------------------

const STATUS_LABELS = {
    draft: __('Draft', 'cps-bloom-mailer'),
    scheduled: __('Scheduled', 'cps-bloom-mailer'),
    sending: __('Sending', 'cps-bloom-mailer'),
    sent: __('Sent', 'cps-bloom-mailer'),
    paused: __('Paused', 'cps-bloom-mailer'),
    failed: __('Failed', 'cps-bloom-mailer'),
};

const STATUS_FILTER_OPTIONS = [
    { value: '', label: __('All statuses', 'cps-bloom-mailer') },
    { value: 'draft', label: __('Draft', 'cps-bloom-mailer') },
    { value: 'scheduled', label: __('Scheduled', 'cps-bloom-mailer') },
    { value: 'sending', label: __('Sending', 'cps-bloom-mailer') },
    { value: 'sent', label: __('Sent', 'cps-bloom-mailer') },
    { value: 'paused', label: __('Paused', 'cps-bloom-mailer') },
    { value: 'failed', label: __('Failed', 'cps-bloom-mailer') },
];

const BULK_ACTION_OPTIONS = [
    { value: '', label: __('Bulk actions', 'cps-bloom-mailer') },
    { value: 'pause', label: __('Pause', 'cps-bloom-mailer') },
    { value: 'cancel', label: __('Cancel', 'cps-bloom-mailer') },
    { value: 'delete', label: __('Delete', 'cps-bloom-mailer') },
    { value: 'export', label: __('Export selected (CSV)', 'cps-bloom-mailer') },
];

function setUrl(id = 0, $tab = 'campaigns', action = '') {
    const params = new URLSearchParams(window.location.search);
    params.set('t', $tab);
    params.set('c', id);
    if (action) {
        params.set('a', action);
    }

    window.history.replaceState({}, "", `${window.location.pathname}?${params.toString()}`);

    return `admin.php?${params.toString()}`;
}

function unSetUrl() {
    const params = new URLSearchParams(window.location.search);
    params.delete('c');
    params.delete('a');
    window.history.replaceState({}, "", `${window.location.pathname}?${params.toString()}`);
}

function formatDate(dateString) {
    if (!dateString) return '—';
    const d = new Date(dateString.replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatNumber(n) {
    return new Intl.NumberFormat().format(n ?? 0);
}

function StatusBadge({ status }) {
    return (
        <span className={`cps-status-badge cps-status-badge--${status}`}>
            {STATUS_LABELS[status] ?? status}
        </span>
    );
}

// ---------------------------------------------------------------------------
// Row actions menu
// ---------------------------------------------------------------------------

function RowActions({ campaign, onSend, onAction, onDuplicate, onResend, onDelete, onViewStats }) {
    const canSend = campaign.status === 'draft' || campaign.status === 'scheduled';
    const canCancel = campaign.status === 'sending';
    const canResend = campaign.status === 'sent';
    const canStats = campaign.status === 'sent';
    const canPause = campaign.status === 'sending' || campaign.status === 'scheduled';

    return (
        <Dropdown
            popoverProps={{ placement: 'bottom-end' }}
            renderToggle={({ isOpen, onToggle }) => (
                <Button
                    icon={moreVertical}
                    label={__('Actions', 'cps-bloom-mailer')}
                    onClick={onToggle}
                    aria-expanded={isOpen}
                    size="small"
                />
            )}
            renderContent={({ onClose }) => (
                <MenuGroup>
                    {canSend && (
                        <MenuItem
                            icon={inbox}
                            onClick={() => { onSend(campaign); onClose(); }}
                        >
                            {__('Send now', 'cps-bloom-mailer')}
                        </MenuItem>
                    )}

                    {canPause && (
                        <MenuItem
                            onClick={() => { onAction(campaign, 'pause'); onClose(); }}
                        >
                            {__('Pause send', 'cps-bloom-mailer')}
                        </MenuItem>
                    )}

                    {canCancel && (
                        <MenuItem
                            onClick={() => { onAction(campaign, 'cancel'); onClose(); }}
                        >
                            {__('Cancel send', 'cps-bloom-mailer')}
                        </MenuItem>
                    )}

                    {canResend && (
                        <MenuItem
                            icon={inbox}
                            onClick={() => { onResend(campaign); onClose(); }}
                        >
                            {__('Resend to non-openers', 'cps-bloom-mailer')}
                        </MenuItem>
                    )}

                    {canStats && (
                        <MenuItem
                            icon={external}
                            onClick={() => { onViewStats(campaign.id); onClose(); }}
                        >
                            {__('View stats', 'cps-bloom-mailer')}
                        </MenuItem>
                    )}

                    <MenuItem
                        icon={copy}
                        onClick={() => { onDuplicate(campaign.id); onClose(); }}
                    >
                        {__('Duplicate', 'cps-bloom-mailer')}
                    </MenuItem>

                    <MenuItem
                        icon={trash}
                        isDestructive
                        onClick={() => { onDelete(campaign); onClose(); }}
                    >
                        {__('Delete', 'cps-bloom-mailer')}
                    </MenuItem>
                </MenuGroup>
            )}
        />
    );
}

// ---------------------------------------------------------------------------
// Resend confirmation modal
// ---------------------------------------------------------------------------

function ResendModal({ campaign, onClose, onConfirm, sending }) {
    const [subject, setSubject] = useState(`(Resend) ${campaign.subject ?? ''}`);

    return (
        <Modal
            title={__('Resend to non-openers', 'cps-bloom-mailer')}
            onRequestClose={onClose}
            className="cps-modal cps-resend-modal"
        >
            <p className="cps-modal__description">
                {__('This creates a new draft campaign with the same content, sent only to people who didn\u2019t open the original.', 'cps-bloom-mailer')}
            </p>

            <TextControl
                label={__('Subject line', 'cps-bloom-mailer')}
                value={subject}
                onChange={setSubject}
                help={__('You can change this — a different subject often performs better on a resend.', 'cps-bloom-mailer')}
                __nextHasNoMarginBottom
            />

            <div className="cps-modal__footer">
                <Button variant="tertiary" onClick={onClose} disabled={sending}>
                    {__('Cancel', 'cps-bloom-mailer')}
                </Button>
                <Button
                    variant="primary"
                    onClick={() => onConfirm(subject)}
                    isBusy={sending}
                    disabled={sending}
                >
                    {__('Create resend', 'cps-bloom-mailer')}
                </Button>
            </div>
        </Modal>
    );
}

// ---------------------------------------------------------------------------
// Delete confirmation modal
// ---------------------------------------------------------------------------

function DeleteModal({ campaign, onClose, onConfirm, deleting }) {
    return (
        <Modal
            title={__('Delete campaign?', 'cps-bloom-mailer')}
            onRequestClose={onClose}
            className="cps-modal cps-delete-modal"
        >
            <p>
                {__('This permanently deletes', 'cps-bloom-mailer')}{' '}
                <strong>{campaign.title || __('(Untitled)', 'cps-bloom-mailer')}</strong>
                {' '}{__('and all of its send history. This can\u2019t be undone.', 'cps-bloom-mailer')}
            </p>
            <div className="cps-modal__footer">
                <Button variant="tertiary" onClick={onClose} disabled={deleting}>
                    {__('Cancel', 'cps-bloom-mailer')}
                </Button>
                <Button
                    variant="primary"
                    isDestructive
                    onClick={onConfirm}
                    isBusy={deleting}
                    disabled={deleting}
                >
                    {__('Delete campaign', 'cps-bloom-mailer')}
                </Button>
            </div>
        </Modal>
    );
}

// ---------------------------------------------------------------------------
// Desktop table row
// ---------------------------------------------------------------------------

function CampaignTableRow({ campaign, selected, onToggleSelect, handleEdit, ...actions }) {
    return (
        <tr className="cps-bloom-mailer-table__row">
            <td className="cps-bloom-mailer-table__cb">
                <CheckboxControl
                    checked={selected}
                    onChange={() => onToggleSelect(campaign.id)}
                    __nextHasNoMarginBottom
                />
            </td>
            <td className="cps-bloom-mailer-table__title">
                <div onClick={() => handleEdit(campaign)}>
                    {campaign.title || __('(Untitled)', 'cps-bloom-mailer')}
                </div>
                <span className="cps-bloom-mailer-table__subject">{campaign.subject}</span>
            </td>
            <td><StatusBadge status={campaign.status} /></td>
            <td className="cps-bloom-mailer-table__num">{formatNumber(campaign.total_recipients)}</td>
            <td>{formatDate(campaign.sent_at || campaign.scheduled_at || campaign.created_at)}</td>
            <td className="cps-bloom-mailer-table__actions">
                <RowActions campaign={campaign} {...actions} />
            </td>
        </tr>
    );
}

// ---------------------------------------------------------------------------
// Mobile stacked card
// ---------------------------------------------------------------------------

function CampaignCard({ campaign, selected, handleEdit, onToggleSelect, ...actions }) {
    return (
        <div className="cps-bloom-mailer-card">
            <div className="cps-bloom-mailer-card__header">
                <CheckboxControl
                    checked={selected}
                    onChange={() => onToggleSelect(campaign.id)}
                    __nextHasNoMarginBottom
                />
                <div className="cps-bloom-mailer-card__heading">
                    <div onClick={() => handleEdit(campaign)} className="cps-bloom-mailer-card__title">
                        {campaign.title || __('(Untitled)', 'cps-bloom-mailer')}
                    </div>
                    <span className="cps-bloom-mailer-card__subject">{campaign.subject}</span>
                </div>
                <RowActions campaign={campaign} {...actions} />
            </div>

            <div className="cps-bloom-mailer-card__meta">
                <StatusBadge status={campaign.status} />
                <span className="cps-bloom-mailer-card__meta-item">
                    {formatNumber(campaign.total_recipients)} {__('recipients', 'cps-bloom-mailer')}
                </span>
                <span className="cps-bloom-mailer-card__meta-item">
                    {formatDate(campaign.sent_at || campaign.scheduled_at || campaign.created_at)}
                </span>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Campaigns Page
// ---------------------------------------------------------------------------

export default function Campaigns({ props }) {
    const { campaigns, setCampaigns, total, setTotal, totalPages, setTotalPages, activeNotice } = props;
    const [page, setPage] = useState(1);
    const [status, setStatus] = useState('');
    const [search, setSearch] = useState('');
    const [searchInput, setSearchInput] = useState('');
    const [orderby, setOrderby] = useState('created_at');
    const [order, setOrder] = useState('DESC');
    const [loading, setLoading] = useState(true);

    const [selectedIds, setSelectedIds] = useState(new Set());
    const [bulkAction, setBulkAction] = useState('');
    const [bulkRunning, setBulkRunning] = useState(false);

    const [resendTarget, setResendTarget] = useState(null);
    const [resending, setResending] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const [isEditorOpen, setIsEditorOpen] = useState(false);
    const [currentCampaign, setCurrentCampaign] = useState(null);

    useEffect(() => {
        fetchCampaigns();
    }, [page, status, search, orderby, order]);

    const fetchCampaigns = async () => {
        setLoading(true);
        const query = new URLSearchParams({
            page: String(page),
            per_page: '20',
            orderby,
            order,
        });
        if (status) query.set('status', status);
        if (search) query.set('search', search);

        apiFetch({ path: `/cps/v1/mailer/campaigns?${query.toString()}` })
            .then((data) => {
                setCampaigns(data.items ?? []);
                setTotal(data.total ?? 0);
                setTotalPages(data.total_pages ?? 1);
            })
            .catch((err) => activeNotice(err?.message ?? __('Failed to load campaigns.', 'cps-bloom-mailer')))
            .finally(() => setLoading(false));
    };

    // Debounce search input → search param
    useEffect(() => {
        const t = setTimeout(() => {
            setSearch(searchInput);
            setPage(1);
        }, 400);
        return () => clearTimeout(t);
    }, [searchInput]);

    function handleStatusChange(value) {
        setStatus(value);
        setPage(1);
    }

    function toggleSort(column) {
        if (orderby === column) {
            setOrder((prev) => (prev === 'ASC' ? 'DESC' : 'ASC'));
        } else {
            setOrderby(column);
            setOrder('ASC');
        }
        setPage(1);
    }

    function toggleSelect(id) {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id); else next.add(id);
            return next;
        });
    }

    function toggleSelectAll() {
        setSelectedIds((prev) =>
            prev.size === campaigns.length ? new Set() : new Set(campaigns.map((c) => c.id))
        );
    }

    const allSelected = campaigns.length > 0 && selectedIds.size === campaigns.length;

    // ---- Single-campaign actions ----

    async function handleSend(campaign) {
        try {
            await apiFetch({ path: `/cps/v1/mailer/campaigns/${campaign.id}/send`, method: 'POST' });
            fetchCampaigns();
        } catch (err) {
            activeNotice(err?.message ?? __('Failed to send campaign.', 'cps-bloom-mailer'));
        }
    }

    async function singleAction(campaign, action) {
        try {
            await apiFetch({ path: `/cps/v1/mailer/campaigns/${campaign.id}/?action=${action}`, method: 'POST' });
            fetchCampaigns();
        } catch (err) {
            activeNotice(err?.message ?? __('Failed to cancel campaign.', 'cps-bloom-mailer'));
        }
    }

    async function handleDuplicate(id) {
        try {
            await apiFetch({ path: `/cps/v1/mailer/campaigns/${id}/duplicate`, method: 'POST' });
            fetchCampaigns();
        } catch (err) {
            activeNotice(err?.message ?? __('Failed to duplicate campaign.', 'cps-bloom-mailer'));
        }
    }

    function handleResendClick(campaign) {
        setResendTarget(campaign);
    }

    async function handleResendConfirm(subject) {
        if (!resendTarget) return;
        setResending(true);
        try {
            await apiFetch({
                path: `/cps/v1/mailer/campaigns/${resendTarget.id}/resend-non-openers`,
                method: 'POST',
                data: { subject },
            });
            setResendTarget(null);
            fetchCampaigns();
        } catch (err) {
            activeNotice(err?.message ?? __('Failed to create resend campaign.', 'cps-bloom-mailer'));
        } finally {
            setResending(false);
        }
    }

    function handleDeleteClick(campaign) {
        setDeleteTarget(campaign);
    }

    async function handleDeleteConfirm() {
        if (!deleteTarget) return;
        setDeleting(true);
        try {
            await apiFetch({ path: `/cps/v1/mailer/campaigns/${deleteTarget.id}?action=delete`, method: 'POST' });
            setDeleteTarget(null);
            fetchCampaigns();
        } catch (err) {
            activeNotice(err?.message ?? __('Failed to delete campaign.', 'cps-bloom-mailer'));
        } finally {
            setDeleting(false);
        }
    }

    function handleViewStats(id) {
        window.location.href = setUrl(id, 'stats');
    }

    // ---- Bulk actions ----

    async function handleBulkApply() {
        if (!bulkAction || selectedIds.size === 0) return;

        if (bulkAction === 'export') {
            const params = new URLSearchParams();
            params.set('ids', Array.from(selectedIds).join(','));
            exportCSV(params);
            return;
        }

        const confirmMessage = {
            delete: __('Delete the selected campaigns? This can\u2019t be undone.', 'cps-bloom-mailer'),
            cancel: __('Cancel sending for the selected campaigns?', 'cps-bloom-mailer'),
            pause: __('Pause the selected campaigns?', 'cps-bloom-mailer'),
        }[bulkAction];

        if (confirmMessage && !window.confirm(confirmMessage)) {
            return;
        }

        setBulkRunning(true);
        try {
            const result = await apiFetch({
                path: '/cps/v1/mailer/campaigns/bulk',
                method: 'POST',
                data: { bulk_action: bulkAction, ids: Array.from(selectedIds) },
            });

            if (result.failed?.length) {
                activeNotice(
                    `${result.failed.length} ${__('campaign(s) could not be updated (likely wrong status for this action).', 'cps-bloom-mailer')}`
                );
            }

            setSelectedIds(new Set());
            setBulkAction('');
            fetchCampaigns();
        } catch (err) {
            activeNotice(err?.message ?? __('Bulk action failed.', 'cps-bloom-mailer'));
        } finally {
            setBulkRunning(false);
        }
    }

    const handleEdit = (campaign, action = 'edit') => {
        setCurrentCampaign(campaign);
        setIsEditorOpen(true);
        setUrl(campaign?.id, 'campaigns', action);
    };

    const handleCloseEditor = () => {
        setIsEditorOpen(false);
        setCurrentCampaign(null);
        unSetUrl();
    };

    const rowActions = {
        onSend: handleSend,
        onAction: singleAction,
        onDuplicate: handleDuplicate,
        onResend: handleResendClick,
        onDelete: handleDeleteClick,
        onViewStats: handleViewStats
    };

    const exportCSV = async (params) => {
        setBulkRunning(true);

        try {
            const query =
                params instanceof URLSearchParams
                    ? params.toString()
                    : String(params || '');

            const baseUrl = window.cbmData.restUrl + '/campaigns/export';
            const url = query ? `${baseUrl}&${query}` : baseUrl;

            const response = await fetch(url, {
                method: 'GET',
                cache: 'no-store',
                headers: {
                    'X-WP-Nonce': window.cbmData.nonce,
                },
            });

            if (!response.ok) {
                throw new Error('Export failed');
            }

            const blob = await response.blob();
            const blobUrl = URL.createObjectURL(blob);

            const link = document.createElement('a');
            link.href = blobUrl;
            const date = new Date().toISOString().split('T')[0];
            link.download = `bloom-campaign-export-${date}.csv`;

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);
        } catch (e) {
            console.error(e);
        } finally {
            setBulkAction('');
        }
    };

    return (
        !isEditorOpen && !currentCampaign ? (
            <div className="cps-bloom-mailer-page cps-campaigns">
                <div className="cps-bloom-mailer-page__header">
                    <div>
                        <h1 className="cps-bloom-mailer-page__title">
                            {__('Campaigns', 'cps-bloom-mailer')}
                        </h1>
                        <p className="cps-bloom-mailer-page__subtitle">
                            {__('Newsletters and broadcasts you\u2019ve sent or are working on.', 'cps-bloom-mailer')}
                        </p>
                    </div>
                    <Button variant="primary" icon={plus} onClick={() => handleEdit({}, 'new')}>
                        {__('New Campaign', 'cps-bloom-mailer')}
                    </Button>
                </div>

                <div className="cps-campaigns__toolbar">
                    <div className="cps-campaigns__toolbar-left">
                        <SelectControl
                            value={bulkAction}
                            options={BULK_ACTION_OPTIONS}
                            onChange={setBulkAction}
                            __nextHasNoMarginBottom
                        />
                        <Button
                            variant="secondary"
                            onClick={handleBulkApply}
                            disabled={!bulkAction || selectedIds.size === 0 || bulkRunning}
                            isBusy={bulkRunning}
                        >
                            {__('Apply', 'cps-bloom-mailer')}
                        </Button>
                        {selectedIds.size > 0 && (
                            <span className="cps-campaigns__selected-count">
                                {selectedIds.size} {__('selected', 'cps-bloom-mailer')}
                            </span>
                        )}
                    </div>

                    <div className="cps-campaigns__toolbar-right">
                        <SelectControl
                            value={status}
                            options={STATUS_FILTER_OPTIONS}
                            onChange={handleStatusChange}
                            __nextHasNoMarginBottom
                        />
                        <TextControl
                            value={searchInput}
                            onChange={setSearchInput}
                            placeholder={__('Search campaigns…', 'cps-bloom-mailer')}
                            __nextHasNoMarginBottom
                        />
                    </div>
                </div>

                {loading && (
                    <div className="cps-bloom-mailer-loading">
                        <Spinner
                            style={{
                                height: 50,
                                width: 50,
                                color: '#000000',
                            }}
                        />
                        <span>{__('Loading campaigns…', 'cps-bloom-mailer')}</span>
                    </div>
                )}

                {!loading && campaigns.length === 0 && (
                    <div className="cps-bloom-mailer-page__empty">
                        <span className="cps-bloom-mailer-page__empty-icon">✉️</span>
                        <h2>{__('No campaigns yet', 'cps-bloom-mailer')}</h2>
                        <p>{__('Create your first campaign to start sending.', 'cps-bloom-mailer')}</p>
                        <Button variant="primary" icon={plus} onClick={() => handleEdit({}, 'new')}>
                            {__('New Campaign', 'cps-bloom-mailer')}
                        </Button>
                    </div>
                )}

                {!loading && campaigns.length > 0 && (
                    <>
                        <table className="cps-bloom-mailer-table">
                            <thead>
                                <tr>
                                    <th className="cps-bloom-mailer-table__cb">
                                        <CheckboxControl
                                            checked={allSelected}
                                            onChange={toggleSelectAll}
                                            __nextHasNoMarginBottom
                                        />
                                    </th>
                                    <th>{__('Campaign', 'cps-bloom-mailer')}</th>
                                    <th>
                                        <button
                                            type="button"
                                            className="cps-bloom-mailer-table__sort-btn"
                                            onClick={() => toggleSort('status')}
                                        >
                                            {__('Status', 'cps-bloom-mailer')}
                                            {orderby === 'status' && (
                                                <span className="cps-bloom-mailer-table__sort-icon">
                                                    {order === 'ASC' ? '▲' : '▼'}
                                                </span>
                                            )}
                                        </button>
                                    </th>
                                    <th>{__('Recipients', 'cps-bloom-mailer')}</th>
                                    <th>{__('Date', 'cps-bloom-mailer')}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                {campaigns.map((campaign) => (
                                    <CampaignTableRow
                                        key={campaign.id}
                                        campaign={campaign}
                                        handleEdit={handleEdit}
                                        selected={selectedIds.has(campaign.id)}
                                        onToggleSelect={toggleSelect}
                                        {...rowActions}
                                    />
                                ))}
                            </tbody>
                        </table>

                        <div className="cps-bloom-mailer-cards">
                            {campaigns.map((campaign) => (
                                <CampaignCard
                                    key={campaign.id}
                                    campaign={campaign}
                                    handleEdit={handleEdit}
                                    selected={selectedIds.has(campaign.id)}
                                    onToggleSelect={toggleSelect}
                                    {...rowActions}
                                />
                            ))}
                        </div>

                        {totalPages > 1 && (
                            <div className="cps-bloom-mailer-page__pagination">
                                <Button
                                    variant="tertiary"
                                    onClick={() => setPage((p) => Math.max(1, p - 1))}
                                    disabled={page <= 1}
                                >
                                    {__('Previous', 'cps-bloom-mailer')}
                                </Button>
                                <span className="cps-bloom-mailer-page__pagination-label">
                                    {__('Page', 'cps-bloom-mailer')} {page} {__('of', 'cps-bloom-mailer')} {totalPages}
                                    {' · '}
                                    {total} {__('total', 'cps-bloom-mailer')}
                                </span>
                                <Button
                                    variant="tertiary"
                                    onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                                    disabled={page >= totalPages}
                                >
                                    {__('Next', 'cps-bloom-mailer')}
                                </Button>
                            </div>
                        )}
                    </>
                )}

                {resendTarget && (
                    <ResendModal
                        campaign={resendTarget}
                        onClose={() => setResendTarget(null)}
                        onConfirm={handleResendConfirm}
                        sending={resending}
                    />
                )}

                {deleteTarget && (
                    <DeleteModal
                        campaign={deleteTarget}
                        onClose={() => setDeleteTarget(null)}
                        onConfirm={handleDeleteConfirm}
                        deleting={deleting}
                    />
                )}
            </div>
        ) : (
            <Campaign
                campaign={currentCampaign}
                onClose={handleCloseEditor}
            />
        )
    );
}