# فهرست کنترل‌های پنل — ممیزی ایستای قابل بازتولید

157 کنترل در 18 بخش. این جدول صرفاً یافتن مصرف‌کننده در سورس است، نه تأیید کارکرد همه گزینه‌ها در وردپرس.

گزینه‌های فاقد مرجع، نیازمند اتصال/بررسی جداگانه‌اند. مسیرهای پویا دستی مشخص شده‌اند؛ ممکن است ارجاع متنی در شرط یا سازنده کلید باشد، نه خروجی فعال.

| بخش | شناسه | نوع | شواهد مصرف‌کننده |
|---|---|---|---|
| general_branding | `demo_design` | select | `zarincode/inc/Bootstrap/Theme.php` |
| general_branding | `brand_name` | text | `zarincode/footer.php`<br>`zarincode/header.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php`<br>`zarincode-core/includes/Widgets/Native_Design.php`<br>`zarincode-core/includes/Widgets/Original_Design.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| general_branding | `brand_en_name` | text | `zarincode/header.php`<br>`zarincode/inc/Core/Article_Ratings.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php`<br>`zarincode-core/includes/Widgets/Original_Design.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| general_branding | `brand_slogan` | text | `zarincode/inc/Core/Seo_Meta.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| general_branding | `developer_name` | text | `zarincode/footer.php`<br>`zarincode/header.php`<br>`zarincode/single-product.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| general_branding | `official_website` | text | `zarincode/footer.php`<br>`zarincode/header.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| general_branding | `logo_monogram` | text | `zarincode/footer.php`<br>`zarincode/header.php`<br>`zarincode/inc/Bootstrap/Theme.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php` |
| general_branding | `logo_light` | media | `zarincode/header.php` |
| general_branding | `logo_dark` | media | `zarincode/footer.php` |
| general_branding | `favicon_url` | media | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| general_branding | `logo_width` | number | `zarincode/header.php`<br>`zarincode/inc/Core/Settings_Security.php` |
| general_branding | `layout_width_desktop` | number | `zarincode/inc/Core/Layout.php` |
| general_branding | `layout_width_tablet` | number | `zarincode/inc/Core/Layout.php` |
| general_branding | `layout_width_mobile` | number | `zarincode/inc/Core/Layout.php` |
| general_branding | `container_max_width` | select | `zarincode/inc/Core/Layout.php` |
| general_branding | `layout_width_scale` | select | `zarincode/inc/Core/Layout.php` |
| general_branding | `bento_border_radius` | select | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| general_branding | `enable_preloader` | switch | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| general_branding | `preloader_text` | text | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| general_branding | `enable_scroll_progress` | switch | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| general_branding | `enable_back_to_top` | switch | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| crextio_colors | `default_color_preset` | select | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| crextio_colors | `accent_color` | color | `zarincode/inc/Core/Assets.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| crextio_colors | `accent_hover_color` | color | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| crextio_colors | `accent_contrast_color` | color | `zarincode/inc/Core/Assets.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| crextio_colors | `dark_surface_color` | color | `zarincode/inc/Core/Assets.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| crextio_colors | `bg_gradient_from` | color | `zarincode/inc/Core/Assets.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| crextio_colors | `bg_gradient_to` | color | `zarincode/inc/Core/Assets.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| crextio_colors | `dark_bg_from` | color | `zarincode/inc/Core/Assets.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| crextio_colors | `dark_bg_to` | color | `zarincode/inc/Core/Assets.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| crextio_colors | `default_theme_mode` | select | `zarincode/header.php`<br>`zarincode/inc/Core/Assets.php` |
| crextio_colors | `glass_blur_px` | number | `zarincode/inc/Core/Settings_Security.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| crextio_colors | `enable_color_switcher` | switch | `zarincode/inc/Core/Assets.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php` |
| typography_settings | `primary_font` | select | `zarincode-core/includes/Demo/Demo_Seeder.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| typography_settings | `heading_font` | select | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| typography_settings | `body_font_size` | number | `zarincode/inc/Core/Settings_Security.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| typography_settings | `body_line_height` | text | `zarincode/inc/Core/Settings_Security.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| typography_settings | `heading_weight` | select | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| typography_settings | `persian_digits` | switch | `zarincode/inc/Bootstrap/Theme.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php` |
| topbar_settings | `enable_topbar` | switch | `zarincode/header.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php` |
| topbar_settings | `topbar_show_dev` | switch | `zarincode/header.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php` |
| topbar_settings | `topbar_show_phone` | switch | `zarincode/header.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php` |
| topbar_settings | `topbar_show_legal_links` | switch | `zarincode/header.php` |
| topbar_settings | `topbar_custom_badge` | text | `zarincode/header.php` |
| topbar_settings | `topbar_bg_color` | color | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| header_navigation | `header_source` | select | `پویا: Bootstrap/Theme.php با type + _source` |
| header_navigation | `enable_sticky_header` | switch | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| header_navigation | `header_show_search` | switch | `zarincode/header.php`<br>`zarincode-core/includes/Widgets/Editable_Renderer.php` |
| header_navigation | `header_show_dark_toggle` | switch | `zarincode/header.php` |
| header_navigation | `header_account_enabled` | switch | `zarincode/inc/Core/Account.php` |
| header_navigation | `header_account_url` | text | `zarincode/inc/Core/Account.php` |
| header_navigation | `header_account_label_display` | select | `zarincode/inc/Core/Account.php` |
| header_navigation | `header_account_text` | text | `zarincode/inc/Core/Account.php` |
| header_navigation | `header_account_logged_text` | text | `zarincode/inc/Core/Account.php` |
| header_navigation | `header_show_cart` | switch | `zarincode/header.php`<br>`zarincode-core/includes/Widgets/Editable_Renderer.php` |
| header_navigation | `header_show_cta` | switch | `zarincode/header.php` |
| header_navigation | `header_cta_text` | text | `zarincode/header.php`<br>`zarincode-core/includes/Widgets/Original_Design.php` |
| header_navigation | `header_cta_action` | select | `zarincode/header.php` |
| header_navigation | `header_cta_custom_url` | text | `zarincode/header.php` |
| hero_homepage | `hero_badge` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| hero_homepage | `hero_title_part1` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| hero_homepage | `hero_title_highlight` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| hero_homepage | `hero_subtitle` | textarea | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| hero_homepage | `hero_btn_primary_text` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| hero_homepage | `hero_btn_secondary_text` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| hero_homepage | `hero_stat_projects` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| hero_homepage | `hero_stat_satisfaction` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| services_department | `services_sec_badge` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| services_department | `services_sec_title` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| services_department | `services_grid_columns` | select | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| services_department | `service_single_show_sla` | switch | `zarincode/single-zc_service.php` |
| services_department | `service_single_show_form` | switch | `zarincode/single-zc_service.php` |
| services_department | `service_single_show_related` | switch | `zarincode/inc/Core/Single_Experience.php` |
| services_department | `service_default_prepayment` | text | `zarincode/single-zc_service.php` |
| services_department | `service_default_warranty` | text | `zarincode/single-zc_service.php` |
| portfolio_projects | `portfolio_sec_title` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| portfolio_projects | `portfolio_enable_filter` | switch | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| portfolio_projects | `portfolio_per_page` | number | `zarincode/inc/Core/Settings_Security.php` |
| portfolio_projects | `portfolio_show_client` | switch | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| portfolio_projects | `portfolio_show_metrics` | switch | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| pricing_plans | `pricing_sec_title` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| pricing_plans | `pricing_discount_badge` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| pricing_plans | `pricing_enable_modal` | switch | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| pricing_plans | `pricing_currency_label` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| woocommerce_store | `shop_hero_title` | text | `zarincode/woocommerce.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php` |
| woocommerce_store | `shop_hero_subtitle` | textarea | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| woocommerce_store | `product_warranty_badge` | text | `zarincode-core/includes/Demo/Demo_Seeder.php` |
| woocommerce_store | `shop_columns` | select | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| woocommerce_store | `shop_per_page` | number | `zarincode/inc/Core/Settings_Security.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| woocommerce_store | `shop_show_instant_badge` | switch | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| woocommerce_store | `shop_show_source_badge` | switch | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| blog_settings | `blog_sec_title` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| blog_settings | `blog_grid_columns` | select | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| blog_settings | `blog_show_reading_time` | switch | `zarincode/single.php` |
| blog_settings | `blog_show_author_box` | switch | `zarincode/inc/Core/Single_Experience.php` |
| footer_bento | `footer_source` | select | `پویا: Bootstrap/Theme.php با type + _source` |
| footer_bento | `footer_show_cta_strip` | switch | `zarincode/footer.php` |
| footer_bento | `footer_cta_heading` | text | `zarincode/footer.php`<br>`zarincode-core/includes/Widgets/Original_Design.php` |
| footer_bento | `footer_about_text` | textarea | `zarincode/footer.php` |
| footer_bento | `footer_enamad_html` | textarea | `zarincode/footer.php`<br>`zarincode/inc/Bootstrap/Theme.php`<br>`zarincode/inc/Core/Settings_Security.php` |
| footer_bento | `footer_samandehi_html` | textarea | `zarincode/footer.php`<br>`zarincode/inc/Bootstrap/Theme.php`<br>`zarincode/inc/Core/Settings_Security.php` |
| footer_bento | `footer_zarinpal_html` | textarea | `zarincode/footer.php`<br>`zarincode/inc/Bootstrap/Theme.php`<br>`zarincode/inc/Core/Settings_Security.php` |
| footer_bento | `footer_gateway_provider` | select | `zarincode/footer.php` |
| footer_bento | `footer_gateway_html` | textarea | `zarincode/footer.php`<br>`zarincode/inc/Bootstrap/Theme.php`<br>`zarincode/inc/Core/Settings_Security.php` |
| footer_bento | `footer_copyright_text` | textarea | `zarincode/footer.php` |
| article_experience | `blog_sidebar_layout` | select | `zarincode/inc/Core/Article_Layout.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php` |
| article_experience | `blog_article_ratings` | switch | `zarincode/inc/Core/Article_Ratings.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php`<br>`zarincode-core/includes/Features/Registry.php` |
| article_experience | `blog_toc_enabled` | switch | `zarincode/inc/Core/Article_Toc.php` |
| article_experience | `blog_toc_columns` | select | `zarincode/inc/Core/Article_Toc.php` |
| contact_socials | `contact_phone` | text | `zarincode/footer.php`<br>`zarincode/header.php`<br>`zarincode/single-product.php`<br>`zarincode/woocommerce.php`<br>`zarincode/inc/Core/Social_Links.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php`<br>`zarincode-core/includes/Widgets/Original_Design.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| contact_socials | `contact_email` | text | `zarincode-core/includes/Demo/Demo_Seeder.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| contact_socials | `contact_address` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| contact_socials | `contact_working_hours` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| contact_socials | `social_github` | text | `zarincode-core/includes/Demo/Demo_Seeder.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| contact_socials | `social_telegram` | text | `پویا: Core/Social_Links.php با social_ + نام شبکه` |
| contact_socials | `social_whatsapp` | text | `zarincode-core/includes/Demo/Demo_Seeder.php` |
| contact_socials | `social_instagram` | text | `پویا: Core/Social_Links.php با social_ + نام شبکه` |
| contact_socials | `social_eitaa` | text | `پویا: Core/Social_Links.php با social_ + نام شبکه` |
| contact_socials | `social_bale` | text | `پویا: Core/Social_Links.php با social_ + نام شبکه` |
| contact_socials | `social_rubika` | text | `پویا: Core/Social_Links.php با social_ + نام شبکه` |
| contact_socials | `social_soroush` | text | `پویا: Core/Social_Links.php با social_ + نام شبکه` |
| contact_socials | `social_gap` | text | `پویا: Core/Social_Links.php با social_ + نام شبکه` |
| contact_socials | `social_igap` | text | `پویا: Core/Social_Links.php با social_ + نام شبکه` |
| contact_socials | `social_shad` | text | `پویا: Core/Social_Links.php با social_ + نام شبکه` |
| contact_socials | `social_x` | text | `پویا: Core/Social_Links.php با social_ + نام شبکه` |
| contact_socials | `social_facebook` | text | `پویا: Core/Social_Links.php با social_ + نام شبکه` |
| contact_socials | `social_linkedin` | text | `پویا: Core/Social_Links.php با social_ + نام شبکه` |
| contact_socials | `floating_contact_action` | select | `zarincode/inc/Core/Social_Links.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php` |
| contact_socials | `floating_contact_url` | text | `zarincode/inc/Core/Social_Links.php` |
| contact_socials | `enable_floating_contact` | switch | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| contact_socials | `floating_contact_label` | text | `zarincode/inc/Core/Social_Links.php`<br>`zarincode-core/includes/Demo/Demo_Seeder.php` |
| modals_forms | `consult_modal_title` | text | `zarincode/footer.php` |
| modals_forms | `consult_modal_desc` | textarea | `zarincode/footer.php` |
| modals_forms | `forms_recipient_email` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| modals_forms | `forms_success_message` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| legal_compliance | `legal_dpo_name` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| legal_compliance | `legal_phone` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| legal_compliance | `legal_jurisdiction` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| legal_compliance | `legal_warranty_days` | text | **مصرف‌کننده مستقیم یافت نشد — تأیید نشده** |
| legal_compliance | `enable_gdpr_cookie_bar` | switch | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| legal_compliance | `gdpr_cookie_text` | text | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| performance_advanced | `enable_schema_markup` | switch | `zarincode/inc/Core/Article_Ratings.php`<br>`zarincode/inc/Core/Seo_Meta.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| performance_advanced | `optimize_wp_head` | switch | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| performance_advanced | `enable_maintenance_mode` | switch | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| performance_advanced | `maintenance_title` | text | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| performance_advanced | `maintenance_message` | textarea | `zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| performance_advanced | `custom_css` | code | `zarincode/inc/Core/Settings_Security.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| performance_advanced | `custom_head_js` | code | `zarincode/inc/Core/Settings_Security.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| performance_advanced | `custom_footer_js` | code | `zarincode/inc/Core/Settings_Security.php`<br>`zarincode/inc/Redux/OptionsConfig.php (runtime)` |
| feature_modules | `feature_product_policies` | switch | `zarincode-core/includes/Features/Registry.php` |
| feature_modules | `feature_threaded_discussion` | switch | `zarincode-core/includes/Features/Registry.php` |
| feature_modules | `feature_social_profiles` | switch | `zarincode-core/includes/Features/Registry.php` |
| feature_modules | `feature_public_dynamic_tags` | switch | `zarincode-core/includes/Features/Registry.php` |
| feature_modules | `feature_official_widget_styles` | switch | `zarincode/inc/Compatibility/Elementor_Styles.php`<br>`zarincode-core/includes/Features/Registry.php` |
| feature_modules | `feature_native_widgets` | switch | `پویا: Features/Manager.php و Registry.php؛ وابسته به قابلیت/افزونه` |
| feature_modules | `feature_studio_widgets` | switch | `پویا: Features/Manager.php و Registry.php؛ وابسته به قابلیت/افزونه` |
| feature_modules | `feature_professional_widgets` | switch | `پویا: Features/Manager.php و Registry.php؛ وابسته به قابلیت/افزونه` |

## کنترل‌های نیازمند بررسی/اتصال بیشتر

- `hero_badge`
- `hero_title_part1`
- `hero_title_highlight`
- `hero_subtitle`
- `hero_btn_primary_text`
- `hero_btn_secondary_text`
- `hero_stat_projects`
- `hero_stat_satisfaction`
- `services_sec_badge`
- `services_sec_title`
- `portfolio_sec_title`
- `portfolio_enable_filter`
- `portfolio_show_client`
- `portfolio_show_metrics`
- `pricing_sec_title`
- `pricing_discount_badge`
- `pricing_enable_modal`
- `pricing_currency_label`
- `shop_hero_subtitle`
- `shop_show_instant_badge`
- `shop_show_source_badge`
- `blog_sec_title`
- `contact_address`
- `contact_working_hours`
- `forms_recipient_email`
- `forms_success_message`
- `legal_dpo_name`
- `legal_phone`
- `legal_jurisdiction`
- `legal_warranty_days`

این موارد با این ممیزی کامل‌شده اعلام نمی‌شوند. مخصوصاً گزینه‌های هیرو/مقالات/تعرفه/فرم و صفحات حقوقی باید با کنترل مستقل ویجت‌ها و داده‌های ذخیره‌شده هماهنگ و در staging آزموده شوند.
