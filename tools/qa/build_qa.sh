#!/bin/bash
set -e
QA=/tmp/qa3
mkdir -p $QA /tmp/dl
cd /tmp/dl
[ -f wp-cli.phar ] || curl -sSLo wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
[ -f latest.tar.gz ] || curl -sSLo latest.tar.gz https://wordpress.org/latest.tar.gz
[ -f sqlite.zip ] || curl -sSLo sqlite.zip https://downloads.wordpress.org/plugin/sqlite-database-integration.zip
[ -f elementor.zip ] || curl -sSLo elementor.zip "https://downloads.wordpress.org/plugin/elementor.zip"
[ -f woocommerce.zip ] || curl -sSLo woocommerce.zip "https://downloads.wordpress.org/plugin/woocommerce.zip"
if [ ! -f $QA/wp-load.php ]; then
  tar xzf latest.tar.gz -C $QA --strip-components=1
fi
cd $QA
WPC="php /tmp/dl/wp-cli.phar --allow-root --path=$QA"
if [ ! -f wp-config.php ]; then
  $WPC config create --dbname=wp --dbuser=wp --dbpass=wp --skip-check --force
  echo "define('WP_DEBUG', true); define('WP_DEBUG_LOG', true); define('WP_DEBUG_DISPLAY', false); define('WP_ENVIRONMENT_TYPE','local');" >> wp-config.php
fi
# sqlite integration
unzip -qo /tmp/dl/sqlite.zip -d wp-content/plugins/
cp wp-content/plugins/sqlite-database-integration/db.copy wp-content/db.php
sed -i "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$QA/wp-content/plugins/sqlite-database-integration#; s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" wp-content/db.php
unzip -qo /tmp/dl/elementor.zip -d wp-content/plugins/
unzip -qo /tmp/dl/woocommerce.zip -d wp-content/plugins/
ln -sfn /home/user/zarincode wp-content/themes/zarincode
ln -sfn /home/user/zarincode-core wp-content/plugins/zarincode-core
if ! $WPC core is-installed 2>/dev/null; then
  $WPC core install --url=http://127.0.0.1:8897 --title="زرین کد QA" --admin_user=admin --admin_password=admin --admin_email=admin@example.com --skip-email
fi
$WPC theme activate zarincode
$WPC plugin activate zarincode-core elementor woocommerce sqlite-database-integration
$WPC option update siteurl http://127.0.0.1:8897
$WPC option update home http://127.0.0.1:8897
$WPC rewrite structure '/%postname%/' --hard
$WPC eval 'if(class_exists("\\Zarincode\\Runtime260\\Demo\\Demo_Seeder")){ \Zarincode\Runtime260\Demo\Demo_Seeder::seed_all(); echo "SEEDED\n"; }'
echo "QA_BUILD_DONE"
