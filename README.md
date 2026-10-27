<p align="center">
  <img src="assets/icon-256x256.png" alt="Hosted JFT logo" width="128" height="128">
</p>

# Hosted JFT for WordPress

[![WordPress Plugin](https://img.shields.io/wordpress/plugin/v/hosted-jft)](https://wordpress.org/plugins/hosted-jft/)
[![WordPress Tested](https://img.shields.io/wordpress/plugin/tested/hosted-jft)](https://wordpress.org/plugins/hosted-jft/)
[![PHP Version](https://img.shields.io/wordpress/plugin/required-php/hosted-jft)](https://wordpress.org/plugins/hosted-jft/)

[WordPress plugin](https://wordpress.org/plugins/hosted-jft/) that lets an NA Community host their own translated version of the Just For Today (JFT) daily reading.

Each day's reading is a normal WordPress post, tagged with a custom field holding the date in `MM-DD` format. The plugin looks up the post for "today" (in your configured time zone) and renders it through a shortcode, a widget, or a REST endpoint.

## Usage

Add the shortcode to any page or post:

```
[hosted_jft]
```

Override the time zone for a single placement:

```
[hosted_jft timezone="Europe/Rome"]
```

## Installation

1. Upload to `/wp-content/plugins/hosted-jft/` (or install from the WordPress plugin directory)
2. Activate in WordPress admin
3. Go to **Settings → Hosted JFT** and set the **Custom Field Name** and **Time Zone**
4. Create one post per day, each with that custom field set to the day's date (`MM-DD`, e.g. `02-04` for February 4th)
5. Add `[hosted_jft]` to a page, or add the **Hosted JFT** widget to a sidebar/footer

## Settings

Configured under **Settings → Hosted JFT**:

| Setting            | Description                                                                 |
|--------------------|-----------------------------------------------------------------------------|
| Custom Field Name  | Name of the custom field on each JFT post that stores the `MM-DD` date      |
| Time Zone          | Your local time zone; determines which day's post is shown                  |
| JFT Page URL       | URL of the page that displays the full JFT (used by the widget's link)      |
| More Text          | Text for the widget's "view full JFT" link                                  |

## REST Endpoint

The current day's reading is available as JSON:

```
GET /wp-json/hosted-jft/v1/jft
GET /wp-json/hosted-jft/v1/jft?tz=Europe/Rome
```

A legacy path (`/get-jft`, optionally `?tz=...`) is retained for backwards compatibility with existing integrations.

## Development

See [CONTRIBUTING.md](CONTRIBUTING.md) for local setup, coding standards, and testing.
