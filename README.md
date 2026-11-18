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

## Client IP restrictions behind proxies

IP access rules use the validated `REMOTE_ADDR` socket address by default. They ignore
`X-Real-IP`, `X-Forwarded-For`, and `CF-Connecting-IP` from direct, untrusted clients.
If your web server already restores the real client into `REMOTE_ADDR`, no plugin
proxy configuration is needed.

Download logging is separate: `hizzle_downloads_get_ip_address()` records the first
valid address from `CF-Connecting-IP`, `X-Real-IP`, the first `X-Forwarded-For`
entry, or `REMOTE_ADDR`, in that order. This preserves CDN visitor addresses in
logs without requiring proxy configuration. Those forwarded values are
observational and can be spoofed on an unprotected origin; they never authorize
a download. Conditional IP rules use `hizzle_downloads_get_access_ip_address()`.

Otherwise, configure only the actual proxy addresses/networks that connect to PHP.
For example, in a site plugin or must-use plugin (replace these example addresses):

```php
add_filter( 'hizzle_downloads_trusted_proxies', function () {
    return array( '10.0.0.10', '2001:db8::10' );
} );
```

The list accepts IPv4/IPv6 addresses and CIDRs. Never trust every address or a
network containing untrusted clients. For trusted peers the default header is
`X-Forwarded-For`: the plugin walks it from right to left through trusted proxies,
stopping at the first untrusted hop. Your proxies must append the actual peer
address or replace the chain with verified data; they must not pass unverified
client headers through unchanged.

If your trusted ingress instead overwrites a single-IP header, select it explicitly:

```php
add_filter( 'hizzle_downloads_forwarded_ip_header', function () {
    return 'HTTP_X_REAL_IP';
} );
```

Supported selections are `HTTP_X_FORWARDED_FOR`, `HTTP_X_REAL_IP`, and
`HTTP_CF_CONNECTING_IP`. A selected single-IP header must contain one valid IP.
Missing/invalid required forwarding data yields an unknown address, which cannot
match an empty IP rule. Unselected headers do not override the configured source.

For Cloudflare deployments, trust only the verified proxy networks actually
connecting to your origin and select `HTTP_CF_CONNECTING_IP`, or configure client
IP restoration in the web server. This also applies to the account Worker's PHP
fallback; sending a client-IP header alone does not make it trusted by this plugin.
IP rules are not reliable if the proxy trust list or ingress header handling is
misconfigured. Verify both a proxied request and a direct request with forged
headers before relying on IP restrictions.

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
