<?php
/**
 * Complete Required & Recommended Plugin Installer & Manager (TGMPA + Visual Wizard + AJAX Batch Installer)
 *
 * Handles automatic installation, extraction, activation, and updating of required and recommended
 * plugins from bundled theme ZIPs (inc/plugins/), Ehsan NaderiPanah's afzoneha repository, and WordPress.org.
 *
 * @package Zarincode\Theme\Plugins
 * @author  Ehsan NaderiPanah (09024561001 | https://zarincode.com)
 */

namespace Zarincode\Theme\Plugins;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin_Manager {

    private static ?Plugin_Manager $instance = null;

    public static function instance(): Plugin_Manager {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'register_plugin_installer_menus' ], 11 );
        add_action( 'admin_notices', [ $this, 'render_missing_plugins_notice' ] );
        add_action( 'wp_ajax_zarincode_plugin_action', [ $this, 'ajax_handle_plugin_action' ] );
        add_action( 'after_switch_theme', [ $this, 'auto_install_bundled_core_plugins' ], 5 );
    }

    /**
     * Get the complete manifest of Required & Recommended plugins for Zarincode Theme
     *
     * @return array<string, array<string, mixed>>
     */
    public static function get_plugins_manifest(): array {
        $bundled_dir = get_template_directory() . '/inc/plugins/';
        $afzoneha_url = 'https://raw.githubusercontent.com/Ehsan-Np/afzoneha/main/';

        return [
            'zarincode-core' => [
                'name'        => 'هسته و ۱۴ ویجت اختصاصی زرین کد (Zarincode Core)',
                'slug'        => 'zarincode-core',
                'file'        => 'zarincode-core/zarincode-core.php',
                'version'     => '2.5.0',
                'required'    => true,
                'badge'       => 'اجباری — هسته قالب',
                'source_type' => 'Bundled (پکیج داخلی قالب)',
                'local_zip'   => $bundled_dir . 'zarincode-core.zip',
                'remote_zip'  => '',
                'description' => 'اضافه‌کننده ۱۴ ویجت اختصاصی المنتور، سازنده هدر و فوتر، پست‌تایپ‌های خدمات و نمونه‌کارها و درون‌ریز هوشمند دمو.',
                'icon'        => 'dashicons-superhero-alt',
            ],
            'redux-framework' => [
                'name'        => 'فریم‌ورک تنظیمات پیشرفته قالب (Redux Framework)',
                'slug'        => 'redux-framework',
                'file'        => 'redux-framework/redux-framework.php',
                'version'     => '4.4.18',
                'required'    => true,
                'badge'       => 'اجباری — پنل تنظیمات',
                'source_type' => 'Bundled (پکیج داخلی قالب + مخزن afzoneha)',
                'local_zip'   => $bundled_dir . 'redux-framework.zip',
                'remote_zip'  => $afzoneha_url . 'redux-framework.zip',
                'description' => 'موتور قدرتمند پنل تنظیمات قالب برای شخصی‌سازی رنگ‌بندی، تایپوگرافی، هدر، فوتر، فروشگاه و سئو.',
                'icon'        => 'dashicons-admin-generic',
            ],
            'elementor' => [
                'name'        => 'صفحه‌ساز المنتور (Elementor Page Builder)',
                'slug'        => 'elementor',
                'file'        => 'elementor/elementor.php',
                'version'     => '4.3.2',
                'required'    => true,
                'badge'       => 'اجباری — صفحه‌ساز اصلی',
                'source_type' => 'مخزن وردپرس + مخزن گیت‌هاب afzoneha',
                'local_zip'   => file_exists( '/tmp/afzoneha/elementor.4.3.2.zip' ) ? '/tmp/afzoneha/elementor.4.3.2.zip' : $bundled_dir . 'elementor.4.3.2.zip',
                'remote_zip'  => $afzoneha_url . 'elementor.4.3.2.zip',
                'wp_org_zip'  => 'https://downloads.wordpress.org/plugin/elementor.latest-stable.zip',
                'description' => 'صفحه‌ساز بصری پیشرفته جهت ویرایش درگ اند دراپ تمامی صفحات، هدر، فوتر و ۱۴ ویجت اختصاصی زرین کد.',
                'icon'        => 'dashicons-layout',
            ],
            'elementor-pro' => [
                'name'        => 'المنتور پرو (Elementor Pro — نسخه پرمیوم)',
                'slug'        => 'elementor-pro',
                'file'        => 'elementor-pro/elementor-pro.php',
                'version'     => '3.25.0',
                'required'    => true,
                'badge'       => 'اجباری — امکانات پرمیوم',
                'source_type' => 'Bundled (پکیج داخلی قالب + مخزن afzoneha)',
                'local_zip'   => $bundled_dir . 'elementor-pro.zip',
                'remote_zip'  => $afzoneha_url . 'elementor-pro.zip',
                'description' => 'نسخه حرفه‌ای المنتور جهت پشتیبانی کامل از قالب‌های پویا، پاپ‌آپ‌ها، فرم‌ها و ووکامرس بیلدر.',
                'icon'        => 'dashicons-star-filled',
            ],
            'woocommerce' => [
                'name'        => 'فروشگاه‌ساز ووکامرس (WooCommerce)',
                'slug'        => 'woocommerce',
                'file'        => 'woocommerce/woocommerce.php',
                'version'     => '11.2.0',
                'required'    => true,
                'badge'       => 'اجباری — مارکت‌پلیس محصولات',
                'source_type' => 'مخزن وردپرس + مخزن گیت‌هاب afzoneha',
                'local_zip'   => file_exists( '/tmp/afzoneha/woocommerce.11.2.0.zip' ) ? '/tmp/afzoneha/woocommerce.11.2.0.zip' : $bundled_dir . 'woocommerce.11.2.0.zip',
                'remote_zip'  => $afzoneha_url . 'woocommerce.11.2.0.zip',
                'wp_org_zip'  => 'https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip',
                'description' => 'موتور فروشگاه اینترنتی جهت فروش آنلاین قالب وردپرس، افزونه و سورس‌کدهای نرم‌افزاری با دانلود آنی.',
                'icon'        => 'dashicons-cart',
            ],
            'wordpress-seo-premium' => [
                'name'        => 'افزونه سئو پرمیوم یوست (Yoast SEO Premium)',
                'slug'        => 'wordpress-seo-premium',
                'file'        => 'wordpress-seo-premium/wp-seo-premium.php',
                'version'     => '23.0',
                'required'    => false,
                'badge'       => 'پیشنهادی — سئو پیشرفته',
                'source_type' => 'Bundled (پکیج داخلی قالب + مخزن afzoneha)',
                'local_zip'   => $bundled_dir . 'wordpress-seo-premium.zip',
                'remote_zip'  => $afzoneha_url . 'wordpress-seo-premium.zip',
                'description' => 'ابزار تخصصی بهینه‌سازی کلمات کلیدی، اسکیما، ریدایرکت‌ها و نقشه سایت XML برای کسب رتبه اول گوگل.',
                'icon'        => 'dashicons-chart-area',
            ],
            'one-click-demo-import' => [
                'name'        => 'درون‌ریز دمو با یک کلیک (One Click Demo Import)',
                'slug'        => 'one-click-demo-import',
                'file'        => 'one-click-demo-import/one-click-demo-import.php',
                'version'     => '3.2.1',
                'required'    => false,
                'badge'       => 'پیشنهادی — ابزار کمکی دمو',
                'source_type' => 'مخزن رسمی وردپرس (WordPress.org)',
                'local_zip'   => '',
                'remote_zip'  => 'https://downloads.wordpress.org/plugin/one-click-demo-import.latest-stable.zip',
                'description' => 'افزونه کمکی استاندارد مارکت‌های جهانی جهت درون‌ریزی فایل‌های XML و JSON در کنار درون‌ریز بومی زرین کد.',
                'icon'        => 'dashicons-cloud-upload',
            ],
            'wp-parsidate' => [
                'name'        => 'تاریخ شمسی و فارسی‌ساز وردپرس (WP-Parsidate)',
                'slug'        => 'wp-parsidate',
                'file'        => 'wp-parsidate/wp-parsidate.php',
                'version'     => '5.0.0',
                'required'    => false,
                'badge'       => 'پیشنهادی — تقویم جلالی',
                'source_type' => 'مخزن رسمی وردپرس (WordPress.org)',
                'local_zip'   => '',
                'remote_zip'  => 'https://downloads.wordpress.org/plugin/wp-parsidate.latest-stable.zip',
                'description' => 'تبدیل تاریخ میلادی وردپرس و ووکامرس به تقویم شمسی (جلالی) و بهبود یکپارچگی زبان فارسی.',
                'icon'        => 'dashicons-calendar-alt',
            ],
        ];
    }

    /**
     * Determine current state of a plugin: 'active', 'installed', or 'not_installed'
     */
    public static function get_plugin_status( array $plugin ): string {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $installed_plugins = get_plugins();
        $file              = $plugin['file'];

        // Also detect if plugin folder exists with slightly different main file
        if ( ! isset( $installed_plugins[ $file ] ) ) {
            foreach ( $installed_plugins as $installed_path => $pdata ) {
                if ( strpos( $installed_path, $plugin['slug'] . '/' ) === 0 ) {
                    $file = $installed_path;
                    break;
                }
            }
        }

        if ( is_plugin_active( $file ) ) {
            return 'active';
        }
        if ( isset( $installed_plugins[ $file ] ) ) {
            return 'installed';
        }
        return 'not_installed';
    }

    /**
     * Resolve actual plugin main file path if installed
     */
    public static function resolve_plugin_file( array $plugin ): string {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $installed_plugins = get_plugins();
        if ( isset( $installed_plugins[ $plugin['file'] ] ) ) {
            return $plugin['file'];
        }
        foreach ( $installed_plugins as $installed_path => $pdata ) {
            if ( strpos( $installed_path, $plugin['slug'] . '/' ) === 0 ) {
                return $installed_path;
            }
        }
        return $plugin['file'];
    }

    /**
     * Auto-install and activate bundled core plugins on theme switch
     */
    public function auto_install_bundled_core_plugins(): void {
        foreach ( [ 'zarincode-core', 'redux-framework' ] as $slug ) {
            self::install_and_activate_plugin( $slug );
        }
    }

    /**
     * Install and/or activate a plugin by slug
     *
     * @param string $slug   Plugin slug from manifest.
     * @param string $action 'install_activate', 'activate', or 'deactivate'.
     * @return array{success: bool, status: string, message: string}
     */
    public static function install_and_activate_plugin( string $slug, string $action = 'install_activate' ): array {
        $manifest = self::get_plugins_manifest();
        if ( ! isset( $manifest[ $slug ] ) ) {
            return [
                'success' => false,
                'status'  => 'not_installed',
                'message' => 'افزونه مورد نظر در لیست افزونه‌های قالب یافت نشد.',
            ];
        }

        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        $plugin = $manifest[ $slug ];
        $status = self::get_plugin_status( $plugin );

        if ( 'deactivate' === $action ) {
            $file = self::resolve_plugin_file( $plugin );
            deactivate_plugins( $file );
            return [
                'success' => true,
                'status'  => 'installed',
                'message' => sprintf( 'افزونه «%s» غیرفعال شد.', $plugin['name'] ),
            ];
        }

        // If not installed yet, extract from local bundled ZIP or download
        if ( 'not_installed' === $status ) {
            WP_Filesystem();
            $zip_to_extract = '';
            $temp_file      = '';

            if ( ! empty( $plugin['local_zip'] ) && file_exists( $plugin['local_zip'] ) ) {
                $zip_to_extract = $plugin['local_zip'];
            } else {
                $download_urls = array_filter( [
                    $plugin['remote_zip'] ?? '',
                    $plugin['wp_org_zip'] ?? '',
                ] );
                foreach ( $download_urls as $url ) {
                    $downloaded = download_url( $url, 60 );
                    if ( ! is_wp_error( $downloaded ) && file_exists( $downloaded ) ) {
                        $zip_to_extract = $downloaded;
                        $temp_file      = $downloaded;
                        break;
                    }
                }
            }

            if ( empty( $zip_to_extract ) || ! file_exists( $zip_to_extract ) ) {
                return [
                    'success' => false,
                    'status'  => 'not_installed',
                    'message' => sprintf( 'فایل نصبی افزونه «%s» یافت نشد یا دانلود با خطا مواجه شد.', $plugin['name'] ),
                ];
            }

            $unzipped = unzip_file( $zip_to_extract, WP_PLUGIN_DIR );
            if ( $temp_file && file_exists( $temp_file ) ) {
                @unlink( $temp_file );
            }

            if ( is_wp_error( $unzipped ) ) {
                return [
                    'success' => false,
                    'status'  => 'not_installed',
                    'message' => 'خطا در استخراج فایل فشرده افزونه: ' . $unzipped->get_error_message(),
                ];
            }

            wp_clean_plugins_cache( true );
        }

        // Now activate the plugin
        $file      = self::resolve_plugin_file( $plugin );
        $activated = activate_plugin( $file );
        wp_clean_plugins_cache( true );

        if ( is_wp_error( $activated ) ) {
            return [
                'success' => false,
                'status'  => self::get_plugin_status( $plugin ),
                'message' => 'افزونه نصب شد اما در فعال‌سازی خطایی رخ داد: ' . $activated->get_error_message(),
            ];
        }

        return [
            'success' => true,
            'status'  => 'active',
            'message' => sprintf( 'افزونه «%s» با موفقیت نصب و فعال‌سازی شد!', $plugin['name'] ),
        ];
    }

    /**
     * AJAX Handler for Single & Batch 1-Click Plugin Installation
     */
    public function ajax_handle_plugin_action(): void {
        check_ajax_referer( 'zarincode_plugin_manager_nonce', 'nonce' );
        if ( ! current_user_can( 'install_plugins' ) && ! current_user_can( 'activate_plugins' ) ) {
            wp_send_json_error( [ 'message' => 'شما دسترسی لازم برای نصب افزونه‌ها را ندارید.' ] );
        }

        $slug   = sanitize_text_field( wp_unslash( $_POST['slug'] ?? '' ) );
        $action = sanitize_text_field( wp_unslash( $_POST['plugin_action'] ?? 'install_activate' ) );

        $result = self::install_and_activate_plugin( $slug, $action );
        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * Register Admin Menus for Plugin Installer
     */
    public function register_plugin_installer_menus(): void {
        // Under Appearance > نصب افزونه‌های قالب (Standard ThemeForest / Zhaket TGMPA location)
        add_theme_page(
            'نصب و مدیریت افزونه‌های قالب زرین کد',
            'نصب افزونه‌های قالب',
            'manage_options',
            'zarincode-install-plugins',
            [ $this, 'render_plugin_manager_page' ]
        );
    }

    /**
     * Render Admin Notice when any required plugin is inactive or missing
     */
    public function render_missing_plugins_notice(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( $screen && strpos( $screen->id, 'zarincode-install-plugins' ) !== false ) {
            return;
        }

        $manifest        = self::get_plugins_manifest();
        $missing_names   = [];
        $inactive_names  = [];

        foreach ( $manifest as $slug => $plugin ) {
            if ( empty( $plugin['required'] ) ) {
                continue;
            }
            $st = self::get_plugin_status( $plugin );
            if ( 'not_installed' === $st ) {
                $missing_names[] = $plugin['name'];
            } elseif ( 'installed' === $st ) {
                $inactive_names[] = $plugin['name'];
            }
        }

        if ( empty( $missing_names ) && empty( $inactive_names ) ) {
            return;
        }

        $url = admin_url( 'themes.php?page=zarincode-install-plugins' );
        ?>
        <div class="notice notice-error is-dismissible" style="border-right:5px solid #FDD05B;background:#1E2025;color:#fff;padding:18px 22px;direction:rtl;font-family:Tahoma,system-ui,sans-serif;border-radius:12px;margin-top:15px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
                <div>
                    <h3 style="margin:0 0 8px;font-size:15px;color:#FDD05B;">
                        ⚠️ قالب «زرین کد (Zarincode)» برای عملکرد کامل به نصب و فعال‌سازی افزونه‌های زیر نیاز دارد:
                    </h3>
                    <?php if ( ! empty( $missing_names ) ) : ?>
                        <p style="margin:0 0 4px;font-size:13px;color:#E5E7EB;">
                            <strong>افزونه‌های نصب‌نشده:</strong> <?php echo esc_html( implode( ' ، ', $missing_names ) ); ?>
                        </p>
                    <?php endif; ?>
                    <?php if ( ! empty( $inactive_names ) ) : ?>
                        <p style="margin:0;font-size:13px;color:#FDE68A;">
                            <strong>افزونه‌های نصب‌شده (نیازمند فعال‌سازی):</strong> <?php echo esc_html( implode( ' ، ', $inactive_names ) ); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:10px;">
                    <a href="<?php echo esc_url( $url ); ?>" class="button button-primary button-hero" style="background:#FDD05B;border-color:#FDD05B;color:#2A2C31;font-weight:bold;">
                        ⚡ نصب و فعال‌سازی خودکار افزونه‌ها
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render the Interactive Plugin Installer & Manager Admin Page
     */
    public function render_plugin_manager_page(): void {
        $sync_feedback = '';

        // Synchronous Fallback Handler (if user clicks a non-AJAX form button)
        if ( isset( $_POST['zarincode_sync_plugin_action'] ) && check_admin_referer( 'zarincode_plugin_sync_nonce' ) ) {
            $target_slug = sanitize_text_field( wp_unslash( $_POST['target_slug'] ?? '' ) );
            $act_type    = sanitize_text_field( wp_unslash( $_POST['act_type'] ?? 'install_activate' ) );
            if ( 'bulk_required' === $target_slug ) {
                $ok_count = 0;
                foreach ( self::get_plugins_manifest() as $k => $p ) {
                    if ( ! empty( $p['required'] ) ) {
                        $res = self::install_and_activate_plugin( $k, 'install_activate' );
                        if ( $res['success'] ) {
                            $ok_count++;
                        }
                    }
                }
                $sync_feedback = sprintf( '%d افزونه ضروری قالب زرین کد با موفقیت نصب و فعال‌سازی شدند!', $ok_count );
            } elseif ( $target_slug ) {
                $res           = self::install_and_activate_plugin( $target_slug, $act_type );
                $sync_feedback = $res['message'];
            }
        }

        $manifest = self::get_plugins_manifest();
        $nonce    = wp_create_nonce( 'zarincode_plugin_manager_nonce' );
        ?>
        <style>
            #wpbody-content > .wrap.zc-plugins-fullwidth-wrap {
                width: 100% !important;
                max-width: 100% !important;
                margin: 16px 0 0 0 !important;
                padding: 0 20px 28px 20px !important;
                box-sizing: border-box !important;
            }
        </style>
        <div class="wrap zc-plugins-fullwidth-wrap" style="direction:rtl;font-family:Tahoma,system-ui,sans-serif;width:100%;max-width:none;box-sizing:border-box;">
            <h1 class="screen-reader-text">نصب و مدیریت افزونه‌های قالب زرین کد</h1>
            <hr class="wp-header-end">
            <!-- Header Banner -->
            <div style="background:linear-gradient(135deg,#2A2C31 0%,#15171C 100%);color:#fff;padding:30px 34px;border-radius:24px;border:2px solid #FDD05B;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;box-shadow:0 16px 40px rgba(0,0,0,0.12);">
                <div>
                    <span style="background:#FDD05B;color:#2A2C31;padding:4px 14px;border-radius:999px;font-weight:900;font-size:11.5px;">ZARINCODE TGMPA &amp; AUTO PLUGIN INSTALLER v2.5</span>
                    <h1 style="color:#fff;font-size:23px;margin:10px 0 6px;font-weight:900;">مدیریت و نصب خودکار افزونه‌های مورد نیاز قالب «زرین کد»</h1>
                    <p style="color:#D1D5DB;font-size:13.5px;margin:0;">
                        توسعه‌دهنده: <strong>احسان نادری پناه (09024561001 | Zarincode.com)</strong> — نصب مستقیم از پکیج داخلی قالب و مخزن رسمی بدون نیاز به آپلود دستی
                    </p>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="button" id="zc-bulk-install-btn" class="button button-primary button-hero" style="background:#FDD05B;border-color:#FDD05B;color:#2A2C31;font-weight:900;border-radius:999px;padding:6px 26px;box-shadow:0 8px 20px rgba(253,208,91,0.4);">
                        🚀 نصب و فعال‌سازی یکجای تمام افزونه‌های اجباری با یک کلیک
                    </button>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=zarincode-theme-options' ) ); ?>" class="button button-hero" style="border-radius:999px;background:rgba(255,255,255,0.1);color:#fff;border-color:rgba(255,255,255,0.25);">
                        ⚙️ ورود به پنل تنظیمات پیشرفته قالب
                    </a>
                </div>
            </div>

            <?php if ( $sync_feedback ) : ?>
                <div style="background:#ECFDF5;border:2px solid #10B981;color:#065F46;padding:16px 20px;border-radius:16px;margin-bottom:20px;font-weight:bold;">
                    ✅ <?php echo esc_html( $sync_feedback ); ?>
                </div>
            <?php endif; ?>

            <!-- Live AJAX Progress Box -->
            <div id="zc-plugin-progress-box" style="display:none;background:#FFFBEB;border:2px solid #FDD05B;padding:18px 22px;border-radius:18px;margin-bottom:24px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <strong id="zc-plugin-progress-text" style="color:#2A2C31;font-size:14px;">در حال آماده‌سازی نصب خودکار افزونه‌ها...</strong>
                    <span id="zc-plugin-progress-pct" style="font-weight:900;color:#2A2C31;">0%</span>
                </div>
                <div style="width:100%;height:12px;background:#E5E7EB;border-radius:999px;overflow:hidden;">
                    <div id="zc-plugin-progress-bar" style="width:0%;height:100%;background:linear-gradient(90deg,#2A2C31,#FDD05B);transition:width 0.35s ease;"></div>
                </div>
            </div>

            <!-- Plugins Grid -->
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(350px,1fr));gap:18px;">
                <?php foreach ( $manifest as $slug => $plugin ) :
                    $status = self::get_plugin_status( $plugin );
                    $is_req = ! empty( $plugin['required'] );
                    ?>
                    <div class="zc-plugin-card" data-slug="<?php echo esc_attr( $slug ); ?>" data-required="<?php echo $is_req ? '1' : '0'; ?>" data-status="<?php echo esc_attr( $status ); ?>" style="background:#fff;border-radius:20px;padding:22px;border:2px solid <?php echo 'active' === $status ? '#10B981' : ( $is_req ? '#FDD05B' : '#E5E7EB' ); ?>;display:flex;flex-direction:column;justify-content:space-between;box-shadow:0 6px 18px rgba(0,0,0,0.04);">
                        <div>
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px;">
                                <span style="background:<?php echo $is_req ? '#FEF3C7' : '#F3F4F6'; ?>;color:<?php echo $is_req ? '#92400E' : '#374151'; ?>;padding:4px 12px;border-radius:999px;font-size:11.5px;font-weight:bold;">
                                    <?php echo esc_html( $plugin['badge'] ); ?>
                                </span>
                                <span class="zc-status-pill" style="padding:4px 12px;border-radius:999px;font-size:11.5px;font-weight:bold;background:<?php echo 'active' === $status ? '#D1FAE5' : ( 'installed' === $status ? '#DBEAFE' : '#FEE2E2' ); ?>;color:<?php echo 'active' === $status ? '#065F46' : ( 'installed' === $status ? '#1E40AF' : '#991B1B' ); ?>;">
                                    <?php
                                    if ( 'active' === $status ) {
                                        echo '● فعال و آماده به کار';
                                    } elseif ( 'installed' === $status ) {
                                        echo '◐ نصب شده (غیرفعال)';
                                    } else {
                                        echo '○ نصب نشده';
                                    }
                                    ?>
                                </span>
                            </div>

                            <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:10px;">
                                <div style="width:46px;height:46px;border-radius:14px;background:#2A2C31;color:#FDD05B;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <span class="dashicons <?php echo esc_attr( $plugin['icon'] ); ?>" style="font-size:24px;width:24px;height:24px;"></span>
                                </div>
                                <div>
                                    <h3 style="margin:0 0 4px;font-size:15px;color:#111827;"><?php echo esc_html( $plugin['name'] ); ?></h3>
                                    <div style="font-size:11.5px;color:#6B7280;">نسخه: <strong><?php echo esc_html( $plugin['version'] ); ?></strong> | منبع: <?php echo esc_html( $plugin['source_type'] ); ?></div>
                                </div>
                            </div>

                            <p style="font-size:12.5px;color:#4B5563;line-height:1.75;margin:10px 0 18px;">
                                <?php echo esc_html( $plugin['description'] ); ?>
                            </p>
                        </div>

                        <div style="border-top:1px solid #F3F4F6;padding-top:14px;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                            <form method="post" style="margin:0;width:100%;display:flex;gap:8px;">
                                <?php wp_nonce_field( 'zarincode_plugin_sync_nonce' ); ?>
                                <input type="hidden" name="target_slug" value="<?php echo esc_attr( $slug ); ?>">
                                <?php if ( 'active' === $status ) : ?>
                                    <button type="button" class="button zc-ajax-plugin-btn" data-slug="<?php echo esc_attr( $slug ); ?>" data-action="install_activate" style="flex:1;background:#ECFDF5;border-color:#10B981;color:#065F46;font-weight:bold;">
                                        ✓ افزونه فعال است (بررسی مجدد)
                                    </button>
                                    <?php if ( 'zarincode-core' !== $slug ) : ?>
                                        <button type="submit" name="zarincode_sync_plugin_action" value="1" onclick="this.form.act_type.value='deactivate';" class="button" style="color:#DC2626;border-color:#FCA5A5;">
                                            غیرفعال‌سازی
                                        </button>
                                        <input type="hidden" name="act_type" value="install_activate">
                                    <?php endif; ?>
                                <?php elseif ( 'installed' === $status ) : ?>
                                    <input type="hidden" name="act_type" value="activate">
                                    <button type="submit" name="zarincode_sync_plugin_action" value="1" class="button button-primary zc-ajax-plugin-btn" data-slug="<?php echo esc_attr( $slug ); ?>" data-action="activate" style="flex:1;background:#2563EB;border-color:#2563EB;font-weight:bold;">
                                        ⚡ فعال‌سازی افزونه
                                    </button>
                                <?php else : ?>
                                    <input type="hidden" name="act_type" value="install_activate">
                                    <button type="submit" name="zarincode_sync_plugin_action" value="1" class="button button-primary zc-ajax-plugin-btn" data-slug="<?php echo esc_attr( $slug ); ?>" data-action="install_activate" style="flex:1;background:#2A2C31;border-color:#2A2C31;color:#FDD05B;font-weight:bold;">
                                        📥 نصب و فعال‌سازی خودکار
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <script>
        (function(){
            const ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
            const nonce   = <?php echo wp_json_encode( $nonce ); ?>;

            async function runPluginAction(slug, action, btn) {
                const originalText = btn ? btn.innerHTML : '';
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '⏳ در حال نصب و فعال‌سازی...';
                }
                const formData = new FormData();
                formData.append('action', 'zarincode_plugin_action');
                formData.append('nonce', nonce);
                formData.append('slug', slug);
                formData.append('plugin_action', action);

                try {
                    const res = await fetch(ajaxUrl, { method: 'POST', body: formData });
                    const json = await res.json();
                    if (json.success) {
                        if (btn) {
                            btn.innerHTML = '✅ فعال شد!';
                            btn.style.background = '#10B981';
                            btn.style.color = '#fff';
                        }
                        const card = document.querySelector('.zc-plugin-card[data-slug="' + slug + '"]');
                        if (card) {
                            card.setAttribute('data-status', 'active');
                            card.style.borderColor = '#10B981';
                            const pill = card.querySelector('.zc-status-pill');
                            if (pill) {
                                pill.textContent = '● فعال و آماده به کار';
                                pill.style.background = '#D1FAE5';
                                pill.style.color = '#065F46';
                            }
                        }
                        return true;
                    } else {
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        }
                        alert(json.data && json.data.message ? json.data.message : 'خطا در نصب افزونه');
                        return false;
                    }
                } catch (e) {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                    return false;
                }
            }

            document.querySelectorAll('.zc-ajax-plugin-btn').forEach(function(btn){
                btn.addEventListener('click', function(e){
                    e.preventDefault();
                    const slug   = btn.getAttribute('data-slug');
                    const action = btn.getAttribute('data-action') || 'install_activate';
                    runPluginAction(slug, action, btn);
                });
            });

            const bulkBtn = document.getElementById('zc-bulk-install-btn');
            if (bulkBtn) {
                bulkBtn.addEventListener('click', async function(e){
                    e.preventDefault();
                    const cards = Array.from(document.querySelectorAll('.zc-plugin-card[data-required="1"]'));
                    const pBox  = document.getElementById('zc-plugin-progress-box');
                    const pBar  = document.getElementById('zc-plugin-progress-bar');
                    const pTxt  = document.getElementById('zc-plugin-progress-text');
                    const pPct  = document.getElementById('zc-plugin-progress-pct');

                    pBox.style.display = 'block';
                    bulkBtn.disabled = true;

                    for (let i = 0; i < cards.length; i++) {
                        const slug = cards[i].getAttribute('data-slug');
                        const btn  = cards[i].querySelector('.zc-ajax-plugin-btn');
                        const pct  = Math.round(((i) / cards.length) * 100);
                        pBar.style.width = pct + '%';
                        pPct.textContent = pct + '%';
                        pTxt.textContent = 'در حال نصب و فعال‌سازی افزونه (' + (i + 1) + ' از ' + cards.length + '): ' + slug + ' ...';
                        await runPluginAction(slug, 'install_activate', btn);
                    }

                    pBar.style.width = '100%';
                    pPct.textContent = '100%';
                    pTxt.textContent = '🎉 تمامی افزونه‌های اجباری قالب زرین کد با موفقیت نصب و فعال‌سازی شدند!';
                    bulkBtn.disabled = false;
                });
            }
        })();
        </script>
        <?php
    }
}
