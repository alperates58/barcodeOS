# DataMatrix Creator Web

Shared-hosting friendly PHP + MySQL web version of the desktop DataMatrix Creator.

## Quick Install

1. Upload the `web-app` folder contents to your hosting account.
2. Make sure `config/` is writable during installation.
3. Open `https://your-domain.com/install/`.
4. Enter MySQL and admin details.
5. Delete or protect the `install/` directory after setup.

## Requirements

- PHP 8.1+
- MySQL 5.7+ or MariaDB 10.3+
- PDO MySQL extension
- Modern browser with JavaScript enabled

Barcode rendering currently runs in the browser through bwip-js, which supports GS1 DataMatrix, Data Matrix, QR Code, Code 128, GS1-128, EAN-13 and many more symbols. This keeps the first version compatible with ordinary Linux hosting.

## First MVP Features

- Paste text directly into the form.
- Upload TXT or CSV files.
- Parse one code per line or the first CSV column.
- Normalize GS1 structures compatible with the original desktop app.
- Generate preview in the browser.
- Download a selected PNG.
- Download all valid barcodes as a ZIP.

