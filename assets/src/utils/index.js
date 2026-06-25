import { Notice, __experimentalConfirmDialog as ConfirmDialog } from '@wordpress/components';
import { createContext, useContext, useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const decodeHtml = (html) => {
    const txt = document.createElement('textarea');
    txt.innerHTML = html;
    return txt.value;
};

export const wpPosts = () => {
    const [postsData, setPostsData] = useState({ posts: [], pages: [], products: [] });
    const [loadingPosts, setLoadingPosts] = useState(true);

    const fetchAllPosts = async (type) => {

        let page = 1;
        const posts = [];
        let fetched = [];

        try {
            do {
                fetched = await apiFetch({
                    path: `/wp/v2/${type}?per_page=100&page=${page}&_fields=id,title,date`,
                });

                posts.push(...fetched);
                page++;
            } while (fetched.length === 100);
        } catch (err) {
            console.error(`Error fetching ${type}:`, err);
        }

        return posts.map(post => ({
            id: post.id,
            title: decodeHtml(post.title.rendered),
            date: post.date,
            type: type
        }));
    };

    const loadPosts = async () => {
        setLoadingPosts(true);
        try {
            const [posts, pages] = await Promise.all([
                fetchAllPosts('posts'),
                fetchAllPosts('pages')
            ]);
            setPostsData({ posts, pages });
        } catch (err) {
            console.error('Error loading posts/pages:', err);
        } finally {
            setLoadingPosts(false);
        }
    };

    useEffect(() => {
        loadPosts();
    }, []);

    return { postsData, loadingPosts, reload: loadPosts };
}

export const renderNotice = ({ noticeOpen, notice, setNoticeOpen }) => {
    if (!noticeOpen) return null;
    const [message, status] = notice;
    return (
        <div className="notice-wrap">
            <Notice
                isDismissible
                status={status || 'info'}
                onRemove={() => setNoticeOpen(false)}
            >
                {message}
            </Notice>
        </div>
    );
};

export const resetData = async (type) => {
    const response = await apiFetch({
        path: '/cps/v1/mailer/reset',
        method: 'POST',
        data: { type },
    });

    if (!response?.success) {
        throw new Error(
            response?.message || `Failed to reset ${type}`
        );
    }

    return response;
};

export const loadTemplates = async (preview = false) => {
    const response = await apiFetch({
        path: `/cps/v1/mailer/templates?preview=${preview}`,
        method: 'GET',
    });

    if (!response) {
        throw new Error(
            response?.message || __('Failed to fetch templates', 'cps-bloom-mailer')
        );
    }
    return response;
};

export const formatCampaign = ({
    campaign = {},
    isTemplate = false,
} = {}) => {
    const defaults = window.cbmData.default;
    const data = {
        id: campaign.id ?? '',
        title: campaign.title ?? 'Untitled',
        subject: campaign.subject ?? '',
        preview_text: campaign.preview_text ?? '',
        description: campaign.description ?? '',
        status: campaign.status ?? 'draft',
        blocks: campaign.blocks ?? '',
        design: campaign.design ? JSON.parse(campaign.design) : defaults.design,
        header: campaign.header ? JSON.parse(campaign.header) : defaults.header,
        footer: campaign.footer ? JSON.parse(campaign.footer) : defaults.footer,
    };

    if (!isTemplate) {
        data.from_name = campaign.from_name ?? defaults.from_name;
        data.from_email = campaign.from_email ?? defaults.from_email;
        data.reply_to = campaign.reply_to ?? defaults.reply_to ?? '';
        data.status = campaign.status ?? 'draft';
    }

    if (isTemplate) {
        data.template_key = campaign.template_key ?? '';
        data.is_default = campaign.is_default ?? 0;
    }
    return data;
};

export const importJSON = ({ file, type }) => {
    return new Promise((resolve, reject) => {
        if (!file) {
            reject(new Error('No file selected'));
            return;
        }

        if (!file.name.endsWith('.json')) {
            reject(new Error('Please select a valid JSON file.'));
            return;
        }

        const reader = new FileReader();

        reader.onload = (e) => {
            try {
                const importedData = JSON.parse(e.target.result);

                if (!importedData[type]) {
                    throw new Error('Invalid file structure');
                }

                resolve(importedData);
            } catch (error) {
                reject(error);
            }
        };

        reader.onerror = () => {
            reject(new Error('Failed to read file.'));
        };

        reader.readAsText(file);
    });
};

export const exportJSON = ({ data, filename = 'export' }) => {
    const jsonString = JSON.stringify(data, null, 2);

    const blob = new Blob([jsonString], {
        type: 'application/json',
    });

    const url = URL.createObjectURL(blob);

    try {
        const link = document.createElement('a');

        link.href = url;
        link.download = `${filename}.json`;

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } finally {
        URL.revokeObjectURL(url);
    }
};

// Media library function
export const openMediaLibrary = (onSelect) => {
    const mediaFrame = wp.media({
        title: __('Select or Upload Media', 'cps-bloom-mailer'),
        button: {
            text: __('Use this media', 'cps-bloom-mailer')
        },
        multiple: false,
        library: {
            type: 'image'
        }
    });

    mediaFrame.on('select', function () {
        const attachment = mediaFrame.state().get('selection').first().toJSON();
        onSelect(attachment);
    });

    mediaFrame.open();
};

const format = (value) => {
    if (value === '' || value === null || value === undefined) {
        return 0;
    }

    if (
        typeof value === 'string' &&
        value.startsWith('var:preset|spacing|')
    ) {
        const slug = value.replace('var:preset|spacing|', '');
        return `var(--wp--preset--spacing--${slug})`;
    }

    if (typeof value === 'number') {
        return `${value}px`;
    }

    if (typeof value === 'string' && !isNaN(value)) {
        return `${value}px`;
    }

    return value;
};

export const boxValues = (values = {}) => {
    const top = format(values?.top);
    const right = format(values?.right);
    const bottom = format(values?.bottom);
    const left = format(values?.left);
    return `${top} ${right} ${bottom} ${left}`;
}

export const defaultSpacing = (type) => {
    switch (type) {
        case 'block':
            return { top: '10px', left: '10px', right: '10px', bottom: '10px' }
        case 'button':
            return { top: '12px', left: '30px', right: '30px', bottom: '12px' }
        case 'space':
            return { top: '30px', bottom: '30px' }
        case 'header':
            return { top: '20px', left: '40px', right: '40px', bottom: '30px' }
        case 'footer':
            return { top: '20px', left: '40px', right: '40px', bottom: '20px' }
        default:
            return { top: '30px', left: '30px', right: '30px', bottom: '30px' }
    }
}

const FONT_STACK_MAP = Object.fromEntries(
    (window.cbmData?.fontFamilies ?? []).map(({ value, stack }) => [value, stack])
);

export const resolveFontStack = (key) => {
    return FONT_STACK_MAP[key] ?? FONT_STACK_MAP['system-ui'] ?? 'inherit';
}

const EditingContext = createContext(null);
export const EditingProvider = ({ children }) => {
    const [isEditing, setIsEditing] = useState(false);
    return (
        <EditingContext.Provider value={{ isEditing, setIsEditing }}>
            {children}
        </EditingContext.Provider>
    );
};
export const useEditing = () => useContext(EditingContext);

export function debounce(fn, delay) {
    let timeoutID;
    return function (...args) {
        clearTimeout(timeoutID);
        timeoutID = window.setTimeout(() => fn.apply(this, args), delay);
    };
} 