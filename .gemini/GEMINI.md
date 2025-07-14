# Gemini CLI Project Guidelines: OpenCart Module

## 1. Project Overview

- **Project:** OpenCart E-commerce Platform
- **Version:** 3.2
- **Language:** PHP 7.4+
- **Database:** MySQL
- **Key Goal:** Develop and maintain custom admin modules.

## 2. Coding Conventions & Architecture

- **Framework:** OpenCart uses a custom MVC-L (Model-View-Controller-Language) pattern, which is consistent across both the admin and catalog (storefront) applications.

- **Core Directories:**
    - `admin/`: Contains the backend administration panel.
    - `catalog/`: Contains the frontend of the store that customers interact with.
    - `system/`: Contains the core OpenCart framework, including the engine, libraries, and helpers. Modifications to this directory should be avoided unless absolutely necessary.

- **Admin (Backend) Structure:**
    - **Controllers:** `admin/controller/`. For modules, this is `admin/controller/extension/module/`.
    - **Models:** `admin/model/`. For modules, this is `admin/model/extension/module/`.
    - **Views:** `.twig` files in `admin/view/template/`. For modules, this is `admin/view/template/extension/module/`.
    - **Language Files:** `admin/language/<language-code>/`. For modules, this is `admin/language/en-gb/extension/module/`.
    - **Permissions:** Permissions for new admin routes must be added in `admin/controller/user/user_permission.php` to be accessible.

- **Catalog (Frontend) Structure:**
    - **Controllers:** `catalog/controller/`. For modules, this is `catalog/controller/extension/module/`.
    - **Models:** `catalog/model/`. For modules, this is `catalog/model/extension/module/`.
    - **Views:** `.twig` files in `catalog/view/theme/<your_theme>/template/`. For modules, this is `catalog/view/theme/<your_theme>/template/extension/module/`.
    - **Language Files:** `catalog/language/<language-code>/`. For modules, this is `catalog/language/en-gb/extension/module/`.

## 3. Database Schema

- The database schema is complex. Key tables for products are:
    - `oc_product`: Core product data (model, sku, price, quantity).
    - `oc_product_description`: Product names and descriptions, joined on `product_id`.
    - `oc_product_option`: Links products to options.
    - `oc_product_option_value`: Defines the specific values for a product's options (e.g., Size: Large), including stock and price adjustments. Joined on `product_option_id`.
    - `oc_option_description` and `oc_option_value_description`: Contain the names of the options and their values.
- Always join on `language_id` when querying description tables. The default language ID can be fetched from config: `$this->config->get('config_language_id')`.

## 4. Key Commands

- There are no automated tests or linters set up in this project. Verification must be done manually by checking the functionality in the browser.
