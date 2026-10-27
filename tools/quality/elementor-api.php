<?php
/** Analysis-only public files-manager API used by Manager. Never included at runtime. */
namespace Elementor {
    class Plugin {
        public \Elementor\Core\Files\Manager $files_manager;
        public \Elementor\Core\Kits\Manager $kits_manager;
        public static self $instance;
        public static function instance(): self {}
    }
}
namespace Elementor\Core\Files {
    class Manager {
        public function clear_cache(): void {}
    }
}

namespace Elementor\Core\Kits {
    class Manager {
        public function get_active_kit() {}
    }
}
namespace Elementor\Core\Settings\Page {
    class Manager {
        public const META_KEY = '_elementor_page_settings';
    }
}
namespace Elementor\Core\Base {
    class Document {
        public function get_meta( $key ) {}
        public function update_meta( $key, $value ) {}
        public function refresh_post() {}
        public function get_settings() {}
    }
}
