# Girardota.com.co

WordPress theme and WooCommerce booking plugin for **Girardota.com.co**, a travel and eco-tourism platform for Girardota, Antioquia (Colombia). All site content is in Spanish.

## Structure

| Folder | Install path | Description |
| --- | --- | --- |
| `girardota/` | `wp-content/themes/girardota` | Classic theme: home page, inner pages, blog, events, places and WooCommerce templates. |
| `girardota-reservas/` | `wp-content/plugins/girardota-reservas` | Booking plugin: lodging (per night) and tours (per person) on top of WooCommerce, `evento` and `lugar` post types, shortcodes, contact form and one-time site setup. |

## Requirements

- WordPress 6.4+
- PHP 7.4+ (tested on 8.3)
- WooCommerce (tested on 11.3)

## Installation

1. Copy both folders to the paths above (or zip them and upload from the WordPress admin).
2. Activate WooCommerce, then the **Girardota** theme, then the **Girardota Reservas** plugin.
3. On activation the plugin runs a one-time setup: COP currency, Colombian store settings, Spanish (es_CO) language packs, product categories, sample lodging and tours, events, restaurants/cafés, pages, menus and payment methods.

## Booking flow

- **Lodging** (`_gt_tipo = alojamiento`): check-in/check-out dates and guests; price = nightly rate × nights.
- **Tours** (`_gt_tipo = tour`): date, departure time and number of people; enforces max group size and allowed weekdays (`_gt_dias`).
- Classic cart/checkout shortcodes are used so the custom booking fields and checkout fields (ID document, phone) work.

## Shortcodes

`[gt_alojamientos]`, `[gt_tours]`, `[gt_eventos]`, `[gt_lugares tipo="restaurantes|cafes"]`, `[gt_whatsapp]`, `[gt_whatsapp_numero]`, `[gt_contacto]`

## Configuration

The booking WhatsApp number can be changed under **Settings → General** (`gt_whatsapp`, `gt_whatsapp_display`).
