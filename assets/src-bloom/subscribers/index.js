import { useState, useEffect, useCallback } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
    Button,
    SelectControl,
    TextControl,
    CheckboxControl,
    Spinner,
} from '@wordpress/components';
import { plus, upload } from '@wordpress/icons';
import ImportModal from './ImportModal';

const restUrl = 'cps/v1/bloom/subscribers';
const {
    nonce,
    restUrl: wpRestUrl,
    mailer: canDo,
} = window.cpsBloom;

apiFetch.nonceMiddleware = apiFetch.createNonceMiddleware(nonce);

// ---------------------------------------------------------------------------
// Config
// ---------------------------------------------------------------------------

const STATUS_OPTIONS = [
    { value: '', label: __('All statuses', 'cps-bloom') },
    { value: 'subscribed', label: __('Subscribed', 'cps-bloom') },
    { value: 'unsubscribed', label: __('Unsubscribed', 'cps-bloom') },
    { value: 'bounced', label: __('Bounced', 'cps-bloom') },
];

const BULK_OPTIONS = [
    { value: '', label: __('Bulk actions', 'cps-bloom') },
    { value: 'delete', label: __('Delete', 'cps-bloom') },
    { value: 'export', label: __('Export selected (CSV)', 'cps-bloom') },
];

const ALLOWED_ORDERBY = ['email', 'first_name', 'last_name', 'platform', 'source', 'timezone', 'list', 'created_at'];

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function formatDate(str) {
    if (!str) return '—';
    const d = new Date(str.replace(' ', 'T'));
    if (isNaN(d)) return '—';
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

function StatusBadge({ status }) {
    return (
        <span className={`blm-status-badge blm-status-badge--${status}`}>
            {status || '—'}
        </span>
    );
}

function SortButton({ label, column, orderby, order, onSort }) {
    const active = orderby === column;
    return (
        <button
            type="button"
            className={`cps-bloom-table__sort-btn${active ? ' is-active' : ''}`}
            onClick={() => onSort(column)}
        >
            {label}
            {active && <span className="cps-bloom-table__sort-icon">{order === 'ASC' ? ' ▲' : ' ▼'}</span>}
        </button>
    );
}

// ---------------------------------------------------------------------------
// Table row
// ---------------------------------------------------------------------------

function SubscriberRow({ subscriber, selected, onToggle }) {
    const { tags = [], lists = [] } = subscriber;

    return (
        <tr className="cps-bloom-table__row">
            <td className="cps-bloom-table__cb">
                <CheckboxControl
                    checked={selected}
                    onChange={() => onToggle(subscriber.id)}
                    __nextHasNoMarginBottom
                />
            </td>
            <td className="cps-bloom-table__email">
                <strong>{subscriber.email}</strong>
            </td>
            <td>{subscriber.first_name || '—'}</td>
            <td>{subscriber.last_name || '—'}</td>
            <td><StatusBadge status={subscriber.status} /></td>
            <td>{subscriber.platform || '—'}</td>
            {/* <td>{subscriber.lists || '—'}</td> */}
            <td>
                {lists.length > 0 ? (
                    <div className="blm-lists">
                        {lists.map((l) => (
                            <span key={l.id} className="blm-list">{l.name}</span>
                        ))}
                    </div>
                ) : '—'}
            </td>
            <td>{subscriber.source || '—'}</td>
            <td>{subscriber.timezone || '—'}</td>
            <td>
                {tags.length > 0 ? (
                    <div className="blm-tags">
                        {tags.map((t) => (
                            <span key={t.id} className="blm-tag">{t.name}</span>
                        ))}
                    </div>
                ) : '—'}
            </td>
            <td className="cps-bloom-table__date">{formatDate(subscriber.created_at)}</td>
            <td className="cps-bloom-table__date">{formatDate(subscriber.updated_at)}</td>
        </tr>
    );
}

// ---------------------------------------------------------------------------
// Mobile card
// ---------------------------------------------------------------------------

function SubscriberCard({ subscriber, selected, onToggle }) {
    const { tags = [], lists = [] } = subscriber;

    return (
        <div className="cps-bloom-card">
            <div className="cps-bloom-card__header">
                <CheckboxControl
                    checked={selected}
                    onChange={() => onToggle(subscriber.id)}
                    __nextHasNoMarginBottom
                />
                <div className="cps-bloom-card__heading">
                    <strong className="cps-bloom-card__email">{subscriber.email}</strong>
                    {(subscriber.first_name || subscriber.last_name) && (
                        <span className="cps-bloom-card__name">
                            {[subscriber.first_name, subscriber.last_name].filter(Boolean).join(' ')}
                        </span>
                    )}
                </div>
                <StatusBadge status={subscriber.status} />
            </div>
            <div className="cps-bloom-card__meta">
                {subscriber.platform && (
                    <span className="cps-bloom-card__meta-item">{subscriber.platform}</span>
                )}
                {lists.length > 0 && (
                    <div className="cps-bloom-card__meta-item">
                        {lists.map((l) => <span key={l.id} className="blm-list">{l.name}</span>)}
                    </div>
                )}
                {subscriber.source && (
                    <span className="cps-bloom-card__meta-item">{subscriber.source}</span>
                )}
                {subscriber.timezone && (
                    <span className="cps-bloom-card__meta-item">{subscriber.timezone}</span>
                )}
                <span className="cps-bloom-card__meta-item">{formatDate(subscriber.created_at)}</span>
                <span className="cps-bloom-card__meta-item">{formatDate(subscriber.updated_at)}</span>
            </div>
            {tags.length > 0 && (
                <div className="blm-tags">
                    {tags.map((t) => <span key={t.id} className="blm-tag">{t.name}</span>)}
                </div>
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Notice
// ---------------------------------------------------------------------------

function Notice({ message, type = 'success', onDismiss }) {
    if (!message) return null;
    return (
        <div className={`blm-notice blm-notice--${type}`}>
            <span>{message}</span>
            <button type="button" className="blm-notice__dismiss" onClick={onDismiss}>✕</button>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Main page
// ---------------------------------------------------------------------------

export default function Subscribers({ props }) {
    const { subscribers, setSubscribers, total, setTotal, totalPages, setTotalPages } = props;
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [exporting, setExporting] = useState(false);

    // Filters
    const [search, setSearch] = useState('');
    const [searchInput, setSearchInput] = useState('');
    const [status, setStatus] = useState('');
    const [platform, setPlatform] = useState('');
    const [list, setList] = useState('');
    const [tag, setTag] = useState('');
    const [source, setSource] = useState('');
    const [timezone, setTimezone] = useState('');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [orderby, setOrderby] = useState('created_at');
    const [order, setOrder] = useState('DESC');

    // Filter options
    const [filterOptions, setFilterOptions] = useState({ sources: [], timezones: [], platforms: [], lists: [], tags: [], statuses: [] });

    // Selection
    const [selectedIds, setSelectedIds] = useState(new Set());
    const [bulkAction, setBulkAction] = useState('');
    const [bulkRunning, setBulkRunning] = useState(false);

    // Import modal
    const [importOpen, setImportOpen] = useState(false);

    // Notices
    const [notice, setNotice] = useState(null); // { message, type }

    const showNotice = (message, type = 'success') => setNotice({ message, type });
    const clearNotice = () => setNotice(null);

    // ---- Fetch filter options once ----
    useEffect(() => {
        apiFetch({ path: `${restUrl}/filters` })
            .then((data) => setFilterOptions(data))
            .catch(() => { });
    }, []);

    // ---- Fetch subscribers ----
    const fetchSubscribers = useCallback(() => {
        setLoading(true);
        const params = new URLSearchParams({
            page: String(page),
            per_page: '20',
            orderby,
            order,
        });
        if (search) params.set('search', search);
        if (status) params.set('status', status);
        if (platform) params.set('platform', platform);
        if (list) params.set('list_id', list);
        if (tag) params.set('tag_id', tag);
        if (source) params.set('source', source);
        if (timezone) params.set('timezone', timezone);
        if (dateFrom) params.set('date_from', dateFrom);
        if (dateTo) params.set('date_to', dateTo);

        apiFetch({ path: `${restUrl}?${params.toString()}` })
            .then((data) => {
                setSubscribers(data.items ?? []);
                setTotal(data.total ?? 0);
                setTotalPages(data.total_pages ?? 1);
            })
            .catch((err) => showNotice(err?.message ?? __('Failed to load subscribers.', 'cps-bloom'), 'error'))
            .finally(() => setLoading(false));
    }, [page, search, status, platform, list, tag, source, timezone, dateFrom, dateTo, orderby, order]);

    useEffect(() => { fetchSubscribers(); }, [fetchSubscribers]);

    // Debounce search
    useEffect(() => {
        const t = setTimeout(() => { setSearch(searchInput); setPage(1); }, 400);
        return () => clearTimeout(t);
    }, [searchInput]);

    // ---- Sort ----
    function handleSort(column) {
        if (!ALLOWED_ORDERBY.includes(column)) return;
        if (orderby === column) {
            setOrder((prev) => prev === 'ASC' ? 'DESC' : 'ASC');
        } else {
            setOrderby(column);
            setOrder('ASC');
        }
        setPage(1);
    }

    // ---- Selection ----
    function toggleSelect(id) {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            next.has(id) ? next.delete(id) : next.add(id);
            return next;
        });
    }

    function toggleSelectAll() {
        setSelectedIds((prev) =>
            prev.size === subscribers.length
                ? new Set()
                : new Set(subscribers.map((s) => s.id))
        );
    }

    const allSelected = subscribers.length > 0 && subscribers.every((item) => selectedIds.has(item.id));

    // ---- Bulk actions ----
    async function handleBulkApply() {
        if (!bulkAction || selectedIds.size === 0) return;

        if (bulkAction === 'export') {
            const params = new URLSearchParams();
            params.set('ids', Array.from(selectedIds).join(','));
            exportCSV(params);
            return;
        }

        if (bulkAction === 'delete') {
            if (!window.confirm(__('Delete the selected subscribers? This can\'t be undone.', 'cps-bloom'))) return;

            setBulkRunning(true);
            try {
                const result = await apiFetch({
                    path: `${restUrl}/delete`,
                    method: 'DELETE',
                    data: { ids: Array.from(selectedIds) },
                });
                showNotice(
                    sprintf(__('%d subscriber(s) deleted.', 'cps-bloom'), result.deleted)
                );
                setSelectedIds(new Set());
                setBulkAction('');

                if (page > 1 && subscribers.length === result.deleted) {
                    setPage(page - 1);
                } else {
                    fetchSubscribers();
                }

                // Refresh filter options in case sources changed
                apiFetch({ path: `${restUrl}/filters` }).then(setFilterOptions).catch(() => { });
            } catch (err) {
                showNotice(err?.message ?? __('Delete failed.', 'cps-bloom'), 'error');
            } finally {
                setBulkRunning(false);
            }
        }
    }

    // ---- Export all ----
    async function handleExportAll() {
        const params = new URLSearchParams();
        if (status) params.set('status', status);
        if (platform) params.set('platform', platform);
        if (list) params.set('list_id', list);
        if (tag) params.set('tag_id', tag);
        if (source) params.set('source', source);
        if (timezone) params.set('timezone', timezone);
        if (dateFrom) params.set('date_from', dateFrom);
        if (dateTo) params.set('date_to', dateTo);
        if (search) params.set('search', search);

        exportCSV(params);
    }

    const exportCSV = async (params) => {
        setExporting(true);

        try {
            const query =
                params instanceof URLSearchParams
                    ? params.toString()
                    : String(params || '');

            const baseUrl = wpRestUrl + '/export';
            const url = query ? `${baseUrl}&${query}` : baseUrl;

            const response = await fetch(url, {
                method: 'GET',
                cache: 'no-store',
                headers: {
                    'X-WP-Nonce': nonce,
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
            link.download = `bloom-subscribers-export-${date}.csv`;

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);
        } catch (e) {
            console.error(e);
            showNotice(
                e.message || __('Export failed.', 'cps-bloom'),
                'error'
            );
        } finally {
            setExporting(false);
            setBulkAction('');
        }
    };

    // ---- Import complete ----
    function handleImportComplete(result) {
        if (!canDo) return;
        setImportOpen(false);
        showNotice(
            sprintf(
                __('%d imported, %d updated, %d skipped.', 'cps-bloom'),
                result.imported, result.updated, result.skipped
            )
        );
        fetchSubscribers();
        apiFetch({ path: `${restUrl}/filters` }).then(setFilterOptions).catch(() => { });
    }

    const sortProps = { orderby, order, onSort: handleSort };
    const hasFilters = status || platform || list || tag || source || timezone || dateFrom || dateTo || search;

    function clearFilters() {
        setStatus(''); setPlatform(''); setList(''); setTag(''); setSource('');
        setTimezone(''); setDateFrom(''); setDateTo(''); setSearchInput('');
        setSearch(''); setPage(1);
    }

    // ---- Platform options for select ----
    const platformOptions = [
        { value: '', label: __('All platforms', 'cps-bloom') },
        ...(filterOptions.platforms || []).map((p) => ({ value: p, label: p })),
    ];
    const listOptions = [
        { value: '', label: __('All lists', 'cps-bloom') },
        ...(filterOptions.lists || []).map((l) => ({ value: l.id, label: l.name })),
    ];
    const tagOptions = [
        { value: '', label: __('All Tags', 'cps-bloom') },
        ...(filterOptions.tags || []).map((t) => ({ value: t.id, label: t.name })),
    ];
    const sourceOptions = [
        { value: '', label: __('All sources', 'cps-bloom') },
        ...(filterOptions.sources || []).map((s) => ({ value: s, label: s })),
    ];

    const timezoneOptions = [
        { value: '', label: __('All Timezones', 'cps-bloom') },
        ...(filterOptions.timezones || []).map((l) => ({ value: l, label: l })),
    ];

    const audience = {
        lists: (filterOptions.lists || []).map((l) => ({ value: l.id, label: l.name })),
        tags: (filterOptions.tags || []).map((t) => ({ value: t.id, label: t.name })),
        statuses: (filterOptions.statuses || []).map((st) => ({ value: st, label: st }))
    }

    return (
        <div className="cps-bloom-page">

            {/* Header */}
            <div className="cps-bloom-page__header">
                <div>
                    <h1 className="cps-bloom-page__title">{__('Subscribers', 'cps-bloom')}</h1>
                    <p className="cps-bloom-page__subtitle">
                        {__('All subscribers saved locally, regardless of platform.', 'cps-bloom')}
                    </p>
                </div>
                <div className="cps-bloom-page__header-actions">
                    {canDo && <Button variant="secondary" icon={upload} onClick={() => setImportOpen(true)}>
                        {__('Import CSV', 'cps-bloom')}
                    </Button>}
                    <Button variant="secondary" onClick={handleExportAll} isBusy={exporting}>
                        {__('Export all', 'cps-bloom')}
                    </Button>
                </div>
            </div>

            {/* Notice */}
            {notice && (
                <Notice message={notice.message} type={notice.type} onDismiss={clearNotice} />
            )}

            {/* Toolbar */}
            <div className="cps-bloom-page__toolbar">
                <div className="cps-bloom-page__toolbar-left">
                    <SelectControl
                        __next40pxDefaultSize
                        value={bulkAction}
                        options={BULK_OPTIONS}
                        onChange={setBulkAction}
                        __nextHasNoMarginBottom
                    />
                    <Button
                        variant="secondary"
                        onClick={handleBulkApply}
                        disabled={!bulkAction || selectedIds.size === 0 || bulkRunning}
                        isBusy={bulkRunning}
                    >
                        {__('Apply', 'cps-bloom')}
                    </Button>
                    {selectedIds.size > 0 && (
                        <span className="cps-bloom-page__toolbar-count">
                            {selectedIds.size} {__('selected', 'cps-bloom')}
                        </span>
                    )}
                </div>

                <div className="cps-bloom-page__toolbar-right">
                    <TextControl
                        __next40pxDefaultSize
                        value={searchInput}
                        onChange={(v) => { setSearchInput(v); }}
                        placeholder={__('Search subscribers…', 'cps-bloom')}
                        __nextHasNoMarginBottom
                    />
                    <SelectControl
                        __next40pxDefaultSize
                        value={status}
                        options={STATUS_OPTIONS}
                        onChange={(v) => { setStatus(v); setPage(1); }}
                        __nextHasNoMarginBottom
                    />
                    {filterOptions?.platforms?.length > 0 && (
                        <SelectControl
                            __next40pxDefaultSize
                            value={platform}
                            options={platformOptions}
                            onChange={(v) => { setPlatform(v); setPage(1); }}
                            __nextHasNoMarginBottom
                        />
                    )}
                    {filterOptions?.lists?.length > 0 && (
                        <SelectControl
                            __next40pxDefaultSize
                            value={list}
                            options={listOptions}
                            onChange={(v) => { setList(v); setPage(1); }}
                            __nextHasNoMarginBottom
                        />
                    )}
                    {/* {filterOptions?.tags?.length > 0 && (
                        <SelectControl
                            __next40pxDefaultSize
                            value={tag}
                            options={tagOptions}
                            onChange={(v) => { setTag(v); setPage(1); }}
                            __nextHasNoMarginBottom
                        />
                    )} */}
                    {filterOptions?.sources?.length > 0 && (
                        <SelectControl
                            __next40pxDefaultSize
                            value={source}
                            options={sourceOptions}
                            onChange={(v) => { setSource(v); setPage(1); }}
                            __nextHasNoMarginBottom
                        />
                    )}
                    {filterOptions?.timezones?.length > 0 && (
                        <SelectControl
                            __next40pxDefaultSize
                            value={timezone}
                            options={timezoneOptions}
                            onChange={(v) => { setTimezone(v); setPage(1); }}
                            __nextHasNoMarginBottom
                        />
                    )}
                    <div className="cps-bloom-page__toolbar-date-filters">
                        <input
                            type="date"
                            className="blm-date-input"
                            value={dateFrom}
                            onChange={(e) => { setDateFrom(e.target.value); setPage(1); }}
                            placeholder={__('From', 'cps-bloom')}
                        />
                        <input
                            type="date"
                            className="blm-date-input"
                            value={dateTo}
                            onChange={(e) => { setDateTo(e.target.value); setPage(1); }}
                            placeholder={__('To', 'cps-bloom')}
                        />
                    </div>
                    {hasFilters && (
                        <Button variant="tertiary" onClick={clearFilters}>
                            {__('Clear', 'cps-bloom')}
                        </Button>
                    )}
                </div>
            </div>

            {/* Loading */}
            {loading && (
                <div className="cps-bloom-loading">
                    <Spinner style={{ width: 40, height: 40 }} />
                    <span>{__('Loading…', 'cps-bloom')}</span>
                </div>
            )}

            {/* Empty */}
            {!loading && subscribers.length === 0 && (
                <div className="cps-bloom-page__empty">
                    <span className="cps-bloom-page__empty-icon">📭</span>
                    <h2>{__('No subscribers found', 'cps-bloom')}</h2>
                    <p>{__(`Try adjusting your filters${canDo ? ', or import contacts to get started' : ''}.`, 'cps-bloom')}</p>
                    {canDo && <Button variant="primary" icon={upload} onClick={() => setImportOpen(true)}>
                        {__('Import CSV', 'cps-bloom')}
                    </Button>}
                </div>
            )}

            {/* Desktop table */}
            {!loading && subscribers.length > 0 && (
                <>
                    <table className="cps-bloom-table">
                        <thead>
                            <tr>
                                <th className="cps-bloom-table__cb">
                                    <CheckboxControl
                                        checked={allSelected}
                                        onChange={toggleSelectAll}
                                        __nextHasNoMarginBottom
                                    />
                                </th>
                                <th><SortButton label={__('Email', 'cps-bloom')} column="email"        {...sortProps} /></th>
                                <th><SortButton label={__('First name', 'cps-bloom')} column="first_name"   {...sortProps} /></th>
                                <th><SortButton label={__('Last name', 'cps-bloom')} column="last_name"    {...sortProps} /></th>
                                <th>{__('Status', 'cps-bloom')}</th>
                                <th><SortButton label={__('Platform', 'cps-bloom')} column="platform"     {...sortProps} /></th>
                                <th>{__('List', 'cps-bloom')}</th>
                                <th><SortButton label={__('Source', 'cps-bloom')} column="source"       {...sortProps} /></th>
                                <th><SortButton label={__('Timezone', 'cps-bloom')} column="timezone"       {...sortProps} /></th>
                                <th>{__('Tags', 'cps-bloom')}</th>
                                <th><SortButton label={__('Date Created', 'cps-bloom')} column="created_at" {...sortProps} /></th>
                                <th>{__('Last Changed', 'cps-bloom')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {subscribers.map((s) => (
                                <SubscriberRow
                                    key={s.id}
                                    subscriber={s}
                                    selected={selectedIds.has(s.id)}
                                    onToggle={toggleSelect}
                                />
                            ))}
                        </tbody>
                    </table>

                    {/* Mobile cards */}
                    <div className="cps-bloom-cards">
                        {subscribers.map((s) => (
                            <SubscriberCard
                                key={s.id}
                                subscriber={s}
                                selected={selectedIds.has(s.id)}
                                onToggle={toggleSelect}
                            />
                        ))}
                    </div>

                    {/* Pagination */}
                    {totalPages > 1 && (
                        <div className="cps-bloom-table__pagination">
                            <Button
                                variant="tertiary"
                                onClick={() => setPage((p) => Math.max(1, p - 1))}
                                disabled={page <= 1}
                            >
                                {__('Previous', 'cps-bloom')}
                            </Button>
                            <span className="cps-bloom-table__pagination-label">
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

            {/* Import modal */}
            {canDo && importOpen && (
                <ImportModal
                    onClose={() => setImportOpen(false)}
                    onComplete={handleImportComplete}
                    audience={audience}
                />
            )}
        </div>
    );
}