<?php

namespace ChicpixiesBloomMailer\Templates;

class Blocks
{

    // ─── WELCOME ─────────────────────────────────────────────────────────────
    public static function welcome(): string
    {
        return <<<BLOCKS
<!-- wp:group {"style":{"spacing":{"padding":{"top":"48px","bottom":"48px","left":"32px","right":"32px"}},"color":{"background":"#ffffff"}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<div class="wp-block-group" style="background-color:#ffffff;padding-top:48px;padding-bottom:48px;padding-left:32px;padding-right:32px">

    <!-- wp:image {"align":"center","width":120,"height":120,"style":{"border":{"radius":"50%"}}} -->
    <figure class="wp-block-image aligncenter" style="border-radius:50%"><img src="{{site_logo}}" alt="{{site_name}}" width="120" height="120"/></figure>
    <!-- /wp:image -->

    <!-- wp:spacer {"height":"24px"} -->
    <div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
    <!-- /wp:spacer -->

    <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"32px","fontWeight":"700"},"color":{"text":"#1a1a2e"}}} -->
    <h1 class="wp-block-heading has-text-align-center" style="color:#1a1a2e;font-size:32px;font-weight:700">Welcome to {{site_name}}, {{first_name}}! 🎉</h1>
    <!-- /wp:heading -->

    <!-- wp:spacer {"height":"16px"} -->
    <div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
    <!-- /wp:spacer -->

    <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"17px","lineHeight":"1.7"},"color":{"text":"#555566"}}} -->
    <p class="has-text-align-center" style="color:#555566;font-size:17px;line-height:1.7">We're thrilled to have you with us. Your account is ready and you're all set to start exploring everything we have to offer.</p>
    <!-- /wp:paragraph -->

    <!-- wp:spacer {"height":"32px"} -->
    <div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div>
    <!-- /wp:spacer -->

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}},"color":{"background":"#f4f6ff"},"border":{"radius":"12px"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="background-color:#f4f6ff;border-radius:12px;padding-top:32px;padding-bottom:32px;padding-left:32px;padding-right:32px">

        <!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"18px","fontWeight":"600"},"color":{"text":"#1a1a2e"}}} -->
        <h3 class="wp-block-heading has-text-align-center" style="color:#1a1a2e;font-size:18px;font-weight:600">Here's what you can do next</h3>
        <!-- /wp:heading -->

        <!-- wp:spacer {"height":"16px"} -->
        <div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->

        <!-- wp:columns {"style":{"spacing":{"blockGap":"16px"}}} -->
        <div class="wp-block-columns">

            <!-- wp:column {"style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"16px","right":"16px"}},"color":{"background":"#ffffff"},"border":{"radius":"8px"}}} -->
            <div class="wp-block-column" style="background-color:#ffffff;border-radius:8px;padding-top:20px;padding-bottom:20px;padding-left:16px;padding-right:16px">
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"28px"}}} -->
                <p class="has-text-align-center" style="font-size:28px">✨</p>
                <!-- /wp:paragraph -->
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"14px","fontWeight":"600"},"color":{"text":"#1a1a2e"}}} -->
                <p class="has-text-align-center" style="color:#1a1a2e;font-size:14px;font-weight:600">Complete your profile</p>
                <!-- /wp:paragraph -->
            </div>
            <!-- /wp:column -->

            <!-- wp:column {"style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"16px","right":"16px"}},"color":{"background":"#ffffff"},"border":{"radius":"8px"}}} -->
            <div class="wp-block-column" style="background-color:#ffffff;border-radius:8px;padding-top:20px;padding-bottom:20px;padding-left:16px;padding-right:16px">
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"28px"}}} -->
                <p class="has-text-align-center" style="font-size:28px">🔍</p>
                <!-- /wp:paragraph -->
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"14px","fontWeight":"600"},"color":{"text":"#1a1a2e"}}} -->
                <p class="has-text-align-center" style="color:#1a1a2e;font-size:14px;font-weight:600">Explore content</p>
                <!-- /wp:paragraph -->
            </div>
            <!-- /wp:column -->

            <!-- wp:column {"style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"16px","right":"16px"}},"color":{"background":"#ffffff"},"border":{"radius":"8px"}}} -->
            <div class="wp-block-column" style="background-color:#ffffff;border-radius:8px;padding-top:20px;padding-bottom:20px;padding-left:16px;padding-right:16px">
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"28px"}}} -->
                <p class="has-text-align-center" style="font-size:28px">💬</p>
                <!-- /wp:paragraph -->
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"14px","fontWeight":"600"},"color":{"text":"#1a1a2e"}}} -->
                <p class="has-text-align-center" style="color:#1a1a2e;font-size:14px;font-weight:600">Join the community</p>
                <!-- /wp:paragraph -->
            </div>
            <!-- /wp:column -->

        </div>
        <!-- /wp:columns -->

    </div>
    <!-- /wp:group -->

    <!-- wp:spacer {"height":"32px"} -->
    <div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div>
    <!-- /wp:spacer -->

    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-buttons">
        <!-- wp:button {"style":{"color":{"background":"#4f46e5","text":"#ffffff"},"border":{"radius":"8px"},"spacing":{"padding":{"top":"14px","bottom":"14px","left":"32px","right":"32px"}},"typography":{"fontSize":"16px","fontWeight":"600"}}} -->
        <div class="wp-block-button"><a class="wp-block-button__link" style="background-color:#4f46e5;color:#ffffff;border-radius:8px;padding-top:14px;padding-bottom:14px;padding-left:32px;padding-right:32px;font-size:16px;font-weight:600" href="{{site_url}}">Get Started →</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->

    <!-- wp:spacer {"height":"40px"} -->
    <div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
    <!-- /wp:spacer -->

    <!-- wp:separator {"style":{"color":{"background":"#e8eaed"}}} -->
    <hr class="wp-block-separator" style="background-color:#e8eaed;border-color:#e8eaed"/>
    <!-- /wp:separator -->

    <!-- wp:spacer {"height":"24px"} -->
    <div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
    <!-- /wp:spacer -->

    <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px"},"color":{"text":"#999aaa"}}} -->
    <p class="has-text-align-center" style="color:#999aaa;font-size:13px">You're receiving this because you signed up at <a href="{{site_url}}" style="color:#4f46e5">{{site_name}}</a>. · <a href="{{unsubscribe_url}}" style="color:#999aaa">Unsubscribe</a></p>
    <!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
BLOCKS;
    }

    // ─── NEW POST ────────────────────────────────────────────────────────────
    public static function new_post(): string
    {
        return <<<BLOCKS
<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","bottom":"0px","left":"0px","right":"0px"}},"color":{"background":"#0f172a"}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<div class="wp-block-group" style="background-color:#0f172a;padding:0">

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"32px","right":"32px"}}}} -->
    <div class="wp-block-group" style="padding-top:40px;padding-bottom:40px;padding-left:32px;padding-right:32px">
        <!-- wp:paragraph {"style":{"typography":{"fontSize":"13px","letterSpacing":"3px","fontWeight":"600"},"color":{"text":"#818cf8"}}} -->
        <p style="color:#818cf8;font-size:13px;letter-spacing:3px;font-weight:600">NEW ARTICLE</p>
        <!-- /wp:paragraph -->
        <!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"30px","fontWeight":"700","lineHeight":"1.3"},"color":{"text":"#f1f5f9"}}} -->
        <h1 class="wp-block-heading" style="color:#f1f5f9;font-size:30px;font-weight:700;line-height:1.3">{{post_title}}</h1>
        <!-- /wp:heading -->
        <!-- wp:paragraph {"style":{"typography":{"fontSize":"15px","lineHeight":"1.7"},"color":{"text":"#94a3b8"}}} -->
        <p style="color:#94a3b8;font-size:15px;line-height:1.7">{{post_excerpt}}</p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer {"height":"8px"} --><div style="height:8px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
        <!-- wp:paragraph {"style":{"typography":{"fontSize":"13px"},"color":{"text":"#64748b"}}} -->
        <p style="color:#64748b;font-size:13px">By <strong style="color:#94a3b8">{{post_author}}</strong> · {{post_date}} · {{read_time}} min read</p>
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0"}},"color":{"background":"#ffffff"}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<div class="wp-block-group" style="background-color:#ffffff">

    <!-- wp:image {"align":"full","style":{"border":{"radius":"0px"}}} -->
    <figure class="wp-block-image alignfull"><img src="{{post_image}}" alt="{{post_title}}"/></figure>
    <!-- /wp:image -->

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"32px","right":"32px"}}}} -->
    <div class="wp-block-group" style="padding-top:40px;padding-bottom:40px;padding-left:32px;padding-right:32px">

        <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px","lineHeight":"1.8"},"color":{"text":"#374151"}}} -->
        <p style="color:#374151;font-size:16px;line-height:1.8">{{post_content_preview}}</p>
        <!-- /wp:paragraph -->

        <!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:buttons -->
        <div class="wp-block-buttons">
            <!-- wp:button {"style":{"color":{"background":"#0f172a","text":"#ffffff"},"border":{"radius":"6px"},"spacing":{"padding":{"top":"13px","bottom":"13px","left":"28px","right":"28px"}},"typography":{"fontSize":"15px","fontWeight":"600"}}} -->
            <div class="wp-block-button"><a class="wp-block-button__link" style="background-color:#0f172a;color:#ffffff;border-radius:6px;padding:13px 28px;font-size:15px;font-weight:600" href="{{post_url}}">Read Full Article →</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->

        <!-- wp:spacer {"height":"40px"} --><div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
        <!-- wp:separator {"style":{"color":{"background":"#e5e7eb"}}} --><hr class="wp-block-separator" style="background-color:#e5e7eb;border-color:#e5e7eb"/><!-- /wp:separator -->
        <!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px"},"color":{"text":"#9ca3af"}}} -->
        <p class="has-text-align-center" style="color:#9ca3af;font-size:13px"><a href="{{site_url}}" style="color:#6366f1">{{site_name}}</a> · <a href="{{unsubscribe_url}}" style="color:#9ca3af">Unsubscribe</a></p>
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->
BLOCKS;
    }

    // ─── NEW PRODUCTS ────────────────────────────────────────────────────────
    public static function new_products(): string
    {
        return <<<BLOCKS
<!-- wp:group {"style":{"spacing":{"padding":{"top":"48px","bottom":"48px","left":"32px","right":"32px"}},"color":{"background":"#ffffff"}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<div class="wp-block-group" style="background-color:#ffffff;padding:48px 32px">

    <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px","letterSpacing":"3px","fontWeight":"700"},"color":{"text":"#6366f1"}}} -->
    <p class="has-text-align-center" style="color:#6366f1;font-size:13px;letter-spacing:3px;font-weight:700">JUST ARRIVED</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"28px","fontWeight":"700"},"color":{"text":"#111827"}}} -->
    <h1 class="wp-block-heading has-text-align-center" style="color:#111827;font-size:28px;font-weight:700">Fresh Picks for You, {{first_name}}</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"16px","lineHeight":"1.6"},"color":{"text":"#6b7280"}}} -->
    <p class="has-text-align-center" style="color:#6b7280;font-size:16px;line-height:1.6">Handpicked new arrivals we think you'll love.</p>
    <!-- /wp:paragraph -->

    <!-- wp:spacer {"height":"32px"} --><div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

    <!-- wp:columns {"style":{"spacing":{"blockGap":"24px"}}} -->
    <div class="wp-block-columns">

        <!-- wp:column {"style":{"border":{"radius":"10px","width":"1px","color":"#e5e7eb"},"spacing":{"padding":{"top":"0","bottom":"20px","left":"0","right":"0"}}}} -->
        <div class="wp-block-column" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;padding-bottom:20px">
            <!-- wp:image {"align":"full"} -->
            <figure class="wp-block-image alignfull"><img src="{{product_1_image}}" alt="{{product_1_name}}"/></figure>
            <!-- /wp:image -->
            <!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","bottom":"0","left":"16px","right":"16px"}}}} -->
            <div class="wp-block-group" style="padding:16px 16px 0">
                <!-- wp:paragraph {"style":{"typography":{"fontSize":"11px","letterSpacing":"2px","fontWeight":"600"},"color":{"text":"#9ca3af"}}} -->
                <p style="color:#9ca3af;font-size:11px;letter-spacing:2px;font-weight:600">{{product_1_category}}</p>
                <!-- /wp:paragraph -->
                <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px","fontWeight":"600"},"color":{"text":"#111827"}}} -->
                <p style="color:#111827;font-size:16px;font-weight:600">{{product_1_name}}</p>
                <!-- /wp:paragraph -->
                <!-- wp:paragraph {"style":{"typography":{"fontSize":"18px","fontWeight":"700"},"color":{"text":"#6366f1"}}} -->
                <p style="color:#6366f1;font-size:18px;font-weight:700">{{product_1_price}}</p>
                <!-- /wp:paragraph -->
                <!-- wp:buttons -->
                <div class="wp-block-buttons">
                    <!-- wp:button {"width":100,"style":{"color":{"background":"#111827","text":"#ffffff"},"border":{"radius":"6px"},"typography":{"fontSize":"13px"}}} -->
                    <div class="wp-block-button is-style-fill" style="width:100%"><a class="wp-block-button__link" style="background-color:#111827;color:#ffffff;border-radius:6px;font-size:13px;width:100%;text-align:center;display:block" href="{{product_1_url}}">Shop Now</a></div>
                    <!-- /wp:button -->
                </div>
                <!-- /wp:buttons -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column {"style":{"border":{"radius":"10px","width":"1px","color":"#e5e7eb"},"spacing":{"padding":{"top":"0","bottom":"20px","left":"0","right":"0"}}}} -->
        <div class="wp-block-column" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;padding-bottom:20px">
            <!-- wp:image {"align":"full"} -->
            <figure class="wp-block-image alignfull"><img src="{{product_2_image}}" alt="{{product_2_name}}"/></figure>
            <!-- /wp:image -->
            <!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","bottom":"0","left":"16px","right":"16px"}}}} -->
            <div class="wp-block-group" style="padding:16px 16px 0">
                <!-- wp:paragraph {"style":{"typography":{"fontSize":"11px","letterSpacing":"2px","fontWeight":"600"},"color":{"text":"#9ca3af"}}} -->
                <p style="color:#9ca3af;font-size:11px;letter-spacing:2px;font-weight:600">{{product_2_category}}</p>
                <!-- /wp:paragraph -->
                <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px","fontWeight":"600"},"color":{"text":"#111827"}}} -->
                <p style="color:#111827;font-size:16px;font-weight:600">{{product_2_name}}</p>
                <!-- /wp:paragraph -->
                <!-- wp:paragraph {"style":{"typography":{"fontSize":"18px","fontWeight":"700"},"color":{"text":"#6366f1"}}} -->
                <p style="color:#6366f1;font-size:18px;font-weight:700">{{product_2_price}}</p>
                <!-- /wp:paragraph -->
                <!-- wp:buttons -->
                <div class="wp-block-buttons">
                    <!-- wp:button {"width":100,"style":{"color":{"background":"#111827","text":"#ffffff"},"border":{"radius":"6px"},"typography":{"fontSize":"13px"}}} -->
                    <div class="wp-block-button is-style-fill" style="width:100%"><a class="wp-block-button__link" style="background-color:#111827;color:#ffffff;border-radius:6px;font-size:13px;width:100%;text-align:center;display:block" href="{{product_2_url}}">Shop Now</a></div>
                    <!-- /wp:button -->
                </div>
                <!-- /wp:buttons -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->

    </div>
    <!-- /wp:columns -->

    <!-- wp:spacer {"height":"32px"} --><div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-buttons">
        <!-- wp:button {"style":{"color":{"background":"#ffffff","text":"#111827"},"border":{"radius":"6px","width":"2px","color":"#111827"},"spacing":{"padding":{"top":"12px","bottom":"12px","left":"28px","right":"28px"}},"typography":{"fontSize":"15px","fontWeight":"600"}}} -->
        <div class="wp-block-button"><a class="wp-block-button__link" style="background-color:#ffffff;color:#111827;border:2px solid #111827;border-radius:6px;padding:12px 28px;font-size:15px;font-weight:600" href="{{shop_url}}">Browse All Products</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->

    <!-- wp:spacer {"height":"40px"} --><div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
    <!-- wp:separator {"style":{"color":{"background":"#e5e7eb"}}} --><hr class="wp-block-separator" style="background-color:#e5e7eb;border-color:#e5e7eb"/><!-- /wp:separator -->
    <!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
    <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px"},"color":{"text":"#9ca3af"}}} -->
    <p class="has-text-align-center" style="color:#9ca3af;font-size:13px"><a href="{{site_url}}" style="color:#6366f1">{{site_name}}</a> · <a href="{{unsubscribe_url}}" style="color:#9ca3af">Unsubscribe</a></p>
    <!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
BLOCKS;
    }

    // ─── RE-ENGAGEMENT ───────────────────────────────────────────────────────
    public static function re_engagement(): string
    {
        return <<<BLOCKS
<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0"}},"color":{"background":"#fafafa"}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<div class="wp-block-group" style="background-color:#fafafa">

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"64px","bottom":"48px","left":"40px","right":"40px"}},"color":{"background":"#18181b"},"border":{"radius":"0px"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="background-color:#18181b;padding:64px 40px 48px">

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"64px"}}} -->
        <p class="has-text-align-center" style="font-size:64px">👋</p>
        <!-- /wp:paragraph -->

        <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"28px","fontWeight":"700","lineHeight":"1.3"},"color":{"text":"#f4f4f5"}}} -->
        <h1 class="wp-block-heading has-text-align-center" style="color:#f4f4f5;font-size:28px;font-weight:700;line-height:1.3">We miss you, {{first_name}}.</h1>
        <!-- /wp:heading -->

        <!-- wp:spacer {"height":"12px"} --><div style="height:12px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"16px","lineHeight":"1.7"},"color":{"text":"#a1a1aa"}}} -->
        <p class="has-text-align-center" style="color:#a1a1aa;font-size:16px;line-height:1.7">It's been a while since we last saw you. We've been busy building things we think you'll love — come see what's new.</p>
        <!-- /wp:paragraph -->

        <!-- wp:spacer {"height":"32px"} --><div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-buttons">
            <!-- wp:button {"style":{"color":{"background":"#a78bfa","text":"#ffffff"},"border":{"radius":"8px"},"spacing":{"padding":{"top":"14px","bottom":"14px","left":"36px","right":"36px"}},"typography":{"fontSize":"16px","fontWeight":"700"}}} -->
            <div class="wp-block-button"><a class="wp-block-button__link" style="background-color:#a78bfa;color:#ffffff;border-radius:8px;padding:14px 36px;font-size:16px;font-weight:700" href="{{site_url}}">Take Me Back →</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->

    </div>
    <!-- /wp:group -->

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"40px","right":"40px"}},"color":{"background":"#ffffff"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="background-color:#ffffff;padding:40px">

        <!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"16px","fontWeight":"600"},"color":{"text":"#18181b"}}} -->
        <h3 class="wp-block-heading has-text-align-center" style="color:#18181b;font-size:16px;font-weight:600">While you were away…</h3>
        <!-- /wp:heading -->

        <!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:group {"style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"20px","right":"20px"}},"color":{"background":"#f4f4f5"},"border":{"radius":"8px"}}} -->
        <div class="wp-block-group" style="background-color:#f4f4f5;border-radius:8px;padding:20px">
            <!-- wp:paragraph {"style":{"typography":{"fontSize":"15px","lineHeight":"1.6"},"color":{"text":"#3f3f46"}}} -->
            <p style="color:#3f3f46;font-size:15px;line-height:1.6">🆕 &nbsp;<strong>{{new_feature_1}}</strong> — {{new_feature_1_desc}}</p>
            <!-- /wp:paragraph -->
            <!-- wp:paragraph {"style":{"typography":{"fontSize":"15px","lineHeight":"1.6"},"color":{"text":"#3f3f46"}}} -->
            <p style="color:#3f3f46;font-size:15px;line-height:1.6">🆕 &nbsp;<strong>{{new_feature_2}}</strong> — {{new_feature_2_desc}}</p>
            <!-- /wp:paragraph -->
            <!-- wp:paragraph {"style":{"typography":{"fontSize":"15px","lineHeight":"1.6"},"color":{"text":"#3f3f46"}}} -->
            <p style="color:#3f3f46;font-size:15px;line-height:1.6">🆕 &nbsp;<strong>{{new_feature_3}}</strong> — {{new_feature_3_desc}}</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->

        <!-- wp:spacer {"height":"32px"} --><div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:separator {"style":{"color":{"background":"#e4e4e7"}}} --><hr class="wp-block-separator" style="background-color:#e4e4e7;border-color:#e4e4e7"/><!-- /wp:separator -->
        <!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px"},"color":{"text":"#a1a1aa"}}} -->
        <p class="has-text-align-center" style="color:#a1a1aa;font-size:13px">Not interested anymore? <a href="{{unsubscribe_url}}" style="color:#a1a1aa">Unsubscribe</a></p>
        <!-- /wp:paragraph -->

    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->
BLOCKS;
    }

    // ─── NEWSLETTER ──────────────────────────────────────────────────────────
    public static function newsletter(): string
    {
        return <<<BLOCKS
<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0"}},"color":{"background":"#f8fafc"}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<div class="wp-block-group" style="background-color:#f8fafc">

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}},"color":{"background":"#1e293b"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
    <div class="wp-block-group" style="background-color:#1e293b;padding:32px;display:flex;justify-content:space-between;align-items:center">
        <!-- wp:paragraph {"style":{"typography":{"fontSize":"20px","fontWeight":"700","letterSpacing":"-0.5px"},"color":{"text":"#f1f5f9"}}} -->
        <p style="color:#f1f5f9;font-size:20px;font-weight:700;letter-spacing:-0.5px">{{site_name}} <span style="color:#38bdf8">Weekly</span></p>
        <!-- /wp:paragraph -->
        <!-- wp:paragraph {"style":{"typography":{"fontSize":"13px"},"color":{"text":"#64748b"}}} -->
        <p style="color:#64748b;font-size:13px">Issue #{{issue_number}} · {{issue_date}}</p>
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"32px","right":"32px"}},"color":{"background":"#ffffff"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="background-color:#ffffff;padding:40px 32px">

        <!-- wp:paragraph {"style":{"typography":{"fontSize":"14px","fontWeight":"600","letterSpacing":"2px"},"color":{"text":"#38bdf8"}}} -->
        <p style="color:#38bdf8;font-size:14px;font-weight:600;letter-spacing:2px">THIS WEEK</p>
        <!-- /wp:paragraph -->

        <!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"26px","fontWeight":"700","lineHeight":"1.3"},"color":{"text":"#0f172a"}}} -->
        <h2 class="wp-block-heading" style="color:#0f172a;font-size:26px;font-weight:700;line-height:1.3">{{newsletter_headline}}</h2>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px","lineHeight":"1.8"},"color":{"text":"#475569"}}} -->
        <p style="color:#475569;font-size:16px;line-height:1.8">{{newsletter_intro}}</p>
        <!-- /wp:paragraph -->

        <!-- wp:spacer {"height":"32px"} --><div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
        <!-- wp:separator {"style":{"color":{"background":"#e2e8f0"}}} --><hr class="wp-block-separator" style="background-color:#e2e8f0;border-color:#e2e8f0"/><!-- /wp:separator -->
        <!-- wp:spacer {"height":"32px"} --><div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"24px","left":"0","right":"0"}},"border":{"bottom":{"color":"#e2e8f0","width":"1px"}}}} -->
        <div class="wp-block-group" style="padding-bottom:24px;border-bottom:1px solid #e2e8f0">
            <!-- wp:paragraph {"style":{"typography":{"fontSize":"11px","fontWeight":"700","letterSpacing":"2px"},"color":{"text":"#94a3b8"}}} -->
            <p style="color:#94a3b8;font-size:11px;font-weight:700;letter-spacing:2px">STORY 1</p>
            <!-- /wp:paragraph -->
            <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"19px","fontWeight":"600"},"color":{"text":"#0f172a"}}} -->
            <h3 class="wp-block-heading" style="color:#0f172a;font-size:19px;font-weight:600">{{story_1_title}}</h3>
            <!-- /wp:heading -->
            <!-- wp:paragraph {"style":{"typography":{"fontSize":"15px","lineHeight":"1.7"},"color":{"text":"#64748b"}}} -->
            <p style="color:#64748b;font-size:15px;line-height:1.7">{{story_1_excerpt}}</p>
            <!-- /wp:paragraph -->
            <!-- wp:paragraph -->
            <p><a href="{{story_1_url}}" style="color:#0ea5e9;font-size:14px;font-weight:600;text-decoration:none">Read more →</a></p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->

        <!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"24px","left":"0","right":"0"}},"border":{"bottom":{"color":"#e2e8f0","width":"1px"}}}} -->
        <div class="wp-block-group" style="padding-bottom:24px;border-bottom:1px solid #e2e8f0">
            <!-- wp:paragraph {"style":{"typography":{"fontSize":"11px","fontWeight":"700","letterSpacing":"2px"},"color":{"text":"#94a3b8"}}} -->
            <p style="color:#94a3b8;font-size:11px;font-weight:700;letter-spacing:2px">STORY 2</p>
            <!-- /wp:paragraph -->
            <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"19px","fontWeight":"600"},"color":{"text":"#0f172a"}}} -->
            <h3 class="wp-block-heading" style="color:#0f172a;font-size:19px;font-weight:600">{{story_2_title}}</h3>
            <!-- /wp:heading -->
            <!-- wp:paragraph {"style":{"typography":{"fontSize":"15px","lineHeight":"1.7"},"color":{"text":"#64748b"}}} -->
            <p style="color:#64748b;font-size:15px;line-height:1.7">{{story_2_excerpt}}</p>
            <!-- /wp:paragraph -->
            <!-- wp:paragraph -->
            <p><a href="{{story_2_url}}" style="color:#0ea5e9;font-size:14px;font-weight:600;text-decoration:none">Read more →</a></p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->

        <!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:group {"style":{"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"color":{"background":"#f0f9ff"},"border":{"radius":"8px","left":{"color":"#0ea5e9","width":"4px"}}}} -->
        <div class="wp-block-group" style="background-color:#f0f9ff;border-radius:8px;border-left:4px solid #0ea5e9;padding:24px">
            <!-- wp:paragraph {"style":{"typography":{"fontSize":"13px","fontWeight":"700","letterSpacing":"1px"},"color":{"text":"#0ea5e9"}}} -->
            <p style="color:#0ea5e9;font-size:13px;font-weight:700;letter-spacing:1px">💡 QUICK TIP OF THE WEEK</p>
            <!-- /wp:paragraph -->
            <!-- wp:paragraph {"style":{"typography":{"fontSize":"15px","lineHeight":"1.7"},"color":{"text":"#1e293b"}}} -->
            <p style="color:#1e293b;font-size:15px;line-height:1.7">{{tip_of_week}}</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->

        <!-- wp:spacer {"height":"40px"} --><div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
        <!-- wp:separator {"style":{"color":{"background":"#e2e8f0"}}} --><hr class="wp-block-separator" style="background-color:#e2e8f0;border-color:#e2e8f0"/><!-- /wp:separator -->
        <!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px"},"color":{"text":"#94a3b8"}}} -->
        <p class="has-text-align-center" style="color:#94a3b8;font-size:13px">You're getting this because you subscribed to <a href="{{site_url}}" style="color:#0ea5e9">{{site_name}}</a> · <a href="{{unsubscribe_url}}" style="color:#94a3b8">Unsubscribe</a></p>
        <!-- /wp:paragraph -->

    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->
BLOCKS;
    }

    // ─── PROMOTION ───────────────────────────────────────────────────────────
    public static function promotion(): string
    {
        return <<<BLOCKS
<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0"}},"color":{"background":"#ffffff"}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<div class="wp-block-group" style="background-color:#ffffff">

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"56px","bottom":"56px","left":"40px","right":"40px"}},"color":{"background":"#7c3aed"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="background-color:#7c3aed;padding:56px 40px">

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px","letterSpacing":"4px","fontWeight":"700"},"color":{"text":"#ddd6fe"}}} -->
        <p class="has-text-align-center" style="color:#ddd6fe;font-size:13px;letter-spacing:4px;font-weight:700">LIMITED TIME OFFER</p>
        <!-- /wp:paragraph -->

        <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"56px","fontWeight":"900","lineHeight":"1"},"color":{"text":"#ffffff"}}} -->
        <h1 class="wp-block-heading has-text-align-center" style="color:#ffffff;font-size:56px;font-weight:900;line-height:1">{{discount_percent}}%<br>OFF</h1>
        <!-- /wp:heading -->

        <!-- wp:spacer {"height":"8px"} --><div style="height:8px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"18px","lineHeight":"1.5"},"color":{"text":"#ede9fe"}}} -->
        <p class="has-text-align-center" style="color:#ede9fe;font-size:18px;line-height:1.5">{{promotion_description}}</p>
        <!-- /wp:paragraph -->

        <!-- wp:spacer {"height":"8px"} --><div style="height:8px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:group {"style":{"spacing":{"padding":{"top":"12px","bottom":"12px","left":"24px","right":"24px"}},"color":{"background":"#6d28d9"},"border":{"radius":"6px"}},"layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-group" style="background-color:#6d28d9;border-radius:6px;padding:12px 24px;display:inline-flex;margin:0 auto">
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"15px","fontWeight":"600","letterSpacing":"2px"},"color":{"text":"#c4b5fd"}}} -->
            <p class="has-text-align-center" style="color:#c4b5fd;font-size:15px;font-weight:600;letter-spacing:2px">Use code: <strong style="color:#ffffff;font-size:20px">{{promo_code}}</strong></p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->

        <!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-buttons">
            <!-- wp:button {"style":{"color":{"background":"#ffffff","text":"#7c3aed"},"border":{"radius":"8px"},"spacing":{"padding":{"top":"16px","bottom":"16px","left":"40px","right":"40px"}},"typography":{"fontSize":"17px","fontWeight":"800"}}} -->
            <div class="wp-block-button"><a class="wp-block-button__link" style="background-color:#ffffff;color:#7c3aed;border-radius:8px;padding:16px 40px;font-size:17px;font-weight:800" href="{{shop_url}}">Claim My Discount</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->

        <!-- wp:spacer {"height":"16px"} --><div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px"},"color":{"text":"#c4b5fd"}}} -->
        <p class="has-text-align-center" style="color:#c4b5fd;font-size:13px">⏰ Offer expires {{expiry_date}}. No minimum order required.</p>
        <!-- /wp:paragraph -->

    </div>
    <!-- /wp:group -->

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"32px","right":"32px"}},"color":{"background":"#faf5ff"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="background-color:#faf5ff;padding:40px 32px">

        <!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"16px","fontWeight":"600"},"color":{"text":"#1f2937"}}} -->
        <h3 class="wp-block-heading has-text-align-center" style="color:#1f2937;font-size:16px;font-weight:600">Why shop with us?</h3>
        <!-- /wp:heading -->

        <!-- wp:spacer {"height":"16px"} --><div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:columns -->
        <div class="wp-block-columns">
            <!-- wp:column {"style":{"spacing":{"padding":{"top":"16px","bottom":"16px","left":"16px","right":"16px"}}}} -->
            <div class="wp-block-column" style="padding:16px">
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"24px"}}} --><p class="has-text-align-center" style="font-size:24px">🚚</p><!-- /wp:paragraph -->
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px","fontWeight":"600"},"color":{"text":"#374151"}}} --><p class="has-text-align-center" style="color:#374151;font-size:13px;font-weight:600">Free Shipping</p><!-- /wp:paragraph -->
            </div>
            <!-- /wp:column -->
            <!-- wp:column {"style":{"spacing":{"padding":{"top":"16px","bottom":"16px","left":"16px","right":"16px"}}}} -->
            <div class="wp-block-column" style="padding:16px">
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"24px"}}} --><p class="has-text-align-center" style="font-size:24px">↩️</p><!-- /wp:paragraph -->
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px","fontWeight":"600"},"color":{"text":"#374151"}}} --><p class="has-text-align-center" style="color:#374151;font-size:13px;font-weight:600">Easy Returns</p><!-- /wp:paragraph -->
            </div>
            <!-- /wp:column -->
            <!-- wp:column {"style":{"spacing":{"padding":{"top":"16px","bottom":"16px","left":"16px","right":"16px"}}}} -->
            <div class="wp-block-column" style="padding:16px">
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"24px"}}} --><p class="has-text-align-center" style="font-size:24px">🔒</p><!-- /wp:paragraph -->
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px","fontWeight":"600"},"color":{"text":"#374151"}}} --><p class="has-text-align-center" style="color:#374151;font-size:13px;font-weight:600">Secure Checkout</p><!-- /wp:paragraph -->
            </div>
            <!-- /wp:column -->
        </div>
        <!-- /wp:columns -->

        <!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
        <!-- wp:separator {"style":{"color":{"background":"#ede9fe"}}} --><hr class="wp-block-separator" style="background-color:#ede9fe;border-color:#ede9fe"/><!-- /wp:separator -->
        <!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px"},"color":{"text":"#9ca3af"}}} -->
        <p class="has-text-align-center" style="color:#9ca3af;font-size:13px"><a href="{{site_url}}" style="color:#7c3aed">{{site_name}}</a> · <a href="{{unsubscribe_url}}" style="color:#9ca3af">Unsubscribe</a></p>
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->
BLOCKS;
    }

    // ─── ANNOUNCEMENT ────────────────────────────────────────────────────────
    public static function announcement(): string
    {
        return <<<BLOCKS
<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0"}},"color":{"background":"#ffffff"}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<div class="wp-block-group" style="background-color:#ffffff">

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"48px","bottom":"48px","left":"40px","right":"40px"}},"color":{"background":"#ecfdf5"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="background-color:#ecfdf5;padding:48px 40px">

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"48px"}}} -->
        <p class="has-text-align-center" style="font-size:48px">📣</p>
        <!-- /wp:paragraph -->

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"12px","letterSpacing":"3px","fontWeight":"700"},"color":{"text":"#059669"}}} -->
        <p class="has-text-align-center" style="color:#059669;font-size:12px;letter-spacing:3px;font-weight:700">ANNOUNCEMENT</p>
        <!-- /wp:paragraph -->

        <!-- wp:spacer {"height":"8px"} --><div style="height:8px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"30px","fontWeight":"700","lineHeight":"1.3"},"color":{"text":"#064e3b"}}} -->
        <h1 class="wp-block-heading has-text-align-center" style="color:#064e3b;font-size:30px;font-weight:700;line-height:1.3">{{announcement_title}}</h1>
        <!-- /wp:heading -->

    </div>
    <!-- /wp:group -->

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"40px","right":"40px"}},"color":{"background":"#ffffff"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="background-color:#ffffff;padding:40px">

        <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px","lineHeight":"1.8"},"color":{"text":"#374151"}}} -->
        <p style="color:#374151;font-size:16px;line-height:1.8">Hi {{first_name}},</p>
        <!-- /wp:paragraph -->

        <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px","lineHeight":"1.8"},"color":{"text":"#374151"}}} -->
        <p style="color:#374151;font-size:16px;line-height:1.8">{{announcement_body}}</p>
        <!-- /wp:paragraph -->

        <!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:group {"style":{"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"color":{"background":"#f0fdf4"},"border":{"radius":"8px","width":"1px","color":"#bbf7d0"}}} -->
        <div class="wp-block-group" style="background-color:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:24px">
            <!-- wp:paragraph {"style":{"typography":{"fontSize":"15px","lineHeight":"1.7"},"color":{"text":"#166534"}}} -->
            <p style="color:#166534;font-size:15px;line-height:1.7">{{announcement_highlight}}</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->

        <!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px","lineHeight":"1.8"},"color":{"text":"#374151"}}} -->
        <p style="color:#374151;font-size:16px;line-height:1.8">{{announcement_closing}}</p>
        <!-- /wp:paragraph -->

        <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"color":{"text":"#374151"}}} -->
        <p style="color:#374151;font-size:16px">Warm regards,<br><strong>The {{site_name}} Team</strong></p>
        <!-- /wp:paragraph -->

        <!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-buttons">
            <!-- wp:button {"style":{"color":{"background":"#059669","text":"#ffffff"},"border":{"radius":"8px"},"spacing":{"padding":{"top":"13px","bottom":"13px","left":"32px","right":"32px"}},"typography":{"fontSize":"15px","fontWeight":"600"}}} -->
            <div class="wp-block-button"><a class="wp-block-button__link" style="background-color:#059669;color:#ffffff;border-radius:8px;padding:13px 32px;font-size:15px;font-weight:600" href="{{announcement_url}}">Learn More</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->

        <!-- wp:spacer {"height":"40px"} --><div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
        <!-- wp:separator {"style":{"color":{"background":"#e5e7eb"}}} --><hr class="wp-block-separator" style="background-color:#e5e7eb;border-color:#e5e7eb"/><!-- /wp:separator -->
        <!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px"},"color":{"text":"#9ca3af"}}} -->
        <p class="has-text-align-center" style="color:#9ca3af;font-size:13px"><a href="{{site_url}}" style="color:#059669">{{site_name}}</a> · <a href="{{unsubscribe_url}}" style="color:#9ca3af">Unsubscribe</a></p>
        <!-- /wp:paragraph -->

    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->
BLOCKS;
    }

    // ─── SALE ANNOUNCEMENT ───────────────────────────────────────────────────
    public static function sale_announcement(): string
    {
        return <<<BLOCKS
<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0"}},"color":{"background":"#fff7ed"}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<div class="wp-block-group" style="background-color:#fff7ed">

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"56px","bottom":"56px","left":"40px","right":"40px"}},"color":{"background":"#ea580c"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="background-color:#ea580c;padding:56px 40px">

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"12px","letterSpacing":"4px","fontWeight":"700"},"color":{"text":"#fed7aa"}}} -->
        <p class="has-text-align-center" style="color:#fed7aa;font-size:12px;letter-spacing:4px;font-weight:700">🔥 FLASH SALE — TODAY ONLY</p>
        <!-- /wp:paragraph -->

        <!-- wp:spacer {"height":"8px"} --><div style="height:8px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"48px","fontWeight":"900","lineHeight":"1.1"},"color":{"text":"#ffffff"}}} -->
        <h1 class="wp-block-heading has-text-align-center" style="color:#ffffff;font-size:48px;font-weight:900;line-height:1.1">The Big<br>{{sale_name}}</h1>
        <!-- /wp:heading -->

        <!-- wp:spacer {"height":"16px"} --><div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"17px","lineHeight":"1.5"},"color":{"text":"#ffedd5"}}} -->
        <p class="has-text-align-center" style="color:#ffedd5;font-size:17px;line-height:1.5">Up to <strong style="color:#fef3c7;font-size:22px">{{max_discount}}% off</strong> sitewide. No code needed — discount applied at checkout.</p>
        <!-- /wp:paragraph -->

        <!-- wp:spacer {"height":"28px"} --><div style="height:28px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-buttons">
            <!-- wp:button {"style":{"color":{"background":"#ffffff","text":"#ea580c"},"border":{"radius":"8px"},"spacing":{"padding":{"top":"16px","bottom":"16px","left":"40px","right":"40px"}},"typography":{"fontSize":"17px","fontWeight":"800"}}} -->
            <div class="wp-block-button"><a class="wp-block-button__link" style="background-color:#ffffff;color:#ea580c;border-radius:8px;padding:16px 40px;font-size:17px;font-weight:800" href="{{shop_url}}">Shop the Sale →</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->

    </div>
    <!-- /wp:group -->

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"32px","right":"32px"}},"color":{"background":"#ffffff"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="background-color:#ffffff;padding:40px 32px">

        <!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"18px","fontWeight":"700"},"color":{"text":"#1c1917"}}} -->
        <h3 class="wp-block-heading has-text-align-center" style="color:#1c1917;font-size:18px;font-weight:700">Top deals this sale</h3>
        <!-- /wp:heading -->

        <!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","bottom":"16px","left":"20px","right":"20px"}},"color":{"background":"#fff7ed"},"border":{"radius":"8px","left":{"color":"#ea580c","width":"4px"}}}} -->
        <div class="wp-block-group" style="background-color:#fff7ed;border-radius:8px;border-left:4px solid #ea580c;padding:16px 20px;margin-bottom:12px">
            <!-- wp:paragraph {"style":{"typography":{"fontSize":"15px"},"color":{"text":"#1c1917"}}} -->
            <p style="color:#1c1917;font-size:15px"><strong>{{deal_1_name}}</strong> — <span style="color:#ea580c;font-weight:700">{{deal_1_discount}}% off</span> &nbsp;<s style="color:#9ca3af">{{deal_1_original}}</s> → <strong>{{deal_1_price}}</strong></p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->

        <!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","bottom":"16px","left":"20px","right":"20px"}},"color":{"background":"#fff7ed"},"border":{"radius":"8px","left":{"color":"#ea580c","width":"4px"}}}} -->
        <div class="wp-block-group" style="background-color:#fff7ed;border-radius:8px;border-left:4px solid #ea580c;padding:16px 20px;margin-bottom:12px">
            <!-- wp:paragraph {"style":{"typography":{"fontSize":"15px"},"color":{"text":"#1c1917"}}} -->
            <p style="color:#1c1917;font-size:15px"><strong>{{deal_2_name}}</strong> — <span style="color:#ea580c;font-weight:700">{{deal_2_discount}}% off</span> &nbsp;<s style="color:#9ca3af">{{deal_2_original}}</s> → <strong>{{deal_2_price}}</strong></p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->

        <!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","bottom":"16px","left":"20px","right":"20px"}},"color":{"background":"#fff7ed"},"border":{"radius":"8px","left":{"color":"#ea580c","width":"4px"}}}} -->
        <div class="wp-block-group" style="background-color:#fff7ed;border-radius:8px;border-left:4px solid #ea580c;padding:16px 20px">
            <!-- wp:paragraph {"style":{"typography":{"fontSize":"15px"},"color":{"text":"#1c1917"}}} -->
            <p style="color:#1c1917;font-size:15px"><strong>{{deal_3_name}}</strong> — <span style="color:#ea580c;font-weight:700">{{deal_3_discount}}% off</span> &nbsp;<s style="color:#9ca3af">{{deal_3_original}}</s> → <strong>{{deal_3_price}}</strong></p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->

        <!-- wp:spacer {"height":"32px"} --><div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
        <!-- wp:separator {"style":{"color":{"background":"#e5e7eb"}}} --><hr class="wp-block-separator" style="background-color:#e5e7eb;border-color:#e5e7eb"/><!-- /wp:separator -->
        <!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px"},"color":{"text":"#9ca3af"}}} -->
        <p class="has-text-align-center" style="color:#9ca3af;font-size:13px"><a href="{{site_url}}" style="color:#ea580c">{{site_name}}</a> · <a href="{{unsubscribe_url}}" style="color:#9ca3af">Unsubscribe</a></p>
        <!-- /wp:paragraph -->

    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->
BLOCKS;
    }

    // ─── ABANDONED CART ──────────────────────────────────────────────────────
    public static function abandoned_cart(): string
    {
        return <<<BLOCKS
<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0"}},"color":{"background":"#f9fafb"}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<div class="wp-block-group" style="background-color:#f9fafb">

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"48px","bottom":"48px","left":"32px","right":"32px"}},"color":{"background":"#ffffff"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="background-color:#ffffff;padding:48px 32px">

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"40px"}}} -->
        <p class="has-text-align-center" style="font-size:40px">🛒</p>
        <!-- /wp:paragraph -->

        <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"26px","fontWeight":"700","lineHeight":"1.3"},"color":{"text":"#111827"}}} -->
        <h1 class="wp-block-heading has-text-align-center" style="color:#111827;font-size:26px;font-weight:700;line-height:1.3">You left something behind, {{first_name}}!</h1>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"16px","lineHeight":"1.6"},"color":{"text":"#6b7280"}}} -->
        <p class="has-text-align-center" style="color:#6b7280;font-size:16px;line-height:1.6">Your cart is waiting for you. Don't miss out — these items are going fast.</p>
        <!-- /wp:paragraph -->

        <!-- wp:spacer {"height":"32px"} --><div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:group {"style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"20px","right":"20px"}},"color":{"background":"#f9fafb"},"border":{"radius":"10px","width":"1px","color":"#e5e7eb"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
        <div class="wp-block-group" style="background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:20px;display:flex;align-items:center;gap:16px">
            <!-- wp:image {"width":80,"height":80,"style":{"border":{"radius":"8px"}}} -->
            <figure class="wp-block-image" style="border-radius:8px;flex-shrink:0"><img src="{{cart_item_1_image}}" alt="{{cart_item_1_name}}" width="80" height="80"/></figure>
            <!-- /wp:image -->
            <!-- wp:group {"layout":{"type":"constrained"}} -->
            <div class="wp-block-group" style="flex:1">
                <!-- wp:paragraph {"style":{"typography":{"fontSize":"15px","fontWeight":"600"},"color":{"text":"#111827"}}} -->
                <p style="color:#111827;font-size:15px;font-weight:600">{{cart_item_1_name}}</p>
                <!-- /wp:paragraph -->
                <!-- wp:paragraph {"style":{"typography":{"fontSize":"14px"},"color":{"text":"#6b7280"}}} -->
                <p style="color:#6b7280;font-size:14px">{{cart_item_1_variant}} · Qty: {{cart_item_1_qty}}</p>
                <!-- /wp:paragraph -->
                <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px","fontWeight":"700"},"color":{"text":"#111827"}}} -->
                <p style="color:#111827;font-size:16px;font-weight:700">{{cart_item_1_price}}</p>
                <!-- /wp:paragraph -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:group -->

        <!-- wp:spacer {"height":"12px"} --><div style="height:12px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","bottom":"16px","left":"20px","right":"20px"}},"color":{"background":"#fef9c3"},"border":{"radius":"8px"}}} -->
        <div class="wp-block-group" style="background-color:#fef9c3;border-radius:8px;padding:16px 20px">
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"14px","fontWeight":"600"},"color":{"text":"#92400e"}}} -->
            <p class="has-text-align-center" style="color:#92400e;font-size:14px;font-weight:600">🎁 Complete your order in the next 24 hours and get <strong>free shipping</strong>!</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->

        <!-- wp:spacer {"height":"28px"} --><div style="height:28px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-buttons">
            <!-- wp:button {"style":{"color":{"background":"#111827","text":"#ffffff"},"border":{"radius":"8px"},"spacing":{"padding":{"top":"15px","bottom":"15px","left":"40px","right":"40px"}},"typography":{"fontSize":"16px","fontWeight":"700"}}} -->
            <div class="wp-block-button"><a class="wp-block-button__link" style="background-color:#111827;color:#ffffff;border-radius:8px;padding:15px 40px;font-size:16px;font-weight:700" href="{{cart_url}}">Complete My Order →</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->

        <!-- wp:spacer {"height":"12px"} --><div style="height:12px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px"},"color":{"text":"#9ca3af"}}} -->
        <p class="has-text-align-center" style="color:#9ca3af;font-size:13px">Need help? <a href="{{support_url}}" style="color:#6366f1">Contact support</a></p>
        <!-- /wp:paragraph -->

        <!-- wp:spacer {"height":"40px"} --><div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
        <!-- wp:separator {"style":{"color":{"background":"#e5e7eb"}}} --><hr class="wp-block-separator" style="background-color:#e5e7eb;border-color:#e5e7eb"/><!-- /wp:separator -->
        <!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px"},"color":{"text":"#9ca3af"}}} -->
        <p class="has-text-align-center" style="color:#9ca3af;font-size:13px"><a href="{{site_url}}" style="color:#6366f1">{{site_name}}</a> · <a href="{{unsubscribe_url}}" style="color:#9ca3af">Unsubscribe</a></p>
        <!-- /wp:paragraph -->

    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->
BLOCKS;
    }

    // ─── SUBSCRIBER CONFIRMED ────────────────────────────────────────────────
    public static function subscriber_confirmed(): string
    {
        return <<<BLOCKS
<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0"}},"color":{"background":"#ffffff"}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<div class="wp-block-group" style="background-color:#ffffff">

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"64px","bottom":"64px","left":"40px","right":"40px"}},"color":{"background":"#f0fdf4"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="background-color:#f0fdf4;padding:64px 40px">

        <!-- wp:group {"style":{"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"color":{"background":"#22c55e"},"border":{"radius":"50%"}},"layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-group" style="background-color:#22c55e;border-radius:50%;width:72px;height:72px;margin:0 auto;display:flex;align-items:center;justify-content:center">
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"32px"}}} -->
            <p class="has-text-align-center" style="font-size:32px">✓</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->

        <!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"28px","fontWeight":"700"},"color":{"text":"#14532d"}}} -->
        <h1 class="wp-block-heading has-text-align-center" style="color:#14532d;font-size:28px;font-weight:700">You're confirmed! 🎉</h1>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"16px","lineHeight":"1.7"},"color":{"text":"#166534"}}} -->
        <p class="has-text-align-center" style="color:#166534;font-size:16px;line-height:1.7">Thanks, <strong>{{first_name}}</strong>. Your subscription to <strong>{{site_name}}</strong> is now active. We'll only send you good stuff, we promise.</p>
        <!-- /wp:paragraph -->

    </div>
    <!-- /wp:group -->

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"40px","right":"40px"}},"color":{"background":"#ffffff"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="background-color:#ffffff;padding:40px">

        <!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"16px","fontWeight":"600"},"color":{"text":"#111827"}}} -->
        <h3 class="wp-block-heading has-text-align-center" style="color:#111827;font-size:16px;font-weight:600">What to expect</h3>
        <!-- /wp:heading -->

        <!-- wp:spacer {"height":"16px"} --><div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:columns {"style":{"spacing":{"blockGap":"12px"}}} -->
        <div class="wp-block-columns">
            <!-- wp:column {"style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"20px","right":"20px"}},"color":{"background":"#f9fafb"},"border":{"radius":"8px"}}} -->
            <div class="wp-block-column" style="background-color:#f9fafb;border-radius:8px;padding:20px">
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"24px"}}} --><p class="has-text-align-center" style="font-size:24px">📬</p><!-- /wp:paragraph -->
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px","fontWeight":"600"},"color":{"text":"#374151"}}} --><p class="has-text-align-center" style="color:#374151;font-size:13px;font-weight:600">{{frequency}} emails</p><!-- /wp:paragraph -->
            </div>
            <!-- /wp:column -->
            <!-- wp:column {"style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"20px","right":"20px"}},"color":{"background":"#f9fafb"},"border":{"radius":"8px"}}} -->
            <div class="wp-block-column" style="background-color:#f9fafb;border-radius:8px;padding:20px">
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"24px"}}} --><p class="has-text-align-center" style="font-size:24px">🎯</p><!-- /wp:paragraph -->
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px","fontWeight":"600"},"color":{"text":"#374151"}}} --><p class="has-text-align-center" style="color:#374151;font-size:13px;font-weight:600">Curated content</p><!-- /wp:paragraph -->
            </div>
            <!-- /wp:column -->
            <!-- wp:column {"style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"20px","right":"20px"}},"color":{"background":"#f9fafb"},"border":{"radius":"8px"}}} -->
            <div class="wp-block-column" style="background-color:#f9fafb;border-radius:8px;padding:20px">
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"24px"}}} --><p class="has-text-align-center" style="font-size:24px">🚫</p><!-- /wp:paragraph -->
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px","fontWeight":"600"},"color":{"text":"#374151"}}} --><p class="has-text-align-center" style="color:#374151;font-size:13px;font-weight:600">No spam, ever</p><!-- /wp:paragraph -->
            </div>
            <!-- /wp:column -->
        </div>
        <!-- /wp:columns -->

        <!-- wp:spacer {"height":"28px"} --><div style="height:28px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-buttons">
            <!-- wp:button {"style":{"color":{"background":"#22c55e","text":"#ffffff"},"border":{"radius":"8px"},"spacing":{"padding":{"top":"13px","bottom":"13px","left":"32px","right":"32px"}},"typography":{"fontSize":"15px","fontWeight":"600"}}} -->
            <div class="wp-block-button"><a class="wp-block-button__link" style="background-color:#22c55e;color:#ffffff;border-radius:8px;padding:13px 32px;font-size:15px;font-weight:600" href="{{site_url}}">Explore {{site_name}}</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->

        <!-- wp:spacer {"height":"40px"} --><div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
        <!-- wp:separator {"style":{"color":{"background":"#e5e7eb"}}} --><hr class="wp-block-separator" style="background-color:#e5e7eb;border-color:#e5e7eb"/><!-- /wp:separator -->
        <!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px"},"color":{"text":"#9ca3af"}}} -->
        <p class="has-text-align-center" style="color:#9ca3af;font-size:13px">Changed your mind? <a href="{{unsubscribe_url}}" style="color:#9ca3af">Unsubscribe anytime</a></p>
        <!-- /wp:paragraph -->

    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->
BLOCKS;
    }

    // ─── UNSUBSCRIBE CONFIRMATION ─────────────────────────────────────────────
    public static function unsubscribe_confirmation(): string
    {
        return <<<BLOCKS
<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"40px","right":"40px"}},"color":{"background":"#f9fafb"}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<div class="wp-block-group" style="background-color:#f9fafb;padding:80px 40px">

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"48px","bottom":"48px","left":"40px","right":"40px"}},"color":{"background":"#ffffff"},"border":{"radius":"12px","width":"1px","color":"#e5e7eb"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="background-color:#ffffff;border:1px solid #e5e7eb;border-radius:12px;padding:48px 40px">

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"48px"}}} -->
        <p class="has-text-align-center" style="font-size:48px">👋</p>
        <!-- /wp:paragraph -->

        <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"26px","fontWeight":"700"},"color":{"text":"#111827"}}} -->
        <h1 class="wp-block-heading has-text-align-center" style="color:#111827;font-size:26px;font-weight:700">You've been unsubscribed</h1>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"16px","lineHeight":"1.7"},"color":{"text":"#6b7280"}}} -->
        <p class="has-text-align-center" style="color:#6b7280;font-size:16px;line-height:1.7"><strong>{{first_name}}</strong>, we've removed <strong>{{email}}</strong> from our mailing list. You won't receive any more emails from us.</p>
        <!-- /wp:paragraph -->

        <!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:group {"style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"24px","right":"24px"}},"color":{"background":"#f9fafb"},"border":{"radius":"8px"}}} -->
        <div class="wp-block-group" style="background-color:#f9fafb;border-radius:8px;padding:20px 24px">
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"14px","lineHeight":"1.6"},"color":{"text":"#6b7280"}}} -->
            <p class="has-text-align-center" style="color:#6b7280;font-size:14px;line-height:1.6">Unsubscribed by mistake? We'd love to have you back.</p>
            <!-- /wp:paragraph -->
            <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
            <div class="wp-block-buttons">
                <!-- wp:button {"style":{"color":{"background":"#ffffff","text":"#374151"},"border":{"radius":"6px","width":"1px","color":"#d1d5db"},"spacing":{"padding":{"top":"10px","bottom":"10px","left":"24px","right":"24px"}},"typography":{"fontSize":"14px","fontWeight":"600"}}} -->
                <div class="wp-block-button"><a class="wp-block-button__link" style="background-color:#ffffff;color:#374151;border:1px solid #d1d5db;border-radius:6px;padding:10px 24px;font-size:14px;font-weight:600" href="{{resubscribe_url}}">Re-subscribe</a></div>
                <!-- /wp:button -->
            </div>
            <!-- /wp:buttons -->
        </div>
        <!-- /wp:group -->

        <!-- wp:spacer {"height":"32px"} --><div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:separator {"style":{"color":{"background":"#e5e7eb"}}} --><hr class="wp-block-separator" style="background-color:#e5e7eb;border-color:#e5e7eb"/><!-- /wp:separator -->

        <!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"14px","lineHeight":"1.6"},"color":{"text":"#9ca3af"}}} -->
        <p class="has-text-align-center" style="color:#9ca3af;font-size:14px;line-height:1.6">You can still visit us anytime at <a href="{{site_url}}" style="color:#6366f1">{{site_name}}</a>.</p>
        <!-- /wp:paragraph -->

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px"},"color":{"text":"#d1d5db"}}} -->
        <p class="has-text-align-center" style="color:#d1d5db;font-size:13px">© {{year}} {{site_name}} · {{site_address}}</p>
        <!-- /wp:paragraph -->

    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->
BLOCKS;
    }
}
