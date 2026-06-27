import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import Tags from './tags';
import Lists from './lists';
import Subscribers from './subscribers';

const Contacts = () => {
    const [activeTab, setActiveTab] = useState('subscribers');
    //subscribers
    const [subscribers, setSubscribers] = useState([]);
    const [total, setTotal] = useState(0);
    const [totalPages, setTotalPages] = useState(1);
    //Automation
    const [lists, setLists] = useState([]);
    //tags
    const [tags, setTags] = useState({});

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
            setActiveTab(params.get('t') || 'subscribers');
        };

        window.addEventListener('popstate', handlePopState);

        return () => {
            window.removeEventListener('popstate', handlePopState);
        };
    }, []);

    const tabs = [
        {
            name: 'subscribers',
            title: __('Subscribers', 'cps-bloom'),
            className: 'tab-subscribers'
        },
        {
            name: 'lists',
            title: __('Lists', 'cps-bloom'),
            className: 'tab-lists'
        },
        {
            name: 'tags',
            title: __('Tags', 'cps-bloom'),
            className: 'tab-tags'
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
        lists: Lists,
        tags: Tags,
        subscribers: Subscribers,
    };

    const TAB_PROPS = {
        lists: { lists, setLists },
        tags: { tags, setTags },
        subscribers: { subscribers, setSubscribers, total, setTotal, totalPages, setTotalPages },
    };
    const ActiveComponent = TAB_COMPONENTS[activeTab] || Subscribers;

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

            <ActiveComponent props={(TAB_PROPS[activeTab] || TAB_PROPS.subscribers)} />
        </>
    );
};
export default Contacts;