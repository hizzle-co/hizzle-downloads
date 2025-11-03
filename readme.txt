=== Simple Download Manager - Hizzle Downloads ===
Contributors: picocodes, mutendebrian
Tags: files, downloads, digital downloads, download manager, restrict downloads
Requires at least: 4.9
Tested up to: 6.7
Requires PHP: 5.6
Version: 1.2.2
Stable tag: 1.2.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Donate link: https://noptin.com/products/?utm_source=wp-repo&utm_medium=donate&utm_campaign=readme

Easily add, restrict, and track digital downloads in WordPress — protect files with passwords, user roles, IPs, or subscriber access.

== Description ==

**A simple WordPress download manager for secure file sharing, access control, and download tracking — perfect for digital products.**
★★★★★<br>

Do you need a simple yet powerful way to manage file downloads on your WordPress site? This plugin makes it easy to upload, organize, and control access to downloadable files of any type. Whether you are sharing free resources, selling digital products, or delivering private documents, this plugin gives you full control over who can download your files and when.

With unlimited downloads, flexible restrictions, and detailed tracking, you can confidently provide files to your audience while keeping them secure.

= Key Features =

- **Add unlimited downloadable files** – Add and manage as many downloadable files as you need, with no limits.
- **Password Protection** – Protect individual files with custom passwords so only authorized users can access them.
- **Restrict downloads to specific user roles** – Control file access based on WordPress user roles, ensuring that only administrators, editors, subscribers, or custom roles can download.
- **Restrict downloads to specific IP addresses** – Restrict downloads to specific IP addresses to prevent abuse or unauthorized sharing.
- **Restrict downloads to specific users** – Assign downloads to specific registered users for secure, private file delivery.
- **Restrict downloads to newsletter subscribers** – Restrict downloads to Noptin newsletter subscriber, making it an excellent tool for lead generation.
- **Track file downloads** – Track every file download with detailed statistics, helping you understand how your files are being accessed.
- **Simple Management** – A user-friendly interface makes uploading and managing files straightforward, even for beginners.

= Why Use This Plugin? =

Managing downloads manually in WordPress can be difficult. Links can be shared publicly, access can’t easily be restricted, and tracking is limited. This plugin solves those problems by giving you advanced tools to:

- Protect digital products such as **software, themes, and plugins**.
- Share private **PDF documents, contracts, or reports** securely with clients.
- Provide exclusive resources like **eBooks, whitepapers, and templates** to email subscribers.
- Control access to files for **membership sites and online courses**.
- Monitor and analyze download activity to make better business decisions.

= Benefits for Your Website =

By installing this plugin, you’ll be able to:

- Grow your email list by offering subscriber-only downloads.
- Monetize your website by controlling access to premium resources.
- Increase security by preventing unauthorized downloads and link sharing.
- Gain insights into how your downloads are performing.

Whether you’re a blogger, developer, marketer, educator, or business owner, this plugin gives you all the tools you need to manage file downloads effectively in WordPress.

Take control of your downloads today and provide a seamless, secure experience for your users.

== S3 Sync Configuration ==

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

=== How it works ===

When the S3 credentials are configured:
- Files uploaded to the `wp-content/uploads/hizzle_uploads/` directory are automatically synced to your S3-compatible storage
- Files are organized by hostname (e.g., `my-site.com/path/to/file.zip`)
- The sync happens automatically when downloads are created or updated

=== Cloudflare R2 Example ===

```php
define( 'HIZZLE_DOWNLOADS_S3_ACCESS_KEY', 'your-r2-access-key' );
define( 'HIZZLE_DOWNLOADS_S3_SECRET_KEY', 'your-r2-secret-key' );
define( 'HIZZLE_DOWNLOADS_S3_BUCKET', 'your-bucket-name' );
define( 'HIZZLE_DOWNLOADS_S3_ENDPOINT', 'https://your-account-id.r2.cloudflarestorage.com/your-bucket-name' );
```

=== DigitalOcean Spaces Example ===

```php
define( 'HIZZLE_DOWNLOADS_S3_ACCESS_KEY', 'your-spaces-access-key' );
define( 'HIZZLE_DOWNLOADS_S3_SECRET_KEY', 'your-spaces-secret-key' );
define( 'HIZZLE_DOWNLOADS_S3_BUCKET', 'your-space-name' );
define( 'HIZZLE_DOWNLOADS_S3_ENDPOINT', 'https://your-space-name.nyc3.digitaloceanspaces.com' );
```

== Installation ==

* Go to WordPress Dashboard.
* Click on Plugins -> Add New
* Search form "**Hizzle Downloads**"
* Find the plugin and click on the Install Now button
* After installation, click on Activate Plugin link to activate the plugin.

== Frequently Asked Questions ==

= Can I see how many times each file has been downloaded? =

Yes, Hizzle Downloads allows you to view how many times each file has been downloaded, as well as which users have downloaded the file. This information is available in the Hizzle Downloads dashboard, where you can see a list of all your downloadable files and their download stats. You can also see which users have downloaded the files, and view their information and activity in your mailing list. This can be useful for tracking the popularity of your files and understanding which users are interested in them.

= Can I restrict downloads by user role or newsletter subscription status? =

Yes, Hizzle Downloads allows you to restrict downloads by user role or newsletter subscription status. This means that you can choose which users are able to download your files, based on their user role on your website or their status as a newsletter subscriber. For example, you can make a file available only to users who are registered as members on your website, or only to users who have subscribed to your newsletter. This can be useful for creating exclusive content or offers for your users, and for encouraging people to sign up for your newsletter. You can set these restrictions for each of your downloadable files in the Hizzle Downloads settings.

= How do I display downloadable files? =

Use the `[hizzle-downloads]` shortcode to display all the downloadable files that the current user has access to.

= How can I get in touch? =

Use the [contact form on our website](https://hizzlewp.com/contact/).

= How can I contribute? =

There are a lot of ways to contribute to this plugin:-

* Star the plugin on [GitHub.](https://github.com/hizzle-co/hizzle-downloads/)
* [Clone the plugin,](https://github.com/hizzle-co/hizzle-downloads/) make improvements to the code and send us a [pull request](https://github.com/hizzle-co/hizzle-downloads/pulls) so that we can share your improvements with the world.
* Give us a [5* rating](https://wordpress.org/support/plugin/hizzle-downloads/reviews/?filter=5) on WordPress.

= Will this work with my theme? =

Yeah. The downloads list will take your theme's default styling.

== Changelog ==

= 1.2.2 =
- Update composer packages.

= 1.2.1 =
- WordPress 6.8 compatibility.
- Update composer packages.

= 1.2.0 =
- Test on WP 6.7
- Update composer packages.

= 1.1.1 =
- Test on WP 6.6

= 1.1.0 =
- Update composer packages.

= 1.0.9 =
- WordPress 6.5 compatibility.
- Update packages.

= 1.0.8 =
- Update composer packages.

= 1.0.7 =
- Update composer packages.

= 1.0.6 =
- Test on WordPress 6.2.
- Update composer packages.

= 1.0.5 =
- Unable to delete a single downloadable file.

= 1.0.4 =
- Register a software versions REST API route.

= 1.0.3 =
- Restrict downloads to Noptin newsletter subscribers.
- Add support for password protected downloads.
- Automatically update download files when there is a GitHub release.

= 1.0.2 =
- Update composer packages.

= 1.0.1 =
- Rename downloadable files rest controller file name

= 1.0.0 =
- Initial release
