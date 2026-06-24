/**
 * Internal dependencies
 */
import edit from './Edit';
import metadata from './block.json';
import variations from './variations';

const settings = {
    edit,
    save: () => null,
    variations,
};

export { metadata, settings };