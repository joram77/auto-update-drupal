# Auto update Drupal without composer while keeping htaccess additions
Introduction
----
Auto update a Drupal site without composer in a given path. Currently via Drush 8. 

 Solves the need of automatically automatically updating Drupal sites without composer, with backup, while merging htaccess &amp; robots.txt modifications after updates.



 Tested with Drupal 7-8, *nix OS, PHP 7.3

Usage
---
### Auto updating a Drupal site
Schedule via crontab following PHP script:
For example, every day at midnight, run auto update for your site:

```crontab -e```
Add following line;

```0 0 * * * php -f /path_to_auto_update_drupal/update.php```



### About retaining your .htaccess modifications after automatic Drupal updates
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
