<?php
/**
 * Custom Post Types, Taxonomies & Admin Meta Boxes Manager
 * (Services, Portfolio Projects, WooCommerce Product Live Demo Meta, and Elementor Header/Footer/MegaMenu Builder)
 *
 * @package Zarincode\Core\PostTypes
 * @author  Ehsan NaderiPanah (09024561001 | https://zarincode.com)
 */

namespace Zarincode\Core\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CPT_Manager {

    private static ?CPT_Manager $instance = null;

    public static function instance(): CPT_Manager {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', [ $this, 'register_post_types_and_taxonomies' ], 5 );
        add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_meta_boxes' ], 10, 2 );
        add_action( 'init', [ $this, 'enable_elementor_cpt_support' ], 20 );

        // Native WooCommerce Product Data panel integration for Live Demo URL
        add_action( 'woocommerce_product_options_general_product_data', [ $this, 'render_wc_general_demo_fields' ] );
        add_action( 'woocommerce_process_product_meta', [ $this, 'save_wc_general_demo_fields' ] );
    }

    /**
     * Alias method so both register_cpts_and_taxonomies() and register_post_types_and_taxonomies() work seamlessly.
     */
    public function register_cpts_and_taxonomies(): void {
        $this->register_post_types_and_taxonomies();
    }

    public function register_post_types_and_taxonomies(): void {
        // 1. Services CPT (خدمات تخصصی زرین کد)
        register_post_type(
            'zc_service',
            [
                'labels'              => [
                    'name'               => 'خدمات زرین کد',
                    'singular_name'      => 'خدمت تخصصی',
                    'menu_name'          => 'خدمات زرین کد',
                    'name_admin_bar'     => 'خدمت زرین کد',
                    'add_new'            => 'افزودن خدمت جدید',
                    'add_new_item'       => 'افزودن خدمت تخصصی جدید',
                    'edit_item'          => 'ویرایش خدمت',
                    'new_item'           => 'خدمت جدید',
                    'view_item'          => 'مشاهده خدمت',
                    'search_items'       => 'جستجوی خدمات',
                    'not_found'          => 'خدمتی یافت نشد — از منوی «درون‌ریز دمو زرین کد» خدمات پیش‌فرض را درون‌ریزی کنید.',
                    'all_items'          => 'همه خدمات (۶ خدمت)',
                ],
                'public'              => true,
                'has_archive'         => 'services-archive',
                'publicly_queryable'  => true,
                'show_ui'             => true,
                'show_in_menu'        => true,
                'menu_position'       => 21,
                'menu_icon'           => 'dashicons-awards',
                'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments', 'elementor' ],
                'show_in_rest'        => true,
                'rewrite'             => [ 'slug' => 'service', 'with_front' => false ],
            ]
        );

        register_taxonomy(
            'zc_service_cat',
            [ 'zc_service' ],
            [
                'labels'            => [
                    'name'          => 'دسته‌بندی خدمات',
                    'singular_name' => 'دسته خدمت',
                ],
                'hierarchical'      => true,
                'public'            => true,
                'show_ui'           => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'rewrite'           => [ 'slug' => 'service-category' ],
            ]
        );

        // 2. Portfolio / Projects CPT (نمونه‌کارها و پروژه‌ها)
        register_post_type(
            'zc_project',
            [
                'labels'              => [
                    'name'               => 'نمونه‌کارهای زرین کد',
                    'singular_name'      => 'نمونه‌کار',
                    'menu_name'          => 'نمونه‌کارها (پروژه‌ها)',
                    'add_new'            => 'افزودن نمونه‌کار',
                    'add_new_item'       => 'افزودن نمونه‌کار جدید',
                    'edit_item'          => 'ویرایش نمونه‌کار',
                    'view_item'          => 'مشاهده نمونه‌کار',
                    'all_items'          => 'همه نمونه‌کارها',
                    'not_found'          => 'نمونه‌کاری یافت نشد — از منوی «درون‌ریز دمو زرین کد» نمونه‌کارها را درون‌ریزی کنید.',
                ],
                'public'              => true,
                'has_archive'         => 'projects-archive',
                'publicly_queryable'  => true,
                'show_ui'             => true,
                'show_in_menu'        => true,
                'menu_position'       => 22,
                'menu_icon'           => 'dashicons-portfolio',
                'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments', 'elementor' ],
                'show_in_rest'        => true,
                'rewrite'             => [ 'slug' => 'project', 'with_front' => false ],
            ]
        );

        register_taxonomy(
            'zc_project_cat',
            [ 'zc_project' ],
            [
                'labels'            => [
                    'name'          => 'دسته‌بندی نمونه‌کارها',
                    'singular_name' => 'دسته نمونه‌کار',
                ],
                'hierarchical'      => true,
                'public'            => true,
                'show_ui'           => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'rewrite'           => [ 'slug' => 'project-category' ],
            ]
        );

        // 3. Header, Footer & MegaMenu Builder CPT (هدر و فوترساز المنتور)
        register_post_type(
            'zc_hf_template',
            [
                'labels'              => [
                    'name'               => 'هدر و فوترساز المنتور',
                    'singular_name'      => 'قالب هدر/فوتر',
                    'menu_name'          => 'هدر و فوترساز زرین کد',
                    'add_new'            => 'ساخت هدر/فوتر جدید',
                    'add_new_item'       => 'ساخت هدر، فوتر یا مگامنوی المنتوری',
                    'edit_item'          => 'ویرایش قالب هدر/فوتر',
                    'all_items'          => 'همه هدرها و فوترها',
                ],
                'public'              => true,
                'has_archive'         => false,
                'publicly_queryable'  => true,
                'show_ui'             => true,
                'show_in_menu'        => true,
                'menu_position'       => 23,
                'menu_icon'           => 'dashicons-layout',
                'supports'            => [ 'title', 'editor', 'elementor', 'custom-fields' ],
                'show_in_rest'        => true,
            ]
        );

        // 4. Fallback Digital Product CPT when WooCommerce is not yet activated
        if ( ! class_exists( 'WooCommerce' ) && ! post_type_exists( 'product' ) ) {
            register_post_type(
                'product',
                [
                    'labels'              => [
                        'name'          => 'محصولات آماده فروشگاه',
                        'singular_name' => 'محصول دیجیتال',
                        'menu_name'     => 'محصولات فروشگاه',
                        'add_new'       => 'افزودن محصول جدید',
                        'add_new_item'  => 'افزودن محصول دیجیتال جدید',
                        'edit_item'     => 'ویرایش محصول',
                        'all_items'     => 'همه محصولات آماده',
                    ],
                    'public'              => true,
                    'has_archive'         => 'products-catalog',
                    'publicly_queryable'  => true,
                    'show_ui'             => true,
                    'show_in_menu'        => true,
                    'menu_position'       => 24,
                    'menu_icon'           => 'dashicons-cart',
                    'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments', 'elementor' ],
                    'show_in_rest'        => true,
                    'rewrite'             => [ 'slug' => 'product', 'with_front' => false ],
                ]
            );
        }
    }

    public function enable_elementor_cpt_support(): void {
        $cpt_support = get_option( 'elementor_cpt_support', [ 'page', 'post' ] );
        if ( ! is_array( $cpt_support ) ) {
            $cpt_support = [ 'page', 'post' ];
        }
        $needed = [ 'page', 'post', 'zc_service', 'zc_project', 'zc_hf_template', 'product' ];
        $updated = false;
        foreach ( $needed as $pt ) {
            if ( ! in_array( $pt, $cpt_support, true ) ) {
                $cpt_support[] = $pt;
                $updated = true;
            }
        }
        if ( $updated ) {
            update_option( 'elementor_cpt_support', $cpt_support );
        }
    }

    public function register_meta_boxes(): void {
        add_meta_box(
            'zc_product_meta_box',
            '🌟 لینک پیش‌نمایش آنلاین دمو و مشخصات مارکت‌پلیس زرین کد (Live Demo & Product Meta)',
            [ $this, 'render_product_meta_box' ],
            'product',
            'normal',
            'high'
        );

        add_meta_box(
            'zc_service_meta_box',
            'تنظیمات و شرایط ارائه خدمت (Webinex Service Conditions)',
            [ $this, 'render_service_meta_box' ],
            'zc_service',
            'normal',
            'high'
        );

        add_meta_box(
            'zc_project_meta_box',
            'مشخصات فنی و اجرایی نمونه‌کار (Portfolio Meta)',
            [ $this, 'render_project_meta_box' ],
            'zc_project',
            'normal',
            'high'
        );

        add_meta_box(
            'zc_hf_meta_box',
            'تنظیمات جایگاه هدر / فوتر / مگامنو',
            [ $this, 'render_hf_meta_box' ],
            'zc_hf_template',
            'side',
            'high'
        );
    }

    /**
     * Render WooCommerce Product Meta Box (with prominent Live Demo Link field)
     */
    public function render_product_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'zc_meta_save', 'zc_meta_nonce' );
        $demo_url   = get_post_meta( $post->ID, '_zc_prod_demo_url', true );
        if ( '' === $demo_url ) {
            $demo_url = 'https://zarincode.com/?demo=' . ( $post->post_name ?: 'zarincode-pro' );
        }
        $demo_label = get_post_meta( $post->ID, '_zc_prod_demo_label', true ) ?: 'پیش‌نمایش آنلاین دمو';
        $fields = [
            '_zc_prod_ver'       => [ 'نسخه فعلی محصول (Version)', 'v2.5.0' ],
            '_zc_prod_cat_badge' => [ 'برچسب دسته‌بندی محصول', 'قالب وردپرس' ],
            '_zc_prod_sales'     => [ 'آمار فروش و نصب فعال', '+۴۲۰ فروش موفق' ],
            '_zc_prod_old_price' => [ 'قیمت قبل از تخفیف (نمایشی)', '۳,۹۰۰,۰۰۰' ],
            '_zc_prod_icon'      => [ 'کلاس آیکون FontAwesome', 'fa-brands fa-wordpress' ],
            '_zc_prod_tags'      => [ 'تگ‌های تکنولوژی (جداشده با کاما)', 'Elementor,Redux,WooCommerce,PHP 8.4,RTL Ready' ],
        ];
        ?>
        <div style="direction:rtl;font-family:Tahoma,system-ui,sans-serif;padding:8px 4px;">
            <div style="background:#FFFBEB;border:2px solid #FDD05B;border-radius:14px;padding:16px;margin-bottom:16px;">
                <div style="font-weight:900;font-size:14px;color:#2A2C31;margin-bottom:10px;">
                    🖥️ تنظیمات دکمه «پیش‌نمایش آنلاین دمو (Live Demo)» محصول
                </div>
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:14px;">
                    <div>
                        <label for="_zc_prod_demo_url" style="display:block;font-weight:bold;margin-bottom:6px;color:#1F2937;">
                            لینک پیش‌نمایش آنلاین دمو (Live Demo URL):
                        </label>
                        <input type="url" id="_zc_prod_demo_url" name="_zc_prod_demo_url" value="<?php echo esc_attr( $demo_url ); ?>" placeholder="https://demo.zarincode.com/product-name" dir="ltr" style="width:100%;padding:8px 12px;border-radius:8px;border:1px solid #D1D5DB;text-align:left;font-family:monospace;">
                        <small style="color:#6B7280;display:block;margin-top:4px;">این لینک به دکمه «پیش‌نمایش آنلاین دمو» در کارت محصول و صفحه تکی محصول متصل می‌شود.</small>
                    </div>
                    <div>
                        <label for="_zc_prod_demo_label" style="display:block;font-weight:bold;margin-bottom:6px;color:#1F2937;">
                            متن دکمه نمایش دمو:
                        </label>
                        <input type="text" id="_zc_prod_demo_label" name="_zc_prod_demo_label" value="<?php echo esc_attr( $demo_label ); ?>" placeholder="پیش‌نمایش آنلاین دمو" style="width:100%;padding:8px 12px;border-radius:8px;border:1px solid #D1D5DB;">
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <?php foreach ( $fields as $key => $info ) :
                    $val = get_post_meta( $post->ID, $key, true );
                    if ( '' === $val ) {
                        $val = $info[1];
                    }
                    ?>
                    <div>
                        <label style="display:block;font-weight:bold;margin-bottom:4px;"><?php echo esc_html( $info[0] ); ?></label>
                        <input type="text" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $val ); ?>" style="width:100%;padding:7px 10px;border-radius:8px;border:1px solid #D1D5DB;">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Also add Live Demo URL field inside WooCommerce's native "General" product data tab
     */
    public function render_wc_general_demo_fields(): void {
        if ( ! function_exists( 'woocommerce_wp_text_input' ) ) {
            return;
        }
        echo '<div class="options_group">';
        woocommerce_wp_text_input( [
            'id'          => '_zc_prod_demo_url',
            'label'       => 'لینک پیش‌نمایش دمو (Live Demo URL)',
            'placeholder' => 'https://zarincode.com/?demo=product-slug',
            'desc_tip'    => true,
            'description' => 'لینک دمو آنلاین این محصول که در صفحه فروشگاه و صفحه تکی محصول روی دکمه «پیش‌نمایش آنلاین دمو» قرار می‌گیرد.',
        ] );
        woocommerce_wp_text_input( [
            'id'          => '_zc_prod_demo_label',
            'label'       => 'متن دکمه دمو',
            'placeholder' => 'پیش‌نمایش آنلاین دمو',
            'desc_tip'    => true,
            'description' => 'عنوان نمایشی روی دکمه پیش‌نمایش آنلاین دمو.',
        ] );
        echo '</div>';
    }

    public function save_wc_general_demo_fields( int $post_id ): void {
        if ( isset( $_POST['_zc_prod_demo_url'] ) ) {
            update_post_meta( $post_id, '_zc_prod_demo_url', esc_url_raw( wp_unslash( $_POST['_zc_prod_demo_url'] ) ) );
        }
        if ( isset( $_POST['_zc_prod_demo_label'] ) ) {
            update_post_meta( $post_id, '_zc_prod_demo_label', sanitize_text_field( wp_unslash( $_POST['_zc_prod_demo_label'] ) ) );
        }
    }

    public function render_service_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'zc_meta_save', 'zc_meta_nonce' );
        $fields = [
            '_zc_srv_en_title'          => [ 'عنوان انگلیسی خدمت', 'Website Design' ],
            '_zc_srv_icon'              => [ 'کلاس آیکون FontAwesome', 'fa-solid fa-laptop-code' ],
            '_zc_srv_price_range'       => [ 'بازه قیمتی پس از تخفیف', '۱۰ تا ۵۵ میلیون تومان' ],
            '_zc_srv_old_price'         => [ 'بازه قیمتی قبل از تخفیف', '۲۰ تا ۱۱۰ میلیون' ],
            '_zc_srv_discount'          => [ 'درصد تخفیف', '50%' ],
            '_zc_srv_delivery_mode'     => [ 'نحوه انجام خدمت', 'دورکاری و قرارداد رسمی' ],
            '_zc_srv_refund_guarantee'  => [ 'گارانتی بازگشت وجه', 'دارد (۱۰۰٪ تضمینی)' ],
            '_zc_srv_quality_guarantee' => [ 'ضمانت کیفیت', 'دارد' ],
            '_zc_srv_prepayment'        => [ 'پیش‌پرداخت', '50%' ],
            '_zc_srv_duration'          => [ 'مدت زمان اجرا', '۲۰ تا ۴۵ روز کاری' ],
            '_zc_srv_reports_count'     => [ 'ارائه گزارش از کار', '۵ بار در روند پروژه' ],
            '_zc_srv_revisions'         => [ 'اصلاح و بازبینی رایگان', '۳ بار پس از تحویل' ],
            '_zc_srv_final_delivery'    => [ 'نحوه تحویل نهایی', 'ارائه فایل سورس و اطلاعات کامل سایت' ],
        ];
        echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;direction:rtl;">';
        foreach ( $fields as $key => $info ) {
            $val = get_post_meta( $post->ID, $key, true );
            if ( '' === $val ) {
                $val = $info[1];
            }
            echo '<div><label style="display:block;font-weight:bold;margin-bottom:4px;">' . esc_html( $info[0] ) . '</label>';
            echo '<input type="text" name="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '" style="width:100%;"></div>';
        }
        echo '</div>';
    }

    public function render_project_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'zc_meta_save', 'zc_meta_nonce' );
        $fields = [
            '_zc_prj_client'   => [ 'نام برند / کارفرما', 'پلتفرم دیجیتال پلاس' ],
            '_zc_prj_agency'   => [ 'مجری پروژه', 'زرین کد (احسان نادری پناه)' ],
            '_zc_prj_date'     => [ 'تاریخ اجرا (شمسی)', 'فروردین ۱۴۰۵' ],
            '_zc_prj_duration' => [ 'مدت زمان اجرا', '۲۵ روز کاری' ],
            '_zc_prj_cms'      => [ 'تکنولوژی / CMS', 'وردپرس + المنتور اختصاصی' ],
            '_zc_prj_plan'     => [ 'پلن اجرایی', 'پلن الماسی + خدمات سئو' ],
            '_zc_prj_metrics'  => [ 'دستاورد و نتیجه شاخص', '+۳۲۰٪ رشد فروش آنلاین' ],
            '_zc_prj_url'      => [ 'آدرس وب‌سایت نمونه‌کار', 'https://zarincode.com' ],
        ];
        echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;direction:rtl;">';
        foreach ( $fields as $key => $info ) {
            $val = get_post_meta( $post->ID, $key, true );
            if ( '' === $val ) {
                $val = $info[1];
            }
            echo '<div><label style="display:block;font-weight:bold;margin-bottom:4px;">' . esc_html( $info[0] ) . '</label>';
            echo '<input type="text" name="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '" style="width:100%;"></div>';
        }
        echo '</div>';
    }

    public function render_hf_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'zc_meta_save', 'zc_meta_nonce' );
        $type   = get_post_meta( $post->ID, '_zc_hf_type', true ) ?: 'header';
        $active = get_post_meta( $post->ID, '_zc_hf_active', true ) ?: 'yes';
        ?>
        <p>
            <label style="font-weight:bold;display:block;margin-bottom:4px;">نوع قالب المنتوری:</label>
            <select name="_zc_hf_type" style="width:100%;">
                <option value="header" <?php selected( $type, 'header' ); ?>>هدر اصلی سایت (Header)</option>
                <option value="footer" <?php selected( $type, 'footer' ); ?>>فوتر اصلی سایت (Footer)</option>
                <option value="megamenu" <?php selected( $type, 'megamenu' ); ?>>مگامنوی خدمات و محصولات</option>
                <option value="popup" <?php selected( $type, 'popup' ); ?>>پاپ‌آپ درخواست مشاوره</option>
            </select>
        </p>
        <p>
            <label style="font-weight:bold;display:block;margin-bottom:4px;">وضعیت نمایش در کل سایت:</label>
            <select name="_zc_hf_active" style="width:100%;">
                <option value="yes" <?php selected( $active, 'yes' ); ?>>فعال در کل سایت</option>
                <option value="no" <?php selected( $active, 'no' ); ?>>غیرفعال (پیش‌نویس)</option>
            </select>
        </p>
        <?php
    }

    public function save_meta_boxes( int $post_id, \WP_Post $post ): void {
        if ( ! isset( $_POST['zc_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zc_meta_nonce'] ) ), 'zc_meta_save' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['_zc_prod_demo_url'] ) ) {
            update_post_meta( $post_id, '_zc_prod_demo_url', esc_url_raw( wp_unslash( $_POST['_zc_prod_demo_url'] ) ) );
        }

        $meta_keys = [
            '_zc_prod_demo_label', '_zc_prod_ver', '_zc_prod_cat_badge',
            '_zc_prod_sales', '_zc_prod_old_price', '_zc_prod_icon', '_zc_prod_tags',
            '_zc_srv_en_title', '_zc_srv_icon', '_zc_srv_price_range', '_zc_srv_old_price',
            '_zc_srv_discount', '_zc_srv_delivery_mode', '_zc_srv_refund_guarantee',
            '_zc_srv_quality_guarantee', '_zc_srv_prepayment', '_zc_srv_duration',
            '_zc_srv_reports_count', '_zc_srv_revisions', '_zc_srv_final_delivery',
            '_zc_prj_client', '_zc_prj_agency', '_zc_prj_date', '_zc_prj_duration',
            '_zc_prj_cms', '_zc_prj_plan', '_zc_prj_metrics', '_zc_prj_url',
            '_zc_hf_type', '_zc_hf_active',
        ];

        foreach ( $meta_keys as $key ) {
            if ( isset( $_POST[ $key ] ) ) {
                update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
            }
        }
    }
}
