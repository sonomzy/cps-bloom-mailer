import { useState, useEffect, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Spinner, Notice } from '@wordpress/components';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function fmt(n) {
    return new Intl.NumberFormat().format(Number(n) || 0);
}

function pct(n) {
    return `${n}%`;
}

function statsUrl(campaignId) {
    const params = new URLSearchParams(window.location.search);
    params.set('page', params.get('page') || 'cps-bloom-mailer');
    if (campaignId) {
        params.set('t', 'stats');
        params.set('c', campaignId);
    } else {
        params.delete('c');
    }
    return `admin.php?${params.toString()}`;
}

function getCampaignIdFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('c');
    return id ? parseInt(id, 10) : null;
}

// ---------------------------------------------------------------------------
// Stat card
// ---------------------------------------------------------------------------

function StatCard({ label, value, sub, color = '#4f46e5' }) {
    return (
        <div className="cps-stat-card">
            <div className="cps-stat-value" style={{ color }}>{value}</div>
            <div className="cps-stat-label">{label}</div>
            {sub && <div className="cps-stat-sub">{sub}</div>}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Sparkline — small trend line, used for opens-over-time and subscriber growth
// ---------------------------------------------------------------------------

function Sparkline({ data, color = '#059669', countKey = 'count' }) {
    const points = useMemo(() => {
        if (!data || data.length === 0) return null;

        const values = data.map((d) => parseInt(d[countKey], 10) || 0);
        const max = Math.max(...values) || 1;
        const w = 260, h = 60, pad = 4;
        const stepX = (w - pad * 2) / Math.max(values.length - 1, 1);

        return values
            .map((v, i) => {
                const x = pad + i * stepX;
                const y = pad + (1 - v / max) * (h - pad * 2);
                return `${x.toFixed(1)},${y.toFixed(1)}`;
            })
            .join(' ');
    }, [data, countKey]);

    if (!points) {
        return <span className="cps-stats__empty-inline">{__('No data yet', 'cps-bloom-mailer')}</span>;
    }

    return (
        <svg viewBox="0 0 260 60" width={260} height={60} xmlns="http://www.w3.org/2000/svg">
            <polyline fill="none" stroke={color} strokeWidth={2} points={points} />
        </svg>
    );
}

// ---------------------------------------------------------------------------
// Bar chart — open rate per recent campaign
// ---------------------------------------------------------------------------

function OpenRateBarChart({ campaigns }) {
    const W = 600, H = 180, PAD = 40, BAR_PAD = 6;

    const bars = useMemo(() => {
        if (!campaigns || campaigns.length === 0) return null;

        const items = [...campaigns].reverse();
        const maxRate = Math.max(...items.map((c) => parseFloat(c.open_rate))) || 1;
        const barW = Math.floor((W - PAD * 2) / items.length) - BAR_PAD;

        return items.map((c, i) => {
            const x = PAD + i * (barW + BAR_PAD);
            const barH = Math.max(2, (parseFloat(c.open_rate) / maxRate) * (H - PAD - 20));
            const y = H - PAD - barH;
            const label = c.title.length > 10 ? `${c.title.substring(0, 10)}…` : c.title;
            const tooltip = `${c.title} — Open: ${c.open_rate}% / Click: ${c.click_rate}%`;

            return { x, y, barW, barH, label, tooltip, openRate: c.open_rate, key: c.id ?? i };
        });
    }, [campaigns]);

    if (!bars) {
        return <div className="cps-bloom-mailer-page__empty">
            <h2>{__('No sent campaigns yet', 'cps-bloom-mailer')}</h2>
        </div>;
    }

    return (
        <svg viewBox={`0 0 ${W} ${H}`} width="100%" xmlns="http://www.w3.org/2000/svg" style={{ maxWidth: 600 }}>
            <text x={0} y={12} fontSize={10} fill="#999">{__('Open rate', 'cps-bloom-mailer')}</text>

            {bars.map((bar) => (
                <g key={bar.key}>
                    <title>{bar.tooltip}</title>
                    <rect x={bar.x} y={bar.y} width={bar.barW} height={bar.barH} fill="#4f46e5" rx={3} />
                    <text
                        x={bar.x + bar.barW / 2}
                        y={H - PAD + 12}
                        textAnchor="middle"
                        fontSize={9}
                        fill="#888"
                    >
                        {bar.label}
                    </text>
                    <text
                        x={bar.x + bar.barW / 2}
                        y={bar.y - 4}
                        textAnchor="middle"
                        fontSize={9}
                        fill="#4f46e5"
                    >
                        {bar.openRate}%
                    </text>
                </g>
            ))}

            <line x1={PAD} y1={H - PAD} x2={W - PAD} y2={H - PAD} stroke="#eee" strokeWidth={1} />
        </svg>
    );
}

// ---------------------------------------------------------------------------
// Overview view
// ---------------------------------------------------------------------------

function OverviewStats({ data }) {
    return (
        <>
            <div className="cps-stats__grid">
                <StatCard label={__('Total Sent', 'cps-bloom-mailer')} value={fmt(data.total_sent)} />
                <StatCard
                    label={__('Avg Open Rate', 'cps-bloom-mailer')}
                    value={pct(data.avg_open_rate)}
                    sub={__('unique opens', 'cps-bloom-mailer')}
                    color="#059669"
                />
                <StatCard
                    label={__('Avg Click Rate', 'cps-bloom-mailer')}
                    value={pct(data.avg_click_rate)}
                    sub={__('unique clicks', 'cps-bloom-mailer')}
                    color="#0284c7"
                />
                <StatCard
                    label={__('Unsubscribes', 'cps-bloom-mailer')}
                    value={fmt(data.total_unsubs)}
                    color="#dc2626"
                />
                <StatCard label={__('Campaigns Sent', 'cps-bloom-mailer')} value={fmt(data.total_campaigns)} />
            </div>

            {data.subscriber_growth?.length > 0 && (
                <div className="cps-stats__section">
                    <h3>{__('New Subscribers (last 30 days)', 'cps-bloom-mailer')}</h3>
                    <Sparkline data={data.subscriber_growth} color="#059669" />
                </div>
            )}

            {data.recent_campaigns?.length > 0 && (
                <>
                    <div className="cps-stats__section">
                        <h3>{__('Recent Campaign Open Rates', 'cps-bloom-mailer')}</h3>
                        <OpenRateBarChart campaigns={data.recent_campaigns} />
                    </div>

                    <div className="cps-stats__section">
                        <h3>{__('Recent Campaigns', 'cps-bloom-mailer')}</h3>

                        <table className="cps-bloom-mailer-table">
                            <thead>
                                <tr>
                                    <th>{__('Campaign', 'cps-bloom-mailer')}</th>
                                    <th>{__('Sent', 'cps-bloom-mailer')}</th>
                                    <th>{__('Recipients', 'cps-bloom-mailer')}</th>
                                    <th>{__('Open Rate', 'cps-bloom-mailer')}</th>
                                    <th>{__('Click Rate', 'cps-bloom-mailer')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {data.recent_campaigns.map((c) => (
                                    <tr key={c.id}>
                                        <td><a href={statsUrl(c.id)}>{c.title}</a></td>
                                        <td>{c.sent_at ? c.sent_at.substring(0, 10) : '—'}</td>
                                        <td>{fmt(c.total_recipients)}</td>
                                        <td><strong className="cps-stats__open-rate">{pct(c.open_rate)}</strong></td>
                                        <td>{pct(c.click_rate)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* Mobile cards — same responsive pattern as Campaigns/Suppressions */}
                        <div className="cps-bloom-mailer-cards">
                            {data.recent_campaigns.map((c) => (
                                <div className="cps-bloom-mailer-card" key={c.id}>
                                    <a href={statsUrl(c.id)} className="cps-bloom-mailer-card__title">{c.title}</a>
                                    <div className="cps-bloom-mailer-card__row">
                                        <span>{__('Sent', 'cps-bloom-mailer')}</span>
                                        <span>{c.sent_at ? c.sent_at.substring(0, 10) : '—'}</span>
                                    </div>
                                    <div className="cps-bloom-mailer-card__row">
                                        <span>{__('Recipients', 'cps-bloom-mailer')}</span>
                                        <span>{fmt(c.total_recipients)}</span>
                                    </div>
                                    <div className="cps-bloom-mailer-card__row">
                                        <span>{__('Open Rate', 'cps-bloom-mailer')}</span>
                                        <span className="cps-stats__open-rate">{pct(c.open_rate)}</span>
                                    </div>
                                    <div className="cps-bloom-mailer-card__row">
                                        <span>{__('Click Rate', 'cps-bloom-mailer')}</span>
                                        <span>{pct(c.click_rate)}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </>
            )}
        </>
    );
}

// ---------------------------------------------------------------------------
// Single campaign view
// ---------------------------------------------------------------------------

function CampaignStats({ stats }) {
    return (
        <>
            <div className="cps-stats__grid">
                <StatCard label={__('Delivered', 'cps-bloom-mailer')} value={fmt(stats.total_sent)} />
                <StatCard
                    label={__('Open Rate', 'cps-bloom-mailer')}
                    value={pct(stats.open_rate)}
                    sub={`${fmt(stats.unique_opens)} ${__('unique opens', 'cps-bloom-mailer')}`}
                    color="#059669"
                />
                <StatCard
                    label={__('Click Rate', 'cps-bloom-mailer')}
                    value={pct(stats.click_rate)}
                    sub={`${fmt(stats.unique_clicks)} ${__('unique clicks', 'cps-bloom-mailer')}`}
                    color="#0284c7"
                />
                <StatCard
                    label={__('CTOR', 'cps-bloom-mailer')}
                    value={pct(stats.ctor)}
                    sub={__('click-to-open rate', 'cps-bloom-mailer')}
                    color="#7c3aed"
                />
                <StatCard
                    label={__('Unsubscribes', 'cps-bloom-mailer')}
                    value={fmt(stats.unsubscribes)}
                    color="#dc2626"
                />
                <StatCard
                    label={__('Failed', 'cps-bloom-mailer')}
                    value={fmt(stats.total_failed)}
                    color="#9ca3af"
                />
            </div>

            {stats.opens_over_time?.length > 0 && (
                <div className="cps-stats__section">
                    <h3>{__('Opens Over Time', 'cps-bloom-mailer')}</h3>
                    <Sparkline data={stats.opens_over_time} color="#059669" />
                </div>
            )}

            {stats.top_links?.length > 0 && (
                <div className="cps-stats__section">
                    <h3>{__('Top Clicked Links', 'cps-bloom-mailer')}</h3>

                    <table className="cps-bloom-mailer-table">
                        <thead>
                            <tr>
                                <th>{__('URL', 'cps-bloom-mailer')}</th>
                                <th className="cps-bloom-mailer-table__num-col">{__('Clicks', 'cps-bloom-mailer')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {stats.top_links.map((link, i) => {
                                const display = link.url.length > 80
                                    ? `${link.url.substring(0, 80)}…`
                                    : link.url;
                                return (
                                    <tr key={i}>
                                        <td>
                                            <a href={link.url} target="_blank" rel="noopener noreferrer">
                                                {display}
                                            </a>
                                        </td>
                                        <td>{link.clicks}</td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>

                    <div className="cps-bloom-mailer-cards">
                        {stats.top_links.map((link, i) => (
                            <div className="cps-stats__card" key={i}>
                                <a
                                    href={link.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="cps-bloom-mailer-card__link"
                                >
                                    {link.url}
                                </a>
                                <div className="cps-bloom-mailer-card__row">
                                    <span>{__('Clicks', 'cps-bloom-mailer')}</span>
                                    <span>{link.clicks}</span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </>
    );
}

// ---------------------------------------------------------------------------
// StatsPage
// ---------------------------------------------------------------------------

export default function StatsPage({ props: { activeNotice } }) {
    const campaignId = getCampaignIdFromUrl();
    const [overview, setOverview] = useState(null);
    const [campaign, setCampaign] = useState(null);
    const [campaignStats, setCampaignStats] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        setLoading(true);
        const path = campaignId
            ? `/cps/v1/mailer/stats/campaigns/${campaignId}`
            : '/cps/v1/mailer/stats/overview';

        apiFetch({ path })
            .then((data) => {
                if (campaignId) {
                    setCampaign(data.campaign);
                    setCampaignStats(data.stats);
                } else {
                    setOverview(data);
                }
            })
            .catch((err) => activeNotice(err?.message ?? __('Failed to load stats.', 'cps-bloom-mailer')))
            .finally(() => setLoading(false));
    }, [campaignId]);

    return (
        <div className="cps-bloom-mailer-page cps-stats">
            <div className="cps-bloom-mailer-page__header">
                <div>
                    <h1 className="cps-bloom-mailer-page__title">
                        {campaignId ? (
                            <>
                                {__('Stats: ', 'cps-bloom-mailer')}{campaign?.title ?? ''}
                                <a href={statsUrl(null)} className="page-title-action">
                                    {__('Overview', 'cps-bloom-mailer')}
                                </a>
                            </>
                        ) : (
                            __('Stats Overview', 'cps-bloom-mailer')
                        )}
                    </h1>
                </div>
            </div>

            {loading && (
                <div className="cps-bloom-mailer-loading">
                    <Spinner
                        style={{
                            height: 30,
                            width: 30,
                            color: '#000000',
                        }}
                    />
                    <span>{__('Loading stats…', 'cps-bloom-mailer')}</span>
                </div>
            )}

            {!loading && (
                campaignId
                    ? (campaignStats && <CampaignStats stats={campaignStats} />)
                    : (overview && <OverviewStats data={overview} />)
            )}
        </div>
    );
}