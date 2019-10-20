# Auto update Drupal without composer while keeping htaccess additions
Introduction
----
Auto update a Drupal site without composer in a given path. Currently via Drush 8.  Solves the need of automatically automatically updating Drupal sites without composer, with backup, while merging htaccess &amp; robots.txt modifications after updates.
*Applicable if your does not rely on composer managed third party libraries in the vendor folder*


Use cases
---
You are a drupal 7/8 site admin and you want to update Drupal (automatically):
- you **do not want to use composer**, common community complaints: learning curve, memory intensive - Composer internally increases the memory_limit to 1.5G !, requires CLI knowledge, open firewall for package sources [1](https://www.drupal.org/forum/support/upgrading-drupal/2017-01-20/drupal-8-maintenance-is-a-terrible-nightmare) [2](https://www.drupal.org/project/ideas/issues/2845379) [3](https://getcomposer.org/doc/articles/troubleshooting.md#memory-limit-errors)
- you have **no modules that rely on libraries that need updates via composer**, or you want to update these libraries manually  via [Ludwig](https://www.drupal.org/project/ludwig) [explanation](https://drupalcommerce.org/blog/49669/installing-commerce-2x-without-composer-ludwig) 
- before upgrade you want a **backup/sql dump**
- you want to **keep htaccess additions** after update

Example use case:
I am a Drupal 8 site admin and my website only uses a single module, the editor_file module.
The editor_file module does not rely on third party libs in the vendor folder. I can use this method to update my site without composer.

How to check for modules that rely on composer?
----
Get a list of your modules that are active in the Drupal GUI. 

Then, check the source code for each the module and look for composer.json.
* eg. https://git.drupalcode.org/project/editor_file
the root does not contain a composer.json. >> Module does not require composer

* eg. https://git.drupalcode.org/project/commerce/tree/8.x-2.x 
the root contains a composer.json. >> >> Module does  require composer



Prerequisites
----
 Tested with Drupal 7-8, *nix OS, PHP 7.3
 - /var/backups folder, writable (todo, we should make this folder configurable)
 - Drush 8 should be on your PATH, you can get the latest version here https://github.com/drush-ops/drush/releases
 !! Make sure you download Drush 8 as drush 9 and above only use Composer!! 
 
Browse to https://github.com/drush-ops/drush/releases and download the drush.phar attached to the latest 8.x release.  
Install drush . 
excerpt from http://docs.drush.org/en/8.x/install/ . 
Test your install . 
php drush.phar core-status .
Rename to `drush` instead of `php drush.phar`. Destination can be anywhere on $PATH . 
chmod +x drush.phar . 
sudo mv drush.phar /usr/local/bin/drush .
 


Usage
---
### Fire up testsite(optional)
If you want to install a testsite with drush qd, you need Pthe HP sqlite extension installed & enabled.  
Let's install a new Drupal site through drush for the sake of testing:
Note the admin user and password displayed in the CLI output.

```drush qd testsite --yes --core=drupal-8.7.7 --no-server```


Note the full path where the script installed the site.

```cd testsite/drupal-8.7.7/```

```pwd```

Fire up the PHP dev server in Drupal root which contains index.PHP.

```php -S localhost:8080```

browse to http://localhost:8080/user
login with the admin user and password
go to http://localhost:8080/admin/reports/updates
You should see drupal core 8.7.7 "Update available"

### Updating your site 
Open a new terminal

Let's try updating with the auto update script as following. The -f argument should point to AutoUpdateDrupal/update.php, the first argument for the PHP script that follows is the path where we installed Drupal, with sites/default or sites/sitename appended.

```php -f [path-to-directory-of-AutoUpdateDrupal]/update.php "/Users/user/testsite/drupal-8.7.7/sites/default/"```

Now refresh http://localhost:8080/admin/reports/updates
You should see drupal core 8.7.8 in a green background when update has succeeded.

### Auto updating a Drupal site
Schedule via crontab following PHP script:
For example, every day at midnight, run auto update for your site:

```crontab -e```
Add following line;

```0 0 * * * php -f "[path-to-directory-of-AutoUpdateDrupal]/update.php" "[path-to-drupal-install]/sites/default/"```



 About retaining your .htaccess modifications after automatic Drupal updates
 ---
Many site owners face the problem that Drush overwrites .htaccess & robots.txt if updates are applied to these files. This auto update script preserves your .htacces additions. With one of the following strategies:

 - customBlock (default):
    Retain a custom block of rules on top of the htaccess delimited by #custom [your multiline custom htaccess rules...] #/custom.

    example .htaccess with such a custom block on top:
```

 #custom
 <IfModule mod_rewrite.c>
 RewriteEngine On
 RewriteRule ^attachments/(.*) /sites/default/files/attachments/$1 [L]
 </IfModule>
 #/custom

 #
 # Apache/PHP/Drupal settings:
 #
 ...
 ```
 - patchFile (not implemented yet)
 

Rationale for not using composer with Drupal 8
---
In Drupal 7 modules that rely on external PHP libraries had to be updated manually by placin them in the module folder itself (eg.a libs subfolder) . Or by using the libraries API placing them in sites/all/libraries or sites/sitename/libraries [D7 libraries docs](https://www.drupal.org/docs/7/modules/libraries-api/installing-an-external-library-that-is-required-by-a-contributed-module)

Since Drupal 8, these tasks have been automated for complex sites, the preferred update method is now [Composer](https://medium.com/@ChandeepKhosa/updating-drupal-8-with-composer-a-step-by-step-tutorial-119caf638bc). For Drupal websites that rely heavily on custom modules and their dependency trees this is excellent. Composer resolves this by updating the libraries centrally in the vendor folder. This means you are required to use this method to update your site instead of manually overwriting the drupal core files in vendor, unless you use Ludwig.

However, if you have a simple site setup (one **without modules that rely on libs**) you may not want to use Composer. It requires a site admin to have knowledge of a development tool, open firewall to various sources..  In this case you may continue updating via the old method. That is, downloading the latest Drupal release, clearing/overwriting the files in vendor either via drush, or getting the [Drupal release tarball](https://www.drupal.org/project/drupal/releases).

Why you MUST use composer or Ludwig when you need external libaries for modules?
*Due to the way Composer works, these libraries can't be manually uploaded to the site's vendor folder. Instead, Composer must be used to download the module, which then pulls in the required libraries. Once Composer is used to manage a single module, it also needs to be used to manage and update Drupal core, since manual Drupal core updates replace the vendor/ folder, removing the downloaded libraries.* [Quote Ludwig project](https://www.drupal.org/project/ludwig)



Copyright & licensing
---
  ©© wethinkonline.be 2019 - https://www.wethinkonline.be

 License: GNU Lesser General Public License version 3
          see file LICENSE
