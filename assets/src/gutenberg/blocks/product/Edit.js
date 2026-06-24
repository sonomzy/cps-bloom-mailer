import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
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
        buttonColor, buttonTextColor,
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

    // ── Render ────────────────────────────────────────────────────────────────
    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', 'cps-bloom-mailer')}>

                    <PostSelector
                        selectedPosts={ids}
                        wpStates={{ postsData, loadingPosts }}
                        setSelectedPosts={(value) => setAttributes({ ids: value })}
                        type="products"
                        label={__('Feature specific products (overrides other settings)', 'cps-bloom-mailer')}
                    />

                    <RangeControl
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        label={__('Number of products', 'cps-bloom-mailer')}
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
                        />}

                    <SelectControl
                        __next40pxDefaultSize
                        label={__('Order by', 'cps-bloom-mailer')}
                        value={orderBy}
                        options={[
                            { label: __('None', 'cps-bloom-mailer'), value: 'none' },
                            { label: __('Date', 'cps-bloom-mailer'), value: 'date' },
                            { label: __('Title', 'cps-bloom-mailer'), value: 'title' },
                            { label: __('Price', 'cps-bloom-mailer'), value: 'price' },
                            { label: __('Rating', 'cps-bloom-mailer'), value: 'rating' },
                            { label: __('Popularity', 'cps-bloom-mailer'), value: 'popularity' },
                            { label: __('Random', 'cps-bloom-mailer'), value: 'rand' },
                            { label: __('Menu order', 'cps-bloom-mailer'), value: 'menu_order' },
                        ]}
                        onChange={(value) => setAttributes({ orderBy: value })}
                    />

                    <ToggleGroupControl
                        __nextHasNoMarginBottom
                        isBlock
                        label={__('Order', 'cps-bloom-mailer')}
                        value={order}
                        onChange={(value) => setAttributes({ order: value })}
                    >
                        <ToggleGroupControlOption label="Desc" value="desc" />
                        <ToggleGroupControlOption label="Asc" value="asc" />
                    </ToggleGroupControl>

                    <FormTokenField
                        __nextHasNoMarginBottom
                        __experimentalExpandOnFocus
                        label={__('Categories', 'cps-bloom-mailer')}
                        value={(categories ?? [])
                            .map(id => productCats.find(cat => cat.id === id)?.name ?? '')
                            .filter(Boolean)
                        }
                        suggestions={productCats.map(cat => cat.name)}
                        onChange={onChangeCategory}
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={__('Sale products only', 'cps-bloom-mailer')}
                        checked={saleOnly}
                        onChange={(value) => setAttributes({ saleOnly: value })}
                    />

                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={__('Show image', 'cps-bloom-mailer')}
                        checked={showImage}
                        onChange={(value) => setAttributes({ showImage: value })}
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
                {products.length === 0 ? (
                    <p style={{ textAlign: 'center', color: '#999' }}>
                        {loading
                            ? __('Loading products…', 'cps-bloom-mailer')
                            : __('No products found', 'cps-bloom-mailer')}
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
                                style={{ padding: 8, textAlign: 'center' }}
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
                                                {__('SALE', 'cps-bloom-mailer')}
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
                                        padding: '6px 14px',
                                        background: buttonColor,
                                        color: buttonTextColor,
                                        fontSize: 13,
                                    }}>
                                        {buttonText || __('Learn More', 'cps-bloom-mailer')}
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