import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
    PanelBody,
    SelectControl,
    FormTokenField,
    RangeControl,
    ToggleControl,
    TextControl,
    __experimentalGrid as Grid,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import PostSelector from '../../../components/PostSelector';
import { wpPosts } from '../../../utils';

export default function Edit({ attributes, setAttributes, className }) {
    const {
        ids, orderBy, categories, count, columns,
        showExcerpt, showImage, showButton,
        buttonText, buttonColor, buttonTextColor,
    } = attributes;
    const { postsData, loadingPosts } = wpPosts();
    const blockProps = useBlockProps({
        className: `${className || ''} post-wrapper`,
    });

    // ── Categories from core data store ───────────────────────────────────────
    const postCategories = useSelect(
        (select) => select('core').getEntityRecords('taxonomy', 'category', { per_page: -1 }) ?? [],
        []
    );

    // ── Map orderBy attribute value → WP REST API params ─────────────────────
    function getOrderParams(val, hasIds) {
        if (hasIds) return { orderby: 'include' };
        switch (val) {
            case 'newest': return { orderby: 'date', order: 'desc' };
            case 'oldest': return { orderby: 'date', order: 'asc' };
            case 'popular': return { orderby: 'comment_count', order: 'desc' };
            case 'rand': return { orderby: 'rand' };
            default: return {};
        }
    }

    // ── Fetch posts via core data store ───────────────────────────────────────
    const { posts, isResolving } = useSelect((select) => {
        const _categories = categories;
        const perPage = ids?.length > 0 ? ids.length : (count || 2);
        const orderParams = getOrderParams(orderBy, ids?.length > 0);

        const query = {
            per_page: perPage,
            _embed: true,
            ...(ids?.length > 0 ? { include: ids } : {}),
            ...(!ids?.length && _categories?.length > 0 ? { categories: _categories } : {}),
            ...orderParams,
        };

        return {
            posts: select('core').getEntityRecords('postType', 'post', query),
            isResolving: select('core/data').isResolving(
                'core',
                'getEntityRecords',
                ['postType', 'post', query]
            ),
        };
    }, [count, ids, categories, orderBy]);

    // ── Category token field handler ──────────────────────────────────────────
    function onChangeCategory(selectedTokens) {
        if (!selectedTokens.length) {
            setAttributes({ categories: [] });
            return;
        }
        // ✅ use selectedTokens, not value
        const categoryIDs = selectedTokens
            .map(token => postCategories.find(cat => cat.name === token)?.id ?? null)
            .filter(Boolean);
        setAttributes({ categories: categoryIDs });
    }

    // ── Featured image helper — from _embedded, not post.featured_image ───────
    function getFeaturedImage(post) {
        return post._embedded?.['wp:featuredmedia']?.[0]?.source_url ?? null;
    }

    // ── Render ────────────────────────────────────────────────────────────────
    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', 'cps-bloom-mailer')}>
                    <PostSelector
                        selectedPosts={ids}
                        wpStates={{ postsData, loadingPosts }}
                        setSelectedPosts={(value) => setAttributes({ ids: value })}
                        type="posts"
                        label={__('Feature specific posts (overrides other settings)', 'cps-bloom-mailer')}
                    />

                    <RangeControl
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        label={__('Number of posts', 'cps-bloom-mailer')}
                        value={count}
                        min={1}
                        max={10}
                        onChange={(value) => setAttributes({ count: value })}
                    />
                    {count > 1 &&
                        <RangeControl
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
                            label={__('Columns', 'cps-bloom-mailer')}
                            value={columns}
                            min={1}
                            max={4}
                            onChange={(value) => setAttributes({ columns: value })}
                        />
                    }
                    <SelectControl
                        __next40pxDefaultSize
                        label={__('Order by', 'cps-bloom-mailer')}
                        value={orderBy}
                        options={[
                            { label: __('None', 'cps-bloom-mailer'), value: 'none' },
                            { label: __('Newest', 'cps-bloom-mailer'), value: 'newest' },
                            { label: __('Oldest', 'cps-bloom-mailer'), value: 'oldest' },
                            { label: __('Popularity', 'cps-bloom-mailer'), value: 'popular' },
                            { label: __('Random', 'cps-bloom-mailer'), value: 'rand' },
                        ]}
                        onChange={(value) => setAttributes({ orderBy: value })}
                    />

                    <FormTokenField
                        __nextHasNoMarginBottom
                        __experimentalExpandOnFocus
                        label={__('Categories', 'cps-bloom-mailer')}
                        value={(categories ?? [])
                            .map(id => postCategories.find(cat => cat.id === id)?.name ?? '')
                            .filter(Boolean)
                        }
                        suggestions={(postCategories ?? []).map(cat => cat.name)}
                        onChange={onChangeCategory}
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={__('Show featured image', 'cps-bloom-mailer')}
                        checked={showImage}
                        onChange={(value) => setAttributes({ showImage: value })}
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={__('Show excerpt', 'cps-bloom-mailer')}
                        checked={showExcerpt}
                        onChange={(value) => setAttributes({ showExcerpt: value })}
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={__('Show button', 'cps-bloom-mailer')}
                        checked={showButton}
                        onChange={(value) => setAttributes({ showButton: value })}
                    />

                    {showButton && (
                        <TextControl
                            __nextHasNoMarginBottom
                            label={__('Button text', 'cps-bloom-mailer')}
                            value={buttonText}
                            onChange={(value) => setAttributes({ buttonText: value })}
                        />
                    )}

                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                {!posts || posts.length === 0 ? (
                    <p style={{ textAlign: 'center', color: '#999' }}>
                        {(isResolving || posts === null)
                            ? __('Loading posts…', 'cps-bloom-mailer')
                            : __('No posts found', 'cps-bloom-mailer')}
                    </p>
                ) : (
                    <Grid
                        className="posts-list"
                        columns={count > 1 ? (columns || 2) : count}
                        style={{
                            opacity: isResolving ? 0.5 : 1,
                            transition: 'opacity 0.2s',
                        }}
                    >
                        {posts.map(post => {
                            const title = post.title?.rendered ?? '';
                            const excerpt = post.excerpt?.rendered ?? '';
                            const image = getFeaturedImage(post);

                            return (
                                <div
                                    key={post.id}
                                    className="post-item"
                                    style={{ padding: 8, textAlign: 'center' }}
                                >
                                    {showImage && image && (
                                        <img
                                            src={image}
                                            alt={title}
                                            style={{ maxWidth: '100%', marginBottom: 10 }}
                                        />
                                    )}

                                    <h3 style={{ margin: '0 0 8px', fontSize: 15, lineHeight: 1.4 }}>
                                        {title}
                                    </h3>

                                    {showExcerpt && (
                                        <div
                                            className="post-excerpt"
                                            dangerouslySetInnerHTML={{ __html: excerpt }}
                                        />
                                    )}

                                    {showButton && (
                                        <div style={{
                                            display: 'inline-block',
                                            margin: '6px auto 0',
                                            padding: '6px 14px',
                                            background: buttonColor,
                                            color: buttonTextColor,
                                            fontSize: 14,
                                        }}>
                                            {buttonText || __('Read More', 'cps-bloom-mailer')}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </Grid>
                )}
            </div>
        </>
    );
}