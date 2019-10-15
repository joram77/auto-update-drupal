<?php

namespace be\wethinkonline\AutoUpdateDrupal\Tests;
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'AutoUpdateDrupal.php');

use be\wethinkonline\AutoUpdateDrupal\AutoUpdateDrupal;
use PHPUnit\Framework\TestCase;
use PHPUnit\Assert;

/**
 * Requires
 * - allow_url_fopen PHP ini setting TRUE
 * - sqlite php module, eg binaries for Debian via apt-get install php7.3-sqlite3 AND apt-get install sqlite3 binaries
 * - drush.phar or drush commands on your system PATH variable
 */
class AutoUpdateDrupalTest extends TestCase
{
    private $testSitesDirPath; // writable directory w. trailing / to write Drupal PHP files, sqlite db, test site files
    private $drushPath;

    function setUp(): void
    {
        $testSitesDirPath = '/tmp/';
        $drushPath = 'drush';
        $this->testSitesDirPath = $testSitesDirPath;
        $this->drushPath = $drushPath;

    }

    static function create($testSitesDirPath, $drushPath)
    {
        $o = new self();
        $o->testSitesDirPath = $testSitesDirPath;
        $o->drushPath = $drushPath;
        return $o;
    }

    function testChangedHtaccessAfterUpdate()
    {
        $tempSiteDir = $this->testSitesDirPath . date('Ymdhis') . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR;

        exec('mkdir -p ' . $tempSiteDir);
        $sampleHtaccessCustomRules = <<<LBL
        #custom
        <IfModule mod_rewrite.c>
           RewriteEngine On
           RewriteRule ^attachments/(.*) /sites/default/files/attachments/$1 [L]
        </IfModule>
        #/custom
        
        #
        # Apache/PHP/Drupal settings:
        #...
        LBL;
        $fileName = $tempSiteDir . '../../.htaccess';
        // create sample htaccess w custom rules
        $fp = fopen($fileName, 'w');
        fwrite($fp, $sampleHtaccessCustomRules);
        fclose($fp);

        $u = new AutoUpdateDrupal($tempSiteDir, $this->drushPath);
        $r = $u->extractCustomHtaccessRules();
        $freshHtaccess = file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'data/fresh_htaccess.txt',false);

        $fileName = $tempSiteDir . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '.htaccess';
        $fp = fopen($fileName, 'w');
        fwrite($fp, $freshHtaccess);
        fclose($fp);

        if ($r) $u->reinsertCustomHtaccessRules();
        $this->assertStringContainsString('Overwriting htaccess', ob_get_contents());

    }

    function testUpdate8_7_7To8_7_8NoCustomHtaccessRules()
    {
        $drushPath = $this->drushPath;

        $cmd = <<<LBL
             cd {$this->testSitesDirPath} && 
            {$drushPath} qd --profile=minimal --no-server --cache --core=drupal-8.7.7 --yes 
        LBL;
        exec($cmd, $out);

        preg_match('|' . preg_quote('quick-drupal-') . '[0-9]*' . '/drupal-.*\..*\..*/sites/default' . '.|', $out[0], $aSitePathMatches);
        $installDir = $this->testSitesDirPath . $aSitePathMatches[0];
        echo "install dir is $installDir\n";

        $u = new AutoUpdateDrupal($installDir, $drushPath);
        $u->setFlagBackupSite(false);
        $u->updateDrupal();

        exec("cd $installDir && {$drushPath} status", $out2);
        $versionArray = explode(':', $out2[0]);
        $drupalVersionAfterUpdate = trim($versionArray[1]);
        $this->assertEquals('8.7.8', $drupalVersionAfterUpdate);
    }

    function testUpdate7_43Todrupal7_67NoCustomHtaccessRules()
    {
        $drushPath = $this->drushPath;

        $cmd = <<<LBL
             cd {$this->testSitesDirPath} && 
            {$drushPath} qd --profile=minimal --no-server --cache --core=drupal-7_43 --yes 
        LBL;
        exec($cmd, $out);

        preg_match('|' . preg_quote('quick-drupal-') . '[0-9]*' . '/drupal-.*/sites/default' . '.|', $out[0], $aSitePathMatches);
        $installDir = $this->testSitesDirPath . $aSitePathMatches[0];
        echo "install dir is $installDir\n";

        $u = new AutoUpdateDrupal($installDir, $drushPath);
        $u->setFlagBackupSite(false);
        $u->updateDrupal();

        exec("cd $installDir && {$drushPath} status", $out2);
        $versionArray = explode(':', $out2[0]);
        $drupalVersionAfterUpdate = trim($versionArray[1]);
        $this->assertEquals('7.67', $drupalVersionAfterUpdate);

    }


}
