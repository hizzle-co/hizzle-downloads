# hizzle-downloads
Create free or paid downloadable files

## Protecting local downloads

Files remain in the WordPress uploads directory. Download permissions and passwords
are checked by the PHP download handler, so the web server must block direct HTTP
access to `hizzle_uploads` and all its subdirectories. Random filenames reduce
guessability; they do not replace these server rules.

The plugin creates `.htaccess` for Apache and `web.config` for IIS during installation
and upgrades, without overwriting existing files. Apache must allow the `.htaccess`
access rules. IIS must have URL Authorization installed and allow the configuration
in `web.config`; see [IIS authorization documentation](https://learn.microsoft.com/en-us/iis/configuration/system.webserver/security/authorization/).
If Apache ignores `.htaccess`, ask the host to deny HTTP access to this directory in
the virtual host configuration.

For nginx, add this rule inside the site's `server` block and reload nginx:

```nginx
location ^~ /wp-content/uploads/hizzle_uploads/ {
    deny all;
}
```

Adjust the URI to match the site's actual uploads URL, including subdirectory or
multisite prefixes. Apply equivalent rules to any CDN or alternate uploads hostname.
The `^~` prefix prevents generic regex locations for static files from taking
precedence; see [nginx location documentation](https://nginx.org/en/docs/http/ngx_http_core_module.html#location).

With a deny rule, use PHP streaming instead of X-Accel-Redirect or redirect mode.
Add this to a site plugin or must-use plugin:

```php
add_filter( 'hizzle_download_method', function () {
    return 'force';
} );
```

Redirect mode intentionally exposes the storage URL and is unsuitable for files
that require continuing access control. The versions API omits storage paths for
all callers. New GitHub downloads use random storage names; existing files retain
their names, including old copies left by earlier releases. Protect those files
with server rules too. Audit and remove obsolete copies after verifying they are
no longer referenced by downloads, attachments, or integrations.

Verify deployment by requesting an existing file's uploads URL while logged out:
it must be denied. Then verify the PHP download URL succeeds for an authorized
request and rejects an unauthorized request (including password-protected files).

## S3 Sync Configuration

The plugin supports automatic syncing of uploaded files to S3-compatible storage services like Amazon S3 and Cloudflare R2.

To enable this feature, define the following constants in your `wp-config.php` file:

```php
// Required constants
define( 'HIZZLE_DOWNLOADS_S3_ENDPOINT', 'https://{$bucket}.s3.{$region}.amazonaws.com' );
define( 'HIZZLE_DOWNLOADS_S3_ACCESS_KEY', 'your-access-key' );
define( 'HIZZLE_DOWNLOADS_S3_SECRET_KEY', 'your-secret-key' );
define( 'HIZZLE_DOWNLOADS_S3_BUCKET', 'your-bucket-name' );
define( 'HIZZLE_DOWNLOADS_S3_REGION', 'your-bucket-region' );
 
```

### How it works

When the S3 credentials are configured:
- Files uploaded to the `wp-content/uploads/hizzle_uploads/` directory are automatically synced to your S3-compatible storage
- Files are organized by hostname (e.g., `my-site.com/path/to/file.zip`)
- The sync happens automatically when downloads are created or updated

### Cloudflare R2 Example

```php
define( 'HIZZLE_DOWNLOADS_S3_ACCESS_KEY', 'your-r2-access-key' );
define( 'HIZZLE_DOWNLOADS_S3_SECRET_KEY', 'your-r2-secret-key' );
define( 'HIZZLE_DOWNLOADS_S3_BUCKET', 'your-bucket-name' );
define( 'HIZZLE_DOWNLOADS_S3_ENDPOINT', 'https://your-account-id.r2.cloudflarestorage.com/your-bucket-name' );
```

### DigitalOcean Spaces Example

```php
define( 'HIZZLE_DOWNLOADS_S3_ACCESS_KEY', 'your-spaces-access-key' );
define( 'HIZZLE_DOWNLOADS_S3_SECRET_KEY', 'your-spaces-secret-key' );
define( 'HIZZLE_DOWNLOADS_S3_BUCKET', 'your-space-name' );
define( 'HIZZLE_DOWNLOADS_S3_ENDPOINT', 'https://your-space-name.nyc3.digitaloceanspaces.com' );
```
