/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { insert } from '@wordpress/rich-text';
import { RichTextToolbarButton } from '@wordpress/block-editor';
import {
    Dropdown,
    MenuGroup,
    MenuItem,
} from '@wordpress/components';
import { keyboard as icon } from '@wordpress/icons';

const MERGE_TAGS = window.cbmData?.placeholders || [];
const title = __('Merge Tag');

export const mergetags = (props) => {
    const { value, onChange, isVisible } = props;

    const insertTag = (tag) => {
        const newValue = insert(
            value,
            tag,
            value.start ?? value.end,
            value.end ?? value.start
        );

        onChange(newValue);
    };

    return (
        <Dropdown
            popoverProps={{ placement: 'bottom-start' }}
            renderToggle={({ isOpen, onToggle }) => (
                <RichTextToolbarButton
                    icon={icon}
                    title={title}
                    onClick={onToggle}
                    isActive={isOpen}
                />
            )}
            renderContent={({ onClose }) => (
                <MenuGroup label={__('Insert merge tag')}>
                    {MERGE_TAGS.map((tag) => (
                        <MenuItem
                            key={tag.value}
                            onClick={() => {
                                insertTag(tag.value);
                                onClose();
                            }}
                        >
                            {tag.label}
                        </MenuItem>
                    ))}
                </MenuGroup>
            )}
        />
    );
};