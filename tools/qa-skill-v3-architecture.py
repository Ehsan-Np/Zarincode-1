"""Scoped executable architecture rules, not a claim to full AST analysis."""
from pathlib import Path
import json,re
root=Path(__file__).resolve().parent.parent
checks={}
checks['thin_theme_bootstrap']=len((root/'zarincode/functions.php').read_text().splitlines())<=10
plugin=(root/'zarincode-core/zarincode-core.php').read_text()
checks['plugin_no_theme_autoload']=all(s not in plugin for s in ['WP_CONTENT_DIR','get_template_directory','EmbeddedCore'])
checks['no_embedded_runtime'] = not (root/'zarincode/inc/EmbeddedCore').exists()
manager=(root/'zarincode/inc/Plugins/Plugin_Manager.php').read_text()
checks['no_automatic_install_hook']='after_switch_theme' not in manager
checks['no_plugin_source_overwrite']='file_put_contents' not in manager and 'provision_core_plugin_from_theme' not in manager
checks['no_unknown_installer_actions']='in_array( $action' in manager
checks['uninstall_retains_data']=not re.search(r'\b(?:delete_option|wp_delete_post|wp_delete_term|unlink)\s*\(', (root/'zarincode-core/uninstall.php').read_text())
checks['new_domain_independent']=all('Elementor' not in (root/p).read_text() for p in ['zarincode-core/includes/Support/Settings_Schema.php'])
checks['contract_trait_on_all_bases']=all('use \\Zarincode\\Runtime260\\Elementor\\Widget_Contract;' in (root/'zarincode-core/includes/Widgets'/p).read_text() for p in ['Elementor_Widgets_Collection.php','Professional_Widgets.php','Studio_Widgets.php'])
print(json.dumps({'suite':'skill-v3-scoped-architecture','checks':checks,'passed':all(checks.values()),'limitations':['No complete AST dependency graph or full legacy business-logic audit.']},indent=2))
raise SystemExit(0 if all(checks.values()) else 1)
