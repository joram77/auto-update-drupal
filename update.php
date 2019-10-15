<?php
/** Update the drupal site path given in CLI PHP argument 0
 * Example usage: php -f update.php /var/www/mysite/sites/default
 */
use be\wethinkonline\AutoUpdateDrupal\AutoUpdateDrupal;
require_once(__DIR__.'/AutoUpdateDrupal.php');
$up = new AutoUpdateDrupal($argv[1],'drush');
$up->updateDrupal();;