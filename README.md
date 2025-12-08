# Zabbix PDF Report Generator

Created by **Axel Del Canto**.

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

##  Quick Setup

To get the application working on your own Zabbix server, you only need to follow two steps:

#### **Step 1: Copy the Project Folder**

Copy the entire project folder (`zabbix-pdf-report/`) to a directory on your web server (e.g., `/var/www/html/` or `/usr/share/zabbix/`).

#### **Step 2: Create and Edit the `config.php` File**

This is the only file you need to modify.

1.  In the project's root directory, find the file `config.php.example`.
2.  Make a copy of this file and rename it to `config.php`.
3.  Open your new `config.php` and edit the following lines with your Zabbix instance's data and, optionally, the path to your logo.

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

/**
 * (Optional) Path to the custom logo.
 * To use your own logo, uncomment this line and set the path to your image file.
 * The path must be relative to the project root.
 * Example: define('CUSTOM_LOGO_PATH', 'assets/my_logo.png');
 */
// define('CUSTOM_LOGO_PATH', 'assets/your_custom_logo.png');