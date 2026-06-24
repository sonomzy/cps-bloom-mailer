/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Each variation maps a `service` attribute value to a title used by
 * getActiveBlockVariation() in the edit component. Icons are resolved
 * separately via the PNG palette (assets/social-icons/{color}/{service}.png),
 * so no icon component is needed here — keep `icon` omitted or pointed to
 * a neutral placeholder for the block inserter list.
 */

const SERVICE_LABELS = {
    facebook: __('Facebook'),
    x: __('X (Twitter)'),
    instagram: __('Instagram'),
    linkedin: __('LinkedIn'),
    youtube: __('YouTube'),
    pinterest: __('Pinterest'),
    tiktok: __('TikTok'),
    twitch: __('Twitch'),
    whatsapp: __('WhatsApp'),
    discord: __('Discord'),
    snapchat: __('Snapchat'),
    threads: __('Threads'),
    telegram: __('Telegram'),
    etsy: __('Etsy'),
    goodreads: __('Goodreads'),
    medium: __('Medium'),
    wordpress: __('WordPress'),
    twitter: __('Twitter'),
    vimeo: __('Vimeo'),
    github: __('GitHub'),
};

const AVAILABLE_SERVICES = Object.keys(SERVICE_LABELS);
const getIconUrl = (service) => `/wp-content/plugins/cps-bloom-mailer/assets/socials/black/${service}.png`;

const variations = AVAILABLE_SERVICES.map((service) => ({
    name: service,
    icon: (
        <img
            src={getIconUrl(service)}
            alt={service}
            width="24"
            height="24"
        />
    ),
    title: SERVICE_LABELS[service],
    description: SERVICE_LABELS[service],
    attributes: { service, label: SERVICE_LABELS[service] },
    // Used by the inserter to identify which variation an existing block
    // instance matches (i.e. getActiveBlockVariation looks at this).
    isActive: (blockAttributes, variationAttributes) =>
        blockAttributes.service === variationAttributes.service,
    scope: ['inserter', 'block'],
}));

export default variations;
export { SERVICE_LABELS, AVAILABLE_SERVICES };