#!/bin/bash
set -x
export DEBIAN_FRONTEND=noninteractive
sudo apt-get update -qq
sudo apt-get install -y -qq php8.4-cli php8.4-sqlite3 php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-intl unzip curl fonts-dejavu 2>&1 | tail -3
php -v 2>&1 | head -2
mkdir -p /tmp/qa3 /tmp/shots
echo "SETUP_DONE"
