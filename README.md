# hizzle-downloads
Create free or paid downloadable files

## S3 Sync Configuration

The plugin supports automatic syncing of uploaded files to S3-compatible storage services like Amazon S3 and Cloudflare R2.

To enable this feature, define the following constants in your `wp-config.php` file:

```php
// Required constants
define( 'HIZZLE_DOWNLOADS_S3_ENDPOINT', 'https://{$bucket}.s3.{$region}.amazonaws.com' );
define( 'HIZZLE_DOWNLOADS_S3_ACCESS_KEY', 'your-access-key' );
define( 'HIZZLE_DOWNLOADS_S3_SECRET_KEY', 'your-secret-key' );
define( 'HIZZLE_DOWNLOADS_S3_BUCKET', 'your-bucket-name' );

```

### How it works

When the S3 credentials are configured:
- Files uploaded to the `wp-content/uploads/hizzle_uploads/` directory are automatically synced to your S3-compatible storage
- Files are organized by hostname (e.g., `my-site.com/owner/file.zip`)
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