#!/usr/bin/env bash
# Check PHP environment for required extensions and storage permissions
set -euo pipefail

PHP_BIN=${1:-/usr/bin/php}

echo "Using PHP: $PHP_BIN"
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  echo "PHP binary not found: $PHP_BIN" >&2
  exit 2
fi

echo
echo "PHP version and ini file:"
"$PHP_BIN" -v
"$PHP_BIN" -i | awk -F': ' '/Loaded Configuration File/ {print $2; exit}'

echo
echo "Loaded extensions (filtered):"
"$PHP_BIN" -m | egrep -i 'dom|xml|pdo|sqlite' || true

echo
echo "Quick runtime checks for extensions dom/xml and pdo_sqlite:" 
"$PHP_BIN" -r "echo 'dom:' . (extension_loaded('dom') ? 'yes' : 'no') . PHP_EOL; echo 'xml:' . (extension_loaded('xml') ? 'yes' : 'no') . PHP_EOL; echo 'pdo_sqlite:' . (extension_loaded('pdo_sqlite') ? 'yes' : 'no') . PHP_EOL;"

echo
echo "Suggest commands to install on common distros. Run only the block that matches your system and PHP version."
cat <<'EOF'
# Debian/Ubuntu (system PHP)
sudo apt update
sudo apt install -y php-xml php-sqlite3
# If using a specific PHP version (8.2):
# sudo apt install -y php8.2-xml php8.2-sqlite3
# restart Apache
sudo systemctl restart apache2

# Fedora/RHEL (dnf)
sudo dnf install -y php-xml php-sqlite3
sudo systemctl restart httpd

# Arch Linux
sudo pacman -Syu php php-xml php-sqlite
sudo systemctl restart httpd

# XAMPP (Linux) - edit /opt/lampp/etc/php.ini and ensure lines are uncommented:
# extension=pdo_sqlite
# extension=sqlite3
# extension=dom
# extension=xml
# then restart XAMPP:
# sudo /opt/lampp/lampp restart
EOF

echo
echo "Check application storage path and permissions."
# Try to read .env for TCC_STORAGE_PATH or TCC_DB_SQLITE_PATH
ENV_FILE=".env"
STORAGE=${TCC_STORAGE_PATH:-}
DBPATH=${TCC_DB_SQLITE_PATH:-}
if [ -f "$ENV_FILE" ]; then
  STORAGE=$(grep -E '^TCC_STORAGE_PATH=' "$ENV_FILE" | cut -d'=' -f2- || true)
  DBPATH=$(grep -E '^TCC_DB_SQLITE_PATH=' "$ENV_FILE" | cut -d'=' -f2- || true)
fi

if [ -n "$STORAGE" ]; then
  echo "TCC_STORAGE_PATH=$STORAGE"
  if [ -e "$STORAGE" ]; then
    ls -ld "$STORAGE"
  else
    echo "Directory does not exist. Create and chown to web user (example www-data):"
    echo "  sudo mkdir -p $STORAGE && sudo chown -R www-data:www-data $STORAGE && sudo chmod -R 750 $STORAGE"
  fi
fi

if [ -n "$DBPATH" ]; then
  echo "TCC_DB_SQLITE_PATH=$DBPATH"
  DBDIR=$(dirname "$DBPATH")
  if [ -d "$DBDIR" ]; then
    ls -ld "$DBDIR"
  else
    echo "DB directory does not exist. Create and set permissions:"
    echo "  sudo mkdir -p $DBDIR && sudo chown -R www-data:www-data $DBDIR && sudo chmod -R 750 $DBDIR"
  fi
fi

echo
echo "If you're using SELinux (RHEL/Fedora), and Apache can't write, run as root:"
echo "  sudo chcon -R -t httpd_sys_rw_content_t /path/to/storage"

echo
echo "After installing extensions, verify again with:"
echo "  $PHP_BIN -m | egrep -i 'dom|xml|pdo|sqlite'"
echo "Then run composer install and composer dump-autoload in project root."

exit 0
