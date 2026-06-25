import { __ } from '@wordpress/i18n';
import { InspectorControls, PanelColorSettings, useBlockProps } from '@wordpress/block-editor';
import {
    PanelBody,
    SelectControl,
    FormTokenField,
    RangeControl,
    ToggleControl,
    TextControl,
    __experimentalGrid as Grid,
    __experimentalToggleGroupControl as ToggleGroupControl,
    __experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import PostSelector from '../../../components/PostSelector';
import { wpPosts } from '../../../utils';

export default function Edit({ attributes, setAttributes, className }) {
    const {
        ids, orderBy, categories, count, columns, showExcerpt,
        showImage, showButton, buttonText, buttonColor,
        buttonTextColor, background, textColor
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

    const colorSettings = [
        {
            value: background,
            onChange: (colorValue) =>
                setAttributes({ background: colorValue }),
            label: __('Background'),
        },
        {
            value: textColor,
            onChange: (colorValue) =>
                setAttributes({ textColor: colorValue }),
            label: __('Text Color'),
        },
    ];

    if (showButton) {
        colorSettings.unshift(
            {
                value: buttonColor,
                onChange: (colorValue) => {
                    setAttributes({ buttonColor: colorValue });
                },
                label: __('Button Color'),
            },
            {
                value: buttonTextColor,
                onChange: (colorValue) => {
                    setAttributes({ buttonTextColor: colorValue });
                },
                label: __('Btn Text Coor'),
            },
        );
    }

    // ── Render ────────────────────────────────────────────────────────────────
    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings')}>
                    <PostSelector
                        selectedPosts={ids}
                        wpStates={{ postsData, loadingPosts }}
                        setSelectedPosts={(value) => setAttributes({ ids: value })}
                        type="posts"
                        label={__('Feature specific posts (overrides other settings)')}
                    />

                    <RangeControl
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        label={__('Number of posts')}
                        value={count}
                        min={1}
                        max={10}
                        onChange={(value) => setAttributes({ count: value })}
                    />
                    {count > 1 &&
                        <ToggleGroupControl
                            __nextHasNoMarginBottom
                            isBlock
                            label={__('Columns')}
                            value={columns}
                            onChange={(value) => setAttributes({ columns: value })}
                        >
                            <ToggleGroupControlOption label="1" value={1} />
                            <ToggleGroupControlOption label="2" value={2} />
                        </ToggleGroupControl>
                    }
                    <SelectControl
                        __next40pxDefaultSize
                        label={__('Order by')}
                        value={orderBy}
                        options={[
                            { label: __('None'), value: 'none' },
                            { label: __('Newest'), value: 'newest' },
                            { label: __('Oldest'), value: 'oldest' },
                            { label: __('Popularity'), value: 'popular' },
                            { label: __('Random'), value: 'rand' },
                        ]}
                        onChange={(value) => setAttributes({ orderBy: value })}
                    />

                    <FormTokenField
                        __nextHasNoMarginBottom
                        __experimentalExpandOnFocus
                        label={__('Categories')}
                        value={(categories ?? [])
                            .map(id => postCategories.find(cat => cat.id === id)?.name ?? '')
                            .filter(Boolean)
                        }
                        suggestions={(postCategories ?? []).map(cat => cat.name)}
                        onChange={onChangeCategory}
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={__('Show featured image')}
                        checked={showImage}
                        onChange={(value) => setAttributes({ showImage: value })}
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={__('Show excerpt')}
                        checked={showExcerpt}
                        onChange={(value) => setAttributes({ showExcerpt: value })}
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={__('Show button')}
                        checked={showButton}
                        onChange={(value) => setAttributes({ showButton: value })}
                    />

                    {showButton && (
                        <TextControl
                            __nextHasNoMarginBottom
                            label={__('Button text')}
                            value={buttonText}
                            onChange={(value) => setAttributes({ buttonText: value })}
                        />
                    )}

                    <PanelColorSettings
                        __experimentalIsRenderedInSidebar
                        title={__('Color')}
                        colorSettings={colorSettings}
                    />

                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                {!posts || posts.length === 0 ? (
                    <p style={{ textAlign: 'center', color: '#999' }}>
                        {(isResolving || posts === null)
                            ? __('Loading posts…')
                            : __('No posts found')}
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
                                    style={{ background: background, color: textColor, padding: 8, textAlign: 'center' }}
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
                                            {buttonText || __('Read More')}
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