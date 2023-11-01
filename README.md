<p align="center">
  <img src="https://vvveb.com/admin/default/img/biglogo.png" alt="Vvveb">
  <br><br>
  <strong>Powerful and easy to use CMS with page builder to build websites, blogs or ecommerce stores.</strong>
</p>
<p align="center">
  <a href="https://www.vvveb.com">Website</a> |
  <a href="https://docs.vvveb.com/">Documentation</a> |
  <a href="https://github.com/givanz/Vvveb/discussions">Forum</a> |
  <a href="https://twitter.com/vvvebcms">Twitter</a> 
</p>

### [Live Demo](https://demo.vvveb.com) / [Admin Demo](https://demo.vvveb.com/admin) / [Page Builder Demo](https://demo.vvveb.com/admin/?module=/editor/editor&template=index.html&url=/)

[![](https://www.vvveb.com/img/light-theme.png)](https://www.vvveb.com/img/light-theme.png)

| [![](https://www.vvveb.com/img/dark-theme.png)](https://www.vvveb.com/img/dark-theme.png) | [![](https://www.vvveb.com/img/light-theme.png)](https://www.vvveb.com/img/light-theme.png) | [![](https://www.vvveb.com/vvveb-admin/dashboard-light.png)](https://www.vvveb.com/img/dashboard-white.png) | [![](https://www.vvveb.com/vvveb-admin/dashboard-dark.png)](https://www.vvveb.com/vvveb-admin/dashboard-dark.png) |
|:---:|:---:|:---:|:---:|
| **Editor dark** | **Editor light** | **Dashboard light** | **Dashboard dark** |
| [![](https://www.vvveb.com/vvveb-admin/post-light.png)](https://www.vvveb.com/vvveb-admin/post-light.png) | [![](https://www.vvveb.com/vvveb-admin/post-dark.png)](https://www.vvveb.com/vvveb-admin/post-dark.png) | [![](https://www.vvveb.com/vvveb-admin/product-light.png)](https://www.vvveb.com/vvveb-admin/product-light.png) | [![](https://www.vvveb.com/vvveb-admin/product-dark.png)](https://www.vvveb.com/vvveb-admin/product-dark.png) |
| **Post**  | **Post dark** | **Product**  | **Product dark** |
| [![](https://www.vvveb.com/themes/landing/screens/home.png)](https://www.vvveb.com/themes/landing/screens/home.png) | [![](https://www.vvveb.com/themes/landing/screens/home-dark.png)](https://www.vvveb.com/themes/landing/screens/home-dark.png) | [![](https://www.vvveb.com/themes/landing/screens/blog.png)](https://www.vvveb.com/themes/landing/screens/blog.png) | [![](https://www.vvveb.com/themes/landing/screens/blog-dark.png)](https://www.vvveb.com/themes/landing/screens/blog-dark.png) |
| **Home** | **Home dark** | **Blog** | **Blog dark** |
| [![](https://www.vvveb.com/themes/landing/screens/shop.png)](https://www.vvveb.com/themes/landing/screens/shop.png) | [![](https://www.vvveb.com/themes/landing/screens/shop-dark.png)](https://www.vvveb.com/themes/landing/screens/shop-dark.png) | [![](https://www.vvveb.com/themes/landing/screens/product.png)](https://www.vvveb.com/themes/landing/screens/product.png) | [![](https://www.vvveb.com/themes/landing/screens/product-dark.png)](https://www.vvveb.com/themes/landing/screens/product-dark.png) |
| **Shop**  | **Shop dark** | **Product**  | **Product dark** |

### Features

* Drag and drop page builder
* Multi site support
* Localization and multi language support
* Easy publishing with revisions, media management, multi user access.
* Advanced ecommerce features
	* One page checkout
	* Subscriptions
	* Digital assets support
	* Vouchers, coupons
	* Product options, attributes, variants, reviews, qa etc
* Themes and plugins marketplace with one click install from admin dashboard.
* Flexible with custom fields, custom posts and custom products support.
* Manual and automatic backup.
* Import/export for easy migration.
* Easily extendable through plugins with a powerful event system.
* Built in contact forms plugin with storage and email support.
* Very fast, with cache enabled as fast as a static website.
* Low resource footprint serving hundreds of requests per second on free shared hosting.

## System Requirements

* [PHP](https://www.php.net) minimum PHP 7.4+, recommended PHP 8.3+ with the following extensions:
	* mysqli or sqlite3 or pgsql, xml, pcre, zip, dom, curl, gettext, gd or imagick
* Database 
	* [MySQL 5.7+](https://www.mysql.com/) or greater OR [MariaDB](https://mariadb.org/) 10.2 or greater. 
	* [Sqlite](https://www.sqlite.com/) 
	* [Postgresql 11+](https://www.postgresql.org/) 


## Install

* Clone the repository or download the latest [release](https://vvveb.com/download.php)
* Upload the files on your server or localhost and open `http://localhost/` or `http://myserver.com/` 
* Follow the [installation instructions](https://docs.vvveb.com/installation)

###

Command line install

```bash
php cli.php install module=index host=127.0.0.1 user=root password=1234 database=vvveb admin[email]=admin@vvveb.com admin[password]=admin engine=mysqli
```

Replace engine with `sqlite` or `pgsql` if you like.


## Documentation

[User documentation](https://docs.vvveb.com)

[Developer documentation](https://dev.vvveb.com)

## Submodules

Parts of the project have their own repositories that can be used independently of the rest of the project.

 * Admin theme [Vvveb Admin Bootstrap 5 Template](https://github.com/givanz/vvveb-admin-template/ )
 * Default frontend theme [Landing Boostrap 5 template](https://github.com/givanz/landing/)
 * Default blog theme [Minimal Blog Bootstrap 5 template](https://github.com/givanz/blog-default)
 * Page builder [VvvebJs Drag and drop website builder javascript library](https://github.com/givanz/VvvebJs)


## Support

If you like the project you can support it with a [PayPal donation](https://paypal.me/zgivan) or become a backer/sponsor via [Open Collective](https://opencollective.com/vvvebjs)


<a href="https://opencollective.com/vvvebjs/sponsors/0/website"><img src="https://opencollective.com/vvvebjs/sponsors/0/avatar"></a>
<a href="https://opencollective.com/vvvebjs/backers/0/website"><img src="https://opencollective.com/vvvebjs/backers/0/avatar"></a>

## License

GNU Affero General Public License Version 3 (AGPLv3) or any later version

