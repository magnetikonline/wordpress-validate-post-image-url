# WordPress validate post image source
PHP CLI script to trawl all published posts of a WordPress blog database and ensure that all referenced internally served images are linked up correctly, logging anything amiss to a file for further analysis/fixup. It is to be run from the same server/location where the images themselves are kept.

It's rather down and dirty, but does the job.

## Requires
- PHP 5.5
- [MySQLi](http://php.net/mysqli)

## How it works
- Opens the `wp_posts` table and queries all published posts for their text content.
- Works over each in turn, using a regular expression to extract all image references that are internal to the blog.
- Confirms that each image exists on disk under the `wp-content/uploads/` directory (or equivalent depending on WordPress blog configuration) using the [`is_file()`](http://php.net/manual/en/function.is-file.php) function.
- Any image that does not exist on disk is then logged to an error file.

## Usage
Configure the constants at the top of [`wordpressvalidatepostimgsrc.php`](wordpressvalidatepostimgsrc.php) to suit. Details of each setting are as follows:

<table>
	<tr>
		<td>DB_SERVER</td>
		<td>Hostname/socket of the MySQL database. Typically would be <code>localhost</code>.</td>
	</tr>
	<tr>
		<td>DB_USER</td>
		<td>MySQL database login user.</td>
	</tr>
	<tr>
		<td>DB_PASSWORD</td>
		<td>MySQL database login password.</td>
	</tr>
	<tr>
		<td>DB_DATABASE</td>
		<td>MySQL database name containing WordPress blog tables.</td>
	</tr>
	<tr>
		<td>PUBLIC_SITE_UPLOADS_URL</td>
		<td>Full public URL path to the <code>wp-content/uploads/</code> folder, including trailing forward slash. This is used to construct the regular expression for extracting internal blog post image references.</td>
	</tr>
	<tr>
		<td>PATH_TO_UPLOADS</td>
		<td>Full/relative (to script) path containing all WordPress blog images, with trailing slash.</td>
	</tr>
	<tr>
		<td>LOG_FILE_PATH</td>
		<td>Log file name used to output missing blog post images detected.</td>
	</tr>
</table>

Now execute the script from the destination WordPress blog server/location via CLI. It's not a good idea to run via a web server request, this could take some time.

```sh
$ php wordpressvalidatepostimgsrc.php
```

If everything works as expected, detected missing images will be written to `LOG_FILE_PATH` for analysis.

## Example error log output

```
http://www.siteurl.com/the-guid-of-my-blog-post
	http://www.siteurl.com/wp-content/uploads/2014/02/missing-image.jpg
	http://www.siteurl.com/wp-content/uploads/2014/02/bad-image.jpg
	http://www.siteurl.com/wp-content/uploads/2014/02/cant-find-this-image.jpg

http://www.siteurl.com/a-second-blog-post-guid
	http://www.siteurl.com/wp-content/uploads/2012/06/missing-image.png
	http://www.siteurl.com/wp-content/uploads/2012/06/bad-image.gif
	http://www.siteurl.com/wp-content/uploads/2012/06/cant-find-this-image.jpg
```
