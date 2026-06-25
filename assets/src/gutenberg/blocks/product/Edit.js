import { __ } from '@wordpress/i18n';
import { InspectorControls, PanelColorSettings, useBlockProps } from '@wordpress/block-editor';
import {
    PanelBody,
    SelectControl,
    __experimentalToggleGroupControl as ToggleGroupControl,
    __experimentalToggleGroupControlOption as ToggleGroupControlOption,
    FormTokenField,
    RangeControl,
    ToggleControl,
    TextControl,
    __experimentalGrid as Grid,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import PostSelector from '../../../components/PostSelector';
import { wpPosts } from '../../../utils';

export default function Edit({ attributes, setAttributes, className }) {
    const {
        ids, orderBy, categories, count, columns, order,
        saleOnly, showImage, showButton, buttonText,
        buttonColor, buttonTextColor, background, textColor
    } = attributes;

    const { postsData, loadingPosts } = wpPosts();
    const [products, setProducts] = useState([]);
    const [productCats, setProductCats] = useState([]);
    const [loading, setLoading] = useState(true);

    const blockProps = useBlockProps({
        className: `${className || ''} product-wrapper`,
    });

    // ── Star rating helper ────────────────────────────────────────────────────
    function renderStars(rating) {
        const full = Math.floor(rating);
        const half = rating % 1 !== 0;
        return (
            <>
                {Array.from({ length: 5 }, (_, i) => {
                    if (i < full) return <span key={i} className="star full">★</span>;
                    if (i === full && half) return <span key={i} className="star half">★</span>;
                    return <span key={i} className="star empty">☆</span>;
                })}
            </>
        );
    }

    // ── Fetch WooCommerce categories on mount ─────────────────────────────────
    useEffect(() => {
        apiFetch({ path: '/wc/v3/products/categories?per_page=100' })
            .then(setProductCats)
            .catch(console.error);
    }, []);

    // ── Fetch products when filters change ────────────────────────────────────
    useEffect(() => {
        fetchProducts();
    }, [ids, count, orderBy, order, saleOnly, categories]);

    async function fetchProducts() {
        setLoading(true);

        // ✅ use different names to avoid shadowing the destructured attributes
        const _ids = ids ?? [];
        const _orderBy = orderBy || 'date';
        const _order = order || 'asc';

        // WC REST API orderby param
        const getOrderByParam = (val, hasIds) => {
            if (hasIds) return 'include';
            if (val === 'none') return 'date';
            return val; // date, title, price, rating, popularity, menu_order
        };

        const orderByParam = getOrderByParam(_orderBy, _ids.length > 0);

        let path = `/wc/v3/products?status=publish&orderby=${orderByParam}`;

        // order param is ignored by WC for include/rand
        if (!['include', 'rand'].includes(orderByParam)) {
            path += `&order=${_order}`;
        }

        if (_ids.length > 0) {
            path += `&per_page=${_ids.length}&include=${_ids.join(',')}`;
        } else {
            path += `&per_page=${count || 2}`;
            if (saleOnly) path += '&on_sale=true';
            if (categories?.length > 0) path += `&category=${categories.join(',')}`;
        }

        try {
            const fetched = await apiFetch({ path });
            setProducts(
                (fetched ?? []).map(p => ({
                    id: p.id,
                    name: p.name,
                    permalink: p.permalink,
                    price_html: p.price_html,
                    on_sale: p.on_sale,
                    featured_image: p.images?.[0] ?? null,
                    average_rating: p.average_rating,
                    rating_count: p.rating_count,
                }))
            );
        } catch (error) {
            console.error('Error fetching products:', error);
        } finally {
            setLoading(false);
        }
    }

    // ── Category token field handler ──────────────────────────────────────────
    function onChangeCategory(value) {
        if (!value.length) {
            setAttributes({ categories: [] });
            return;
        }

        const ids = value
            .map(token => productCats.find(cat => cat.name === token)?.id ?? null)
            .filter(Boolean);
        setAttributes({ categories: ids });
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
        colorSettings.push(
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
                        type="products"
                        label={__('Feature specific products (overrides other settings)')}
                    />

                    <RangeControl
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        label={__('Number of products')}
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
                            { label: __('Date'), value: 'date' },
                            { label: __('Title'), value: 'title' },
                            { label: __('Price'), value: 'price' },
                            { label: __('Rating'), value: 'rating' },
                            { label: __('Popularity'), value: 'popularity' },
                            { label: __('Random'), value: 'rand' },
                            { label: __('Menu order'), value: 'menu_order' },
                        ]}
                        onChange={(value) => setAttributes({ orderBy: value })}
                    />

                    <ToggleGroupControl
                        __nextHasNoMarginBottom
                        isBlock
                        label={__('Order')}
                        value={order}
                        onChange={(value) => setAttributes({ order: value })}
                    >
                        <ToggleGroupControlOption label="Desc" value="desc" />
                        <ToggleGroupControlOption label="Asc" value="asc" />
                    </ToggleGroupControl>

                    <FormTokenField
                        __nextHasNoMarginBottom
                        __experimentalExpandOnFocus
                        label={__('Categories')}
                        value={(categories ?? [])
                            .map(id => productCats.find(cat => cat.id === id)?.name ?? '')
                            .filter(Boolean)
                        }
                        suggestions={productCats.map(cat => cat.name)}
                        onChange={onChangeCategory}
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={__('Sale products only')}
                        checked={saleOnly}
                        onChange={(value) => setAttributes({ saleOnly: value })}
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={__('Show image')}
                        checked={showImage}
                        onChange={(value) => setAttributes({ showImage: value })}
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
                {products.length === 0 ? (
                    <p style={{ textAlign: 'center', color: '#999' }}>
                        {loading
                            ? __('Loading products…')
                            : __('No products found')}
                    </p>
                ) : (
                    <Grid
                        className="products-list"
                        columns={count > 1 ? (columns || 2) : count}
                        style={{
                            opacity: loading ? 0.5 : 1,
                            transition: 'opacity 0.2s',
                        }}
                    >
                        {products.map(product => (
                            <div
                                key={product.id}
                                className="product-item"
                                style={{ background: background, color: textColor, padding: 8, textAlign: 'center' }}
                            >
                                {(showImage || product.on_sale) && (
                                    <div style={{ position: 'relative' }}>
                                        {showImage && product.featured_image && (
                                            <img
                                                src={product.featured_image.src}
                                                alt={product.featured_image.alt}
                                                style={{ maxWidth: '100%' }}
                                            />
                                        )}
                                        {product.on_sale && (
                                            <span className="sale-badge">
                                                {__('SALE')}
                                            </span>
                                        )}
                                    </div>
                                )}

                                <p style={{ margin: '0 0 6px', fontWeight: 600, fontSize: 15 }}>
                                    {product.name}
                                </p>

                                <div
                                    className="product-price"
                                    dangerouslySetInnerHTML={{ __html: product.price_html }}
                                />

                                {product.average_rating > 0 && (
                                    <div style={{ fontSize: 15, marginBottom: 6 }}>
                                        {renderStars(parseFloat(product.average_rating))}
                                        <span>({product.rating_count})</span>
                                    </div>
                                )}

                                {showButton && (
                                    <div style={{
                                        display: 'inline-block',
                                        margin: '6px auto 0',
                                        padding: '10px 13px',
                                        background: buttonColor,
                                        color: buttonTextColor,
                                        fontSize: 13,
                                    }}>
                                        {buttonText || __('Learn More')}
                                    </div>
                                )}
                            </div>
                        ))}
                    </Grid>
                )}
            </div>
        </>
    );
}