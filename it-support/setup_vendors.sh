#!/bin/bash
# IT Support - Download offline vendor assets
# Run this script once to download Bootstrap, Chart.js and jQuery

set -e
cd "$(dirname "$0")"

echo "Creating vendor directories..."
mkdir -p assets/vendor/bootstrap/css
mkdir -p assets/vendor/bootstrap/js
mkdir -p assets/vendor/bootstrap/fonts
mkdir -p assets/vendor/chartjs
mkdir -p assets/vendor/jquery

echo "Downloading Bootstrap 5.3.2 CSS..."
curl -fsSL -o assets/vendor/bootstrap/css/bootstrap.min.css \
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"

echo "Downloading Bootstrap 5.3.2 JS..."
curl -fsSL -o assets/vendor/bootstrap/js/bootstrap.bundle.min.js \
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"

echo "Downloading Bootstrap Icons 1.11.3..."
curl -fsSL -o assets/vendor/bootstrap/css/bootstrap-icons.min.css \
  "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"

# Download bootstrap-icons font files
FONTS_BASE="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts"
curl -fsSL -o assets/vendor/bootstrap/fonts/bootstrap-icons.woff \
  "${FONTS_BASE}/bootstrap-icons.woff" 2>/dev/null || true
curl -fsSL -o assets/vendor/bootstrap/fonts/bootstrap-icons.woff2 \
  "${FONTS_BASE}/bootstrap-icons.woff2" 2>/dev/null || true

# Fix font paths in CSS (cross-platform compatible)
if [[ "$OSTYPE" == "darwin"* ]]; then
    sed -i '' 's|fonts/bootstrap-icons|../fonts/bootstrap-icons|g' \
        assets/vendor/bootstrap/css/bootstrap-icons.min.css 2>/dev/null || true
else
    sed -i 's|fonts/bootstrap-icons|../fonts/bootstrap-icons|g' \
        assets/vendor/bootstrap/css/bootstrap-icons.min.css 2>/dev/null || true
fi

echo "Downloading Chart.js 4.4.1..."
curl -fsSL -o assets/vendor/chartjs/chart.min.js \
  "https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"

echo "Downloading jQuery 3.7.1..."
curl -fsSL -o assets/vendor/jquery/jquery.min.js \
  "https://code.jquery.com/jquery-3.7.1.min.js"

echo ""
echo "All vendor assets downloaded successfully!"
echo "Now configure config.php with your database credentials and access the app."
