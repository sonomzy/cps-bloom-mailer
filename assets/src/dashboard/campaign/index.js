import apiFetch from '@wordpress/api-fetch';
import { useDispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from "@wordpress/components";
import Editor from '../../gutenberg';
import { formatCampaign, loadTemplates } from '../../utils';

const Campaign = ({ loaded = {}, onClose = null }) => {
    const { createNotice } = useDispatch('core/notices');
    const [loading, setLoading] = useState('get');
    const [campaign, setCampaign] = useState(null);
    const [templates, setTemplates] = useState({});

    useEffect(() => {
        getCampaign();
        getTemplates();
    }, []);

    const activeNotice = (msg, type = 'error') => {
        createNotice(type, msg, {
            type: 'snackbar',
            isDismissible: true,
        });
    }

    const saveCampaign = async (campaignData, silent = false) => {
        if (!silent) setLoading('save');

        try {
            const response = await apiFetch({
                path: '/cps/v1/mailer/save',
                method: 'POST',
                data: {
                    id: campaignData?.id,
                    data: campaignData,
                    auto: silent
                },
            });

            if (response?.success) {
                setCampaign(response.data);
                if (!silent) {
                    const params = new URLSearchParams(window.location.search);
                    const cId = params.get('c');
                    if (!cId || cId === '0') {
                        params.set('c', response.data.id);
                        window.history.pushState({}, "", `${window.location.pathname}?${params.toString()}`);
                    }
                    activeNotice(response?.message || __('Campaign saved successfully', 'cps-bloom-mailer'), 'success');
                }
                return true;
            } else {
                if (!silent) activeNotice(response?.message);
                return false;
            }
        } catch (error) {
            console.error('Error saving email campaign:', error);
            if (!silent) activeNotice(error?.message || __('Error saving campaign.', 'cps-bloom-mailer'));
            return false;
        } finally {
            if (!silent) setLoading(null);
        }
    };

    const sendDemoEmail = async (data) => {
        setLoading('demo');

        try {
            const response = await apiFetch({
                path: '/cps/v1/mailer/send-demo',
                method: 'POST',
                data,
            });
            if (response?.success) {
                activeNotice(response.message || __('You will recieve your email shortly', 'cps-bloom-mailer'), 'info');
            } else {
                activeNotice(response?.message || 'Failed to send email');
            }
        } catch (error) {
            activeNotice(error?.message || __('Unable to send email', 'cps-bloom-mailer'));
        } finally {
            setLoading(null);
        }
    };

    const getCampaign = async () => {
        try {
            if (loaded?.id) {
                const formatted = formatCampaign({ campaign: loaded });
                setCampaign(formatted);
            } else {
                const params = new URLSearchParams(window.location.search);
                const cId = params.get('c');

                if (!cId || cId === '0') {
                    const formatted = formatCampaign();
                    setCampaign(formatted);
                    return;
                }

                const response = await apiFetch({
                    path: `/cps/v1/mailer/campaign?id=${cId}`,
                    method: 'GET',
                });
                if (response) {
                    const formatted = formatCampaign({ campaign: response });
                    setCampaign(formatted);
                } else {
                    activeNotice(__('Failed to fetch campaign', 'cps-bloom-mailer'));
                }
            }
        } catch (error) {
            console.error('Error fetching campaign:', error);
            activeNotice(__('Error fetching campaign', 'cps-bloom-mailer'));
        } finally {
            setLoading(null);
        }
    };

    const getTemplates = async () => {
        try {
            const response = await loadTemplates(true);
            setTemplates(response);
        } catch (error) {
            console.error(error);
            activeNotice(__('Error fetching templates', 'cps-bloom-mailer'));
        }
    };

    const handleSaveCampaign = async (campaignData, silent = false) => {
        if (!campaign) return;
        return await saveCampaign(campaignData, silent);
    };

    const sendCampaign = async () => {
        if (!campaign || campaign.status !== 'draft') return;
        setLoading('send');

        try {
            const response = await apiFetch({
                path: '/cps/v1/mailer/send-campaign',
                method: 'POST',
                data: campaign.id,
            });
            if (response?.success) {
                activeNotice(response.message || __('Campaign queued', 'cps-bloom-mailer'), 'info');
            } else {
                activeNotice(response?.message || 'Failed to queue campaign');
            }
        } catch (error) {
            activeNotice(error?.message || __('Failed to queue campaign', 'cps-bloom-mailer'));
        } finally {
            setLoading(null);
        }
    }

    const close = () => {
        if (onClose) {
            onClose();
            return;
        }

        const params = new URLSearchParams(window.location.search);
        params.delete('c');
        params.delete('a');

        const query = params.toString();

        window.location.href = query
            ? `${window.location.pathname}?${query}`
            : window.location.pathname;
    };

    return (
        <>
            {loading === 'get' ? (
                <div className="cps-bloom-mailer-loading" style={{ height: '100vh' }}>
                    <Spinner
                        style={{
                            height: 50,
                            width: 50,
                            color: '#000000',
                        }}
                    />
                    <span>{__('Loading campaign…', 'cps-bloom-mailer')}</span>
                </div>
            ) : (
                <Editor
                    campaign={campaign}
                    onClose={close}
                    onSave={handleSaveCampaign}
                    loading={loading}
                    templates={templates}
                    sendDemo={sendDemoEmail}
                    sendCampaign={sendCampaign}
                />
            )}
        </>
    );
}
export default Campaign;