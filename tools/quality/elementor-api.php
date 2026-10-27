<?php
/** Analysis-only public files-manager API used by Manager. Never included at runtime. */
namespace Elementor {
    class Plugin {
        public \Elementor\Core\Files\Manager $files_manager;
        public static function instance(): self {}
    }
}
namespace Elementor\Core\Files {
    class Manager {
        public function clear_cache(): void {}
    }
}
