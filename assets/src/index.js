import { __ } from '@wordpress/i18n';
import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import { registerBlockType } from '@wordpress/blocks';
import { registerCoreBlocks } from '@wordpress/block-library';
import { registerFormatType } from '@wordpress/rich-text';
import { metadata as productMeta, edit as ProductEdit } from './gutenberg/blocks/product';
import { metadata as postMeta, edit as PostEdit } from './gutenberg/blocks/post';
import { metadata as socialsMeta, settings as socialsEdit } from './gutenberg/blocks/socials';
import { metadata as socialMeta, settings as socialEdit } from './gutenberg/blocks/social';
import { mergetags } from './gutenberg/components/formats';
import './styles.scss';

domReady(() => {
    const SettingsRoot = document.getElementById('cps-bloom-mailer-settings');
    if (SettingsRoot) {
        import(/* webpackChunkName: "Settings" */ './dashboard/settings').then(({ default: Settings }) => {
            const root = createRoot(SettingsRoot);
            root.render(<Settings />);
        });
    }

    registerCoreBlocks();
    registerBlockType(productMeta, { edit: ProductEdit, save: () => null });
    registerBlockType(postMeta, { edit: PostEdit, save: () => null });
    registerBlockType(socialsMeta, socialsEdit);
    registerBlockType(socialMeta, socialEdit);
    registerFormatType('cps-bloom-mailer/merge-tag', {
        title: __('Merge Tag'),
        tagName: 'span',
        className: 'merge-tag',
        edit: mergetags,
    });

    const CampaignsRoot = document.getElementById('cps-bloom-mailer-admin');
    if (CampaignsRoot) {
        import(/* webpackChunkName: "campaigns" */ './dashboard').then(({ default: Dashboard }) => {
            const root = createRoot(CampaignsRoot);
            root.render(<Dashboard />);
        });
    }

    const EditorRoot = document.getElementById('cps-campaign-edit');
    if (EditorRoot) {
        import(/* webpackChunkName: "campaign" */ './dashboard/campaign').then(({ default: Campaign }) => {
            const root = createRoot(EditorRoot);
            root.render(<Campaign />);
        });
    }
});