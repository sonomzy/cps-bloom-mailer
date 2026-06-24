import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button, Notice, Spinner, Modal, SelectControl, TextControl, Dropdown, MenuGroup, MenuItem } from '@wordpress/components';
import { plus, moreVertical, trash } from '@wordpress/icons';
import './style.scss';

// ---------------------------------------------------------------------------
// Event config
// ---------------------------------------------------------------------------

const EVENT_OPTIONS = window.cbmData?.events || [];

function eventLabel(eventValue) {
    return EVENT_OPTIONS.find((e) => e.value === eventValue)?.label ?? eventValue;
}

// ---------------------------------------------------------------------------
// RowActions dropdown
// ---------------------------------------------------------------------------

function RowActions({ automation, onToggle, onDelete }) {
    const isActive = automation.status === 'active';

    return (
        <Dropdown
            popoverProps={{ placement: 'bottom-end' }}
            renderToggle={({ isOpen, onToggle: onDropdownToggle }) => (
                <Button
                    icon={moreVertical}
                    label={__('Actions', 'cps-bloom-mailer')}
                    onClick={onDropdownToggle}
                    aria-expanded={isOpen}
                    size="small"
                />
            )}
            renderContent={({ onClose }) => (
                <MenuGroup>
                    <MenuItem
                        onClick={() => { onToggle(automation.id); onClose(); }}
                    >
                        {isActive ? __('Pause', 'cps-bloom-mailer') : __('Activate', 'cps-bloom-mailer')}
                    </MenuItem>
                    <MenuItem
                        icon={trash}
                        isDestructive
                        onClick={() => { onDelete(automation.id); onClose(); }}
                    >
                        {__('Delete', 'cps-bloom-mailer')}
                    </MenuItem>
                </MenuGroup>
            )}
        />
    );
}

// ---------------------------------------------------------------------------
// AutomationRow — desktop table row
// ---------------------------------------------------------------------------

function AutomationRow({ automation, onToggle, onDelete }) {
    return (
        <tr className={`cps-bloom-mailer-table__row cps-bloom-mailer-table__row--${automation.status}`}>
            <td className="cps-bloom-mailer-table__name">{automation.name}</td>
            <td className="cps-bloom-mailer-table__event">
                <span className="cps-bloom-mailer-table__event-badge">
                    {eventLabel(automation.event)}
                </span>
            </td>
            <td className="cps-bloom-mailer-table__campaign">
                {automation.campaign_title ?? `#${automation.campaign_id}`}
            </td>
            <td className="cps-bloom-mailer-table__status">
                <span className={`cps-status-badge cps-status-badge--${automation.status}`}>
                    {automation.status === 'active'
                        ? __('Active', 'cps-bloom-mailer')
                        : __('Paused', 'cps-bloom-mailer')}
                </span>
            </td>
            <td className="cps-bloom-mailer-table__last-run">
                {automation.last_triggered_at ?? __('Never', 'cps-bloom-mailer')}
            </td>
            <td className="cps-bloom-mailer-table__actions">
                <RowActions automation={automation} onToggle={onToggle} onDelete={onDelete} />
            </td>
        </tr>
    );
}

// ---------------------------------------------------------------------------
// AutomationCard — mobile stacked card
// ---------------------------------------------------------------------------

function AutomationCard({ automation, onToggle, onDelete }) {
    return (
        <div className={`cps-bloom-mailer-card cps-bloom-mailer-card--${automation.status}`}>
            <div className="cps-bloom-mailer-card__header">
                <div className="cps-bloom-mailer-card__heading">
                    <div className="cps-bloom-mailer-card__title">{automation.name}</div>
                    <span className="cps-bloom-mailer-card__subject">
                        {automation.campaign_title ?? `#${automation.campaign_id}`}
                    </span>
                </div>
                <RowActions automation={automation} onToggle={onToggle} onDelete={onDelete} />
            </div>

            <div className="cps-bloom-mailer-card__meta">
                <span className={`cps-status-badge cps-status-badge--${automation.status}`}>
                    {automation.status === 'active'
                        ? __('Active', 'cps-bloom-mailer')
                        : __('Paused', 'cps-bloom-mailer')}
                </span>
                <span className="cps-bloom-mailer-card__meta-item">
                    <span className="cps-bloom-mailer-table__event-badge">
                        {eventLabel(automation.event)}
                    </span>
                </span>
                <span className="cps-bloom-mailer-card__meta-item">
                    {__('Last run:', 'cps-bloom-mailer')}{' '}
                    {automation.last_triggered_at ?? __('Never', 'cps-bloom-mailer')}
                </span>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// CreateModal
// ---------------------------------------------------------------------------

function CreateModal({ onClose, onCreated }) {
    const [name, setName] = useState('');
    const [campaignId, setCampaignId] = useState('');
    const [event, setEvent] = useState('new_subscriber');
    const [campaigns, setCampaigns] = useState([]);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);

    useEffect(() => {
        apiFetch({ path: '/cps/v1/mailer/campaigns?id=-1' })
            .then((data) => setCampaigns(data ?? []))
            .catch(() => {});
    }, []);

    const campaignOptions = [
        { value: '', label: __('— Select a campaign —', 'cps-bloom-mailer') },
        ...campaigns.map((c) => ({ value: String(c.id), label: c.title || `#${c.id}` })),
    ];

    const eventOptions = EVENT_OPTIONS.map((e) => ({ value: e.value, label: e.label }));

    async function handleSave() {
        setError(null);

        if (!name.trim()) {
            setError(__('Please enter a name for this automation.', 'cps-bloom-mailer'));
            return;
        }
        if (!campaignId) {
            setError(__('Please select a campaign.', 'cps-bloom-mailer'));
            return;
        }

        setSaving(true);
        try {
            await apiFetch({
                path: '/cps/v1/mailer/automations/create',
                method: 'POST',
                data: { name: name.trim(), campaign_id: parseInt(campaignId, 10), event },
            });
            onCreated();
            onClose();
        } catch (err) {
            setError(err?.message ?? __('Something went wrong. Please try again.', 'cps-bloom-mailer'));
        } finally {
            setSaving(false);
        }
    }

    const selectedEvent = EVENT_OPTIONS.find((e) => e.value === event);

    return (
        <Modal
            title={__('New Automation', 'cps-bloom-mailer')}
            onRequestClose={onClose}
            className="cps-modal"
        >
            {error && (
                <Notice status="error" isDismissible={false} className="cps-modal-notice">
                    {error}
                </Notice>
            )}

            <TextControl
                label={__('Name', 'cps-bloom-mailer')}
                value={name}
                onChange={setName}
                placeholder={__('e.g. Welcome Email', 'cps-bloom-mailer')}
                __nextHasNoMarginBottom
            />

            <SelectControl
                label={__('Trigger Event', 'cps-bloom-mailer')}
                value={event}
                options={eventOptions}
                onChange={setEvent}
                __nextHasNoMarginBottom
            />

            {selectedEvent?.description && (
                <p className="cps-modal__event-hint">{selectedEvent.description}</p>
            )}

            <SelectControl
                label={__('Campaign', 'cps-bloom-mailer')}
                value={campaignId}
                options={campaignOptions}
                onChange={setCampaignId}
                __nextHasNoMarginBottom
            />

            <div className="cps-modal__footer">
                <Button variant="tertiary" onClick={onClose} disabled={saving}>
                    {__('Cancel', 'cps-bloom-mailer')}
                </Button>
                <Button variant="primary" onClick={handleSave} isBusy={saving} disabled={saving}>
                    {__('Create Automation', 'cps-bloom-mailer')}
                </Button>
            </div>
        </Modal>
    );
}

// ---------------------------------------------------------------------------
// Automations
// ---------------------------------------------------------------------------

export default function Automations({ props }) {
    const { activeNotice, automations, setAutomations } = props;
    const [loading, setLoading] = useState(true);
    const [showCreate, setShowCreate] = useState(false);
    const [page, setPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [totalPages, setTotalPages] = useState(1);

    const PER_PAGE = 20;

    useEffect(() => {
        fetchAutomations();
    }, [page]);

    const fetchAutomations = () => {
        setLoading(true);
        const query = new URLSearchParams({
            page: String(page),
            per_page: String(PER_PAGE),
        });
        apiFetch({ path: `/cps/v1/mailer/automations?${query.toString()}` })
            .then((data) => {
                setAutomations(data.items ?? []);
                setTotal(data.total ?? 0);
                setTotalPages(data.total_pages ?? 1);
            })
            .catch((err) => activeNotice(err?.message ?? __('Failed to load automations.', 'cps-bloom-mailer')))
            .finally(() => setLoading(false));
    };

    async function handleToggle(id) {
        try {
            const res = await apiFetch({
                path: `/cps/v1/mailer/automations/${id}/toggle`,
                method: 'POST',
            });
            setAutomations((prev) =>
                prev.map((a) => (a.id === id ? { ...a, status: res.status } : a))
            );
        } catch {
            // silently fail
        }
    }

    async function handleDelete(id) {
        try {
            await apiFetch({
                path: `/cps/v1/mailer/automations/${id}/delete`,
                method: 'DELETE',
            });
            // If we just deleted the last item on a page beyond page 1, go back
            const remaining = automations.filter((a) => a.id !== id);
            if (remaining.length === 0 && page > 1) {
                setPage((p) => p - 1);
            } else {
                setAutomations(remaining);
                setTotal((t) => t - 1);
            }
        } catch {
            // silently fail
        }
    }

    const rowProps = { onToggle: handleToggle, onDelete: handleDelete };

    return (
        <div className="cps-bloom-mailer-page cps-automations">
            <div className="cps-bloom-mailer-page__header">
                <div>
                    <h1 className="cps-bloom-mailer-page__title">
                        {__('Automations', 'cps-bloom-mailer')}
                    </h1>
                    <p className="cps-bloom-mailer-page__subtitle">
                        {__('Send the right email automatically based on subscriber activity.', 'cps-bloom-mailer')}
                    </p>
                </div>
                <Button variant="primary" icon={plus} onClick={() => setShowCreate(true)}>
                    {__('New Automation', 'cps-bloom-mailer')}
                </Button>
            </div>

            {loading && (
                <div className="cps-bloom-mailer-loading">
                    <Spinner style={{ height: 50, width: 50, color: '#000000' }} />
                    <span>{__('Loading automations…', 'cps-bloom-mailer')}</span>
                </div>
            )}

            {!loading && automations.length === 0 && (
                <div className="cps-bloom-mailer-page__empty">
                    <h2>{__('No automations yet', 'cps-bloom-mailer')}</h2>
                    <p>{__('Create your first automation to start sending emails on autopilot.', 'cps-bloom-mailer')}</p>
                    <Button variant="primary" icon={plus} onClick={() => setShowCreate(true)}>
                        {__('New Automation', 'cps-bloom-mailer')}
                    </Button>
                </div>
            )}

            {!loading && automations.length > 0 && (
                <>
                    {/* Desktop table */}
                    <table className="cps-bloom-mailer-table">
                        <thead>
                            <tr>
                                <th>{__('Name', 'cps-bloom-mailer')}</th>
                                <th>{__('Event', 'cps-bloom-mailer')}</th>
                                <th>{__('Campaign', 'cps-bloom-mailer')}</th>
                                <th>{__('Status', 'cps-bloom-mailer')}</th>
                                <th>{__('Last Run', 'cps-bloom-mailer')}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {automations.map((automation) => (
                                <AutomationRow
                                    key={automation.id}
                                    automation={automation}
                                    {...rowProps}
                                />
                            ))}
                        </tbody>
                    </table>

                    {/* Mobile cards */}
                    <div className="cps-bloom-mailer-cards">
                        {automations.map((automation) => (
                            <AutomationCard
                                key={automation.id}
                                automation={automation}
                                {...rowProps}
                            />
                        ))}
                    </div>

                    {/* Pagination */}
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

            {showCreate && (
                <CreateModal
                    onClose={() => setShowCreate(false)}
                    onCreated={fetchAutomations}
                />
            )}
        </div>
    );
}