# Custom Error Pages

This directory contains custom error page templates for the LocalGem application.

## Available Error Pages

- **500.php** - Internal Server Error (purple gradient)
- **404.php** - Page Not Found (pink/red gradient)
- **403.php** - Access Denied (pink/yellow gradient)

## How It Works

1. When an exception is thrown, the Swidly framework catches it in `Swidly::handleException()`
2. The framework determines the HTTP status code from the exception
3. It looks for a matching error template in `themes/{theme}/views/errors/{code}.php`
4. If found, it renders the custom error page
5. If not found, it renders a simple fallback error page

## Available Variables

All error pages have access to the following variables:

- `$homeUrl` - URL to redirect users back to the homepage
- `$debugMode` - Boolean indicating if debug mode is enabled
- `$errorMessage` - The error message (only shown in debug mode)
- `$errorFile` - The file where the error occurred (debug mode only)
- `$errorLine` - The line number where the error occurred (debug mode only)
- `$errorTrace` - Full stack trace (debug mode only, 500 page only)

## Testing

Test the error pages by visiting:

- `/test-500` - Triggers a 500 Internal Server Error
- `/test-403` - Triggers a 403 Access Denied
- `/invalid-page` - Triggers a 404 Page Not Found

## Customization

To customize error pages:

1. Edit the PHP files in this directory
2. Modify the styles, messages, or layout as needed
3. Add new error pages by creating files like `401.php`, `503.php`, etc.
4. All pages should be responsive and user-friendly

## Debug Mode

When debug mode is enabled (`app::debug => true` in config), error pages will display:
- Error message
- File and line number
- Stack trace (for 500 errors)

In production, these details are hidden for security.
