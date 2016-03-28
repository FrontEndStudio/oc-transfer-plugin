# October CMS Transfer plugin

## About

When migrating from [CMS Made Simple](http://cmsmadesimple.org) to [October CMS](http://octobercms.com) you will find out that database table and column names do not match.

This plugin contains some artisan commands to transfer data from the CMS Made Simple database to October CMS.

## Implemented are:

CMSMS News -> RainLab Blog \\
CMSMS Gallery -> Fes Album

## How to use

- Under your October CMS plugins directory create a directory called: fes/transfer
- Checkout this repository
- Run: ~ composer install

- Under: config/database.php add a new database configuration named 'cmsms'. This database contains CMS Made Simple data be sure to have read access from the location your run this plugin.

```
    'cmsms' => [
      'driver'    => 'mysql',
      'host'      => '',
      'port'      => 3306,
      'database'  => '',
      'username'  => '',
      'password'  => '',
      'charset'   => 'utf8',
      'collation' => 'utf8_unicode_ci',
      'prefix'    => '',
    ]
```

## Commands

# list available parameters

```
php artisan transfer:cmsms-data --help
```

*! the limit parameter is not yet implemented*

# transfer all data

```
php artisan transfer:cmsms-data
```

# transfer by type

```
php artisan transfer:cmsms-data --type=news
php artisan transfer:cmsms-data --type=news-category
php artisan transfer:cmsms-data --type=gallery
```

## Dependencies

To convert WYSIWYG (html) data to markdown, the [html-to-markdown](https://github.com/thephpleague/html-to-markdown) libary is used.


