import { __ } from '@wordpress/i18n';
import { __experimentalLibrary as Library, __experimentalListView as ListView } from '@wordpress/block-editor';

export function InserterSidebar({onClose}) {
    return (
        <Library
            showInserterHelpPanel
            shouldFocusBlock={false}
            onClose={onClose}
        />
    );
}

export function ListViewSidebar() {
    return (
        <ListView />
    );
}