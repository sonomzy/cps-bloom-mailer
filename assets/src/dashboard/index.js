import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import Automations from './automations';
import Campaigns from './campaigns';
import Templates from './templates';
import StatsPage from './stats';
import './style.scss';
import { renderNotice } from '../utils';

const Dashboard = () => {
    const [loading, setLoading] = useState(true);
    const [activeTab, setActiveTab] = useState('campaigns');
    const [notice, setNotice] = useState([]);
    const [noticeOpen, setNoticeOpen] = useState(false);
    //campaigns
    const [campaigns, setCampaigns] = useState([]);
    const [total, setTotal] = useState(0);
    const [totalPages, setTotalPages] = useState(1);
    //Automation
    const [automations, setAutomations] = useState([]);
    //templates
    const [templates, setTemplates] = useState({});

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get("t");

        if (tab) {
            setActiveTab(tab);
        }
    }, []);

    useEffect(() => {
        const handlePopState = () => {
            const params = new URLSearchParams(window.location.search);
            setActiveTab(params.get('t') || 'campaigns');
        };

        window.addEventListener('popstate', handlePopState);

        return () => {
            window.removeEventListener('popstate', handlePopState);
        };
    }, []);

    useEffect(() => {
        if (noticeOpen) {
            const timer = setTimeout(() => {
                setNoticeOpen(false);
            }, 5000);
            return () => clearTimeout(timer);
        }
    }, [noticeOpen]);

    const activeNotice = (msg, type = 'error') => {
        setNotice([msg, type]);
        setNoticeOpen(true);
    }

    const tabs = [
        {
            name: 'campaigns',
            title: __('Campaigns', 'cps-bloom-mailer'),
            className: 'tab-campaigns'
        },
        {
            name: 'automations',
            title: __('Automations', 'cps-bloom-mailer'),
            className: 'tab-automations'
        },
        {
            name: 'templates',
            title: __('Templates', 'cps-bloom-mailer'),
            className: 'tab-templates'
        },
        {
            name: 'stats',
            title: __('Stats', 'cps-bloom-mailer'),
            className: 'tab-stats'
        },
    ];

    const handleTabChange = (tab) => {
        setActiveTab(tab);

        const params = new URLSearchParams(window.location.search);
        params.set("t", tab);
        params.delete('a');
        params.delete('c');

        window.history.pushState({}, "", `${window.location.pathname}?${params.toString()}`);
    };

    const TAB_COMPONENTS = {
        automations: Automations,
        templates: Templates,
        stats: StatsPage,
        campaigns: Campaigns,
    };

    const TAB_PROPS = {
        automations: { activeNotice, automations, setAutomations },
        templates: { activeNotice, templates, setTemplates },
        stats: { activeNotice },
        campaigns: { activeNotice, loading, setLoading, campaigns, setCampaigns, total, setTotal, totalPages, setTotalPages },
    };
    const ActiveComponent = TAB_COMPONENTS[activeTab] || Campaigns;

    return (
        <>
            <div className="tab-header">
                {tabs.map((tab) => (
                    <button
                        key={tab.name}
                        onClick={() => handleTabChange(tab.name)}
                        className={activeTab === tab.name ? "active" : ""}
                    >
                        {tab.title}
                    </button>
                ))}
            </div>

            {renderNotice({ noticeOpen, notice, setNoticeOpen })}
            <ActiveComponent props={(TAB_PROPS[activeTab] || TAB_PROPS.campaigns)} />
        </>
    );
};
export default Dashboard;