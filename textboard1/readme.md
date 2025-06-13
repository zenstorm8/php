# Text Board

A lightweight, file-based PHP message board for simple text posting. Designed for small deployments or local use, this application requires no database—just a writable JSON file to store posts and a simple file for error logs.

---

## Features

* **Configurable Settings**: Easily adjust board title, maximum posts, message length, rate limits, and logging via constants.
* **No Database Required**: Stores posts in a JSON file (`posts.json`).
* **CSRF Protection**: Uses a per-session token to prevent cross-site request forgery.
* **Rate Limiting**: Enforces a delay between posts per user to mitigate spam.
* **Error Logging**: Logs PHP errors and optional heartbeat entries to `error.txt`.
* **Security Headers**: Implements strict HTTP headers (CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy) to enhance security.
* **Simple Theming**: Dark theme CSS included, easily customizable.

---

## Requirements

* PHP **8.4.8** or higher
* Writable filesystem for the application directory (to create and update `posts.json` and `error.txt`)
* A web server (e.g., Apache, Nginx) configured to run PHP applications

---

## Configuration

All configuration constants are defined at the top of `index.php`. Modify these values to customize behavior:

```php
// Board title displayed in the page `<title>` and `<h1>`
define('BOARD_TITLE', 'Text Board');

// Maximum number of recent posts to retain
define('MAX_POSTS', 30);

// Maximum characters allowed per message
define('MAX_MESSAGE_LENGTH', 2000);

// Minimum seconds required between consecutive posts per user
define('RATE_LIMIT_SECONDS', 10);

// Toggle whether page-load "heartbeat" entries are appended to the error log
define('ENABLE_HEARTBEAT_LOG', true);

// Whether to clear or preserve the error log on each page load
define('CLEAR_ERROR_LOG_ON_START', false);
```

> **Note:** If you enable `CLEAR_ERROR_LOG_ON_START`, `error.txt` will be wiped on every page load. Use caution in production.

---

## Installation

1. **Download or clone** this repository into your web root directory.
2. Ensure **write permissions** for the application directory so PHP can create/update `posts.json` and `error.txt`:

   ```bash
   chown -R www-data:www-data /path/to/app
   chmod -R 770 /path/to/app
   ```
3. Verify your server is running **PHP 8.4.8+**. The application will exit with an error if an older PHP version is detected.
4. Access the board in your browser: `https://your-domain.com/`.

---

## Usage

* Visitors can post messages via the text area. Each message is trimmed, validated (not empty, within length limits), then prepended to the list in `posts.json`.
* The board displays up to `MAX_POSTS` recent messages.
* Posting triggers a **redirect** back to the main page to avoid accidental form resubmission.

---

## File Structure

```
/ (project root)
├── index.php         # Main application file
├── posts.json        # Stores message history (auto-created)
├── error.txt         # PHP error & heartbeat log (auto-created)
└── README.md         # This documentation
```

---

## Security Considerations

1. **CSRF Token**: Generated per session and validated on each POST.
2. **Session Management**: Uses PHP native sessions. Ensure secure cookie settings in production.
3. **Error Logging**: Errors logged to `error.txt`—never displayed in browser.
4. **HTTP Headers**:

   * `X-Frame-Options: DENY`
   * `X-Content-Type-Options: nosniff`
   * `Referrer-Policy: strict-origin-when-cross-origin`
   * `Content-Security-Policy: default-src 'self'; script-src 'none'; style-src 'self' 'unsafe-inline';`

---

## Customization

* **Styling**: Edit the inline `<style>` block in `index.php` or move to a separate CSS file.
* **Templates**: Modify HTML structure directly in `index.php`.
* **Logging**: Toggle heartbeat logging or clear-log-on-start via constants.
* **Rate Limits**: Adjust `RATE_LIMIT_SECONDS` for faster or slower posting limits.

---

## Troubleshooting

* **No Posts Appear**: Ensure `posts.json` is writable and valid JSON.
* **Cannot Write to Log**: Check permissions for `error.txt`.
* **PHP Version Error**: Confirm `php -v` shows version ≥ 8.4.8.
* **CSRF Errors**: If you see mismatches in `error.txt`, verify sessions are working (check cookie settings).

---

## Contributing

Contributions are welcome! Please fork the repo and submit a pull request for bug fixes or enhancements.

---

## License

This project is released under the [MIT License](LICENSE). Feel free to use and modify as needed.
