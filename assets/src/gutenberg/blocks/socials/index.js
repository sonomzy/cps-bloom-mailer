import edit from './Edit';
import metadata from './block.json';
import { InnerBlocks } from '@wordpress/block-editor';
const settings = {
    example: {
        innerBlocks: [
            {
                name: 'cps-bloom-mailer/social',
                attributes: {
                    service: 'pinterest',
                    url: 'https://pinterest.com',
                },
            },
            {
                name: 'cps-bloom-mailer/social',
                attributes: {
                    service: 'facebook',
                    url: 'https://www.facebook.com/',
                },
            },
            {
                name: 'cps-bloom-mailer/social',
                attributes: {
                    service: 'twitter',
                    url: 'https://twitter.com',
                },
            },
        ],
    },
    edit,
    save: () => (<InnerBlocks.Content />),
};

export { metadata, settings };