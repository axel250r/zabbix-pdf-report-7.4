


# Zabbix PDF Report Generator

<p align="center">
  <a href="https://paypal.me/axel250r">
    <img alt="Donate" src="https://img.shields.io/badge/Donate-PayPal-00457C?logo=paypal&logoColor=white">
  </a>
  <a href="https://github.com/axel250r/zabbix-pdf-report-6.0/stargazers">
    <img alt="GitHub Stars" src="https://img.shields.io/github/stars/axel250r/zabbix-pdf-report-6.0.svg?style=social&logo=github&cacheSeconds=60&_v=2025-09-28-10">
  </a>
  <a href="https://www.linkedin.com/in/axel-del-canto-del-canto-4ba643186/">
    <img alt="LinkedIn" src="https://img.shields.io/badge/LinkedIn-Axel%20Del%20Canto-0A66C2?logo=linkedin&logoColor=white">
  </a>
</p>



Created by **Axel Del Canto** WITH IA.

A simple and powerful web tool to select Zabbix items and export their historical graphs into a PDF report.

---

## ✨ Key Features

* **PDF Export:** Generate professional PDF reports with graphs of the selected items.
* **Multiple Selection:** Intuitive interface to select hosts, host groups, and templates.
* **Smart Pagination:** Easily navigate through hundreds of hosts, groups, and templates thanks to a paginated interface.
* **Dynamic Filtering:** Quickly find what you're looking for with real-time filters in all selection lists.
* **Multi-language Support:** The interface is available in English and Spanish (and is easily extendable to more languages).
* **Customizable:**
    * Allows replacing the default logo with your own company's logo.
    * Supports light and dark themes that adapt to system preferences.
* **Secure:** Implements CSRF protection and a Content Security Policy (CSP) for safer operation.
* **Open Source License:** Protected under the GNU GPLv3 license to ensure the software and its derivatives remain free.

---
URL Login: http://your-zabbix/zabbix/zabbix-pdf-report/login.php
## 🚀 Quick Setup

To get the application working on your own Zabbix server, you only need to follow two steps:

#### **Step 1: Copy the Project Folder**

Copy the entire project folder (`zabbix-pdf-report/`) to a directory on your web server (e.g., `/var/www/html/` or `/usr/share/zabbix/`).

#### **Step 2: Create and Edit the `config.php` File**

This is the only file you need to modify.

1.  In the project's root directory, find the file `config.php.example`.
2.  Make a copy of this file and rename it to `config.php`.
3.  Open your new `config.php` and edit the following lines with your Zabbix instance's data and, optionally, the path to your logo.
4.  Set your PHP time zone in config.php
   // Time zone for date/time handling in the report generator
date_default_timezone_set('America/Santiago');  // change to your preferred TZ if needed
5.  Add a “PDF Reporter” button to the Zabbix front-end menu

File to edit: /usr/share/zabbix/include/classes/helpers/CMenuHelper.php

Find this line: $submenu_reports = array_filter($submenu_reports);

Paste the following block right above that line save and restart httpd:

$submenu_reports[] = CWebUser::checkAccess(CRoleHelper::UI_REPORTS_SYSTEM_INFO)
    ? (new CMenuItem(_('Informe PDF')))  // or _('PDF Report') if you prefer English
        ->setUrl(new CUrl('zabbix-pdf-report/login.php'), true)
        ->setId('report_pdf')
        // IMPORTANT: use setAliases() (plural), not setAlias()
        ->setAliases(['zabbix-pdf-report/chooser.php'])
    : null;

```php
<?php
// config.php

/**
 * URL of the Zabbix frontend.
 * Modify this line with your Zabbix URL.
 * Example: 'http://192.168.1.100/zabbix'
 */
define('ZABBIX_URL', 'http://your-zabbix.com/zabbix');

/**
 * URL of the Zabbix API endpoint.
 * You usually don't need to change this line.
 */
define('ZABBIX_API_URL', ZABBIX_URL . '/api_jsonrpc.php');
define('ZABBIX_API_USER', 'Admin');   // Nombre de usuario de la API
define('ZABBIX_API_PASS', 'zabbix');  // Contraseña del usuario de la API
/**
 * (Optional) Path to the custom logo.
 * To use your own logo, uncomment this line and set the path to your image file.
 * The path must be relative to the project root.
 * Example: define('CUSTOM_LOGO_PATH', 'assets/my_logo.png');
 */
// define('CUSTOM_LOGO_PATH', 'assets/your_custom_logo.png');
 * To use your own logo, uncomment this line and set the path to your image file.
