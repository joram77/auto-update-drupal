# Auto update Drupal without composer while keeping htaccess additions
Introduction
----
Auto update a Drupal site without composer in a given path. Currently via Drush 8. 

 Solves the need of automatically automatically updating Drupal sites without composer, with backup, while merging htaccess &amp; robots.txt modifications after updates.

Prerequisites
----
 Tested with Drupal 7-8, *nix OS, PHP 7.3
 - PHP installed 
 - Drush 8 should be on your PATH, you can get the latest version here https://github.com/drush-ops/drush/releases
 !! Make sure you download Drush 8 as drush 9 and above only use Composer!! 
 
 Browse to https://github.com/drush-ops/drush/releases and download the drush.phar attached to the latest 8.x release.
Install drush;
excerpt from http://docs.drush.org/en/8.x/install/ . 
Test your install.  
php drush.phar core-status . 
Rename to `drush` instead of `php drush.phar`. Destination can be anywhere on $PATH.   
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
 
Copyright & licensing
---
  ©© wethinkonline.be 2019

 License: GNU Lesser General Public License version 3
          see file LICENSE
