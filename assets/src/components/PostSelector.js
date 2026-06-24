import { useMemo } from '@wordpress/element';
import { FormTokenField } from '@wordpress/components';

const PostSelector = ({ selectedPosts, setSelectedPosts, wpStates, label = '', type = 'posts' }) => {
    const { postsData, loadingPosts } = wpStates;

    const allOptions = useMemo(() => postsData[type] || [], [postsData, type]);

    // FormTokenField works with strings, so map to titles for display
    const suggestions = useMemo(() => allOptions.map(item => item.title), [allOptions]);

    // Convert selected IDs back to titles for the field value
    const tokenValue = useMemo(() => {
        return selectedPosts
            .map(id => allOptions.find(item => item.id === id)?.title)
            .filter(Boolean);
    }, [selectedPosts, allOptions]);

    const handleChange = (tokens) => {
        // Convert titles back to IDs
        const ids = tokens
            .map(token => allOptions.find(item => item.title === token)?.id)
            .filter(Boolean);
        setSelectedPosts(ids);
    };

    return (
        <div className="post-select-container">
            {loadingPosts ? (
                <div className="loading-message">Loading...</div>
            ) : (
                <FormTokenField
                    label={label || 'Select posts'}
                    value={tokenValue}
                    suggestions={suggestions}
                    onChange={handleChange}
                    placeholder="Search posts..."
                    __experimentalExpandOnFocus
                    __next40pxDefaultSize
                />
            )}
        </div>
    );
};
export default PostSelector;