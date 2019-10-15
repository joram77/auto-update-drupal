<?php

namespace be\wethinkonline\AutoUpdateDrupal;
/**
 * Auto update a Drupal site without composer in a given path via Drush 8.
 *
 *
 * Solves the need of automatically automatically updating Drupal sites without composer, with backup,
 * while merging htaccess & robots.txt modifications after updates.
 *
 *  ©© wethinkonline.be 2019
 *
 * License: GNU Lesser General Public License version 3
 *          see file license.txt
 *
 * Tested with Drupal 7-8, *nix OS, PHP 7.3
 *
 * Preserves .htacces modifications with one of the following strategies.
 * $htaccessStrategy:
 * - customBlock
 *    Retain a custom block of rules on top of the htaccess delimited by #custom [your multiline custom htaccess rules...] #/custom
 *    (Advantage over patchfile is that site developers can use a simple htaccess format to maintain their changes.
 *    Neither does it not require the generation of the patch files on changing custom rules.)
 *
 *    example .htaccess with such a custom block on top:
 *
 * #custom
 * <IfModule mod_rewrite.c>
 * RewriteEngine On
 * RewriteRule ^attachments/(.*) /sites/default/files/attachments/$1 [L]
 * </IfModule>
 * #/custom
 *
 * #
 * # Apache/PHP/Drupal settings:
 * #
 * ...
 *
 * - patchFile (not implemented)
 *    Patch the htaccess with a .patch file containing your modifications on top of core .htaccess.
 *
 * Requires:
 * - php exec permissions (allowed_functions php.ini)
 * - write access to $siteDirPath
 * - $drushPath pointing to correct drush.phar for your drupal version
 *

 */
class AutoUpdateDrupal
{
    private $siteDirPath;
    private $versionFilePath;
    private $postUpdateCmd;
    private $customHtaccessRulesBeforeUpdate;

    private $fileHashesBeforeUpdate = [];
    private $fileHashesAfterUpdate = [];

    private $htaccessStrategy = 'customBlock';
    private $customHtaccessEndDelimiter;
    private $customHtaccessStartDelimiter;
    private $drushPath;
    private $flagBackupSite = true;


    function __construct($siteDirPath, $drushPath = 'drush')
    {
        $this->setCustomHtaccessStartDelimiter(null);
        $this->setCustomHtaccessEndDelimiter(null);
        $this->siteDirPath = rtrim($siteDirPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR ;
        $this->drushPath = $drushPath;
        $this->versionFilePath = $siteDirPath . 'core/lib/Drupal.php';
    }

    /**
     * Update drupal & modules in $this->>siteDirPath via Drush 8
     */
    function updateDrupal()
    {
        $cdCommand = "cd {$this->siteDirPath} && ";
        if ($this->flagBackupSite) $this->backupSiteAndDb();

        $flagCustomHtaccessBlockDetected = $this->extractCustomHtaccessRules();
        $cmd = "$cdCommand {$this->drushPath} -y pm-update";
        echo "Executing update...\n";
        exec($cmd, $output);

        if ($flagCustomHtaccessBlockDetected) $this->reinsertCustomHtaccessRules();

        exec("{$cdCommand}{$this->postUpdateCmd}", $output);
    }

    // Backup full site dir & db before update. pm-update/ Pm-updatecode only contains funtionality to back up Drupal core files.
    function backupSiteAndDb()
    {
        $cdCommand = "cd {$this->siteDirPath} && ";
        echo "Making backups... \n";
        $dateTime = date('Y-m-d_h-i-s');
        mkdir('/var/backups/site-backup_' . $dateTime . '/');
        exec("$cdCommand {$this->drushPath} -y  sql-dump > /var/backups/site-backup_" . $dateTime . '/database.sql');
        exec("cp -R {$this->siteDirPath}" . ' /var/backups/site-backup_' . $dateTime . '/site/');

    }

    function setPostCommitCmd($c)
    {
        $this->postUpdateCmd = $c;
    }

    function extractVersion()
    {
        require_once($this->versionFilePath);
        return Drupal::VERSION;
    }

    /**
     * Split htaccess contents of $siteDirPath in array containing
     * 0 -> full pattern match if succeeded
     * 1 -> custom block on top of file delimited by #custom ... #/custom or other separators
     * 2 - > htaccess rules Drupal
     */
    function splitHtaccess()
    {
        $htaccess = file_get_contents($this->siteDirPath . '../../.htaccess');
        $splitPattern = '|' . preg_quote($this->customHtaccessStartDelimiter) . '(.*?)' . preg_quote($this->customHtaccessEndDelimiter) . '.(.*?)$|s';
        preg_match_all($splitPattern, $htaccess, $aHtaccessSplitCustom);
        return $aHtaccessSplitCustom;
    }

    function extractCustomHtaccessRules()
    {
        $aHtaccessSplitCustom = $this->splitHtaccess();
        if (count($aHtaccessSplitCustom[0]) === 1) { // one full pattern match
            $this->customHtaccessRulesBeforeUpdate = $aHtaccessSplitCustom[1][0];
            echo "The custom htaccess rules before update are: \n\n";
            echo str_repeat('-', 70) . "\n";
            echo $this->customHtaccessRulesBeforeUpdate;
            echo str_repeat('-', 70) . "\n";
        } else if (count($aHtaccessSplitCustom[0]) === 0) { // no full pattern match, no custom block detected
            $this->customHtaccessRulesBeforeUpdate = "";
            echo "No custom htaccess rules detected before update.\n";
        } else {
            error_log("Incorrect .htaccess format, more than one #custom ... #/custom or incorrect block encountered. Invalid state.\n");
            $this->customHtaccessRulesBeforeUpdate = null;
            return false;
        }


        $htaccessFilename = $this->siteDirPath . '../../.htaccess';
        $this->fileHashesBeforeUpdate[$htaccessFilename] = md5_file($htaccessFilename);

        return true;
    }

    /**
     *    extract existing .htaccess modifications before reading, if drupal drush update action has not rewritten the htaccess file
     * .htaccess custom blockgoes on top of htaccess file delimited by #custom ....(.custom.htaccess contents) #/custom
     */
    function reinsertCustomHtaccessRules()
    {
        //clearstatcache(true,$this->siteDirPath . '../../.htaccess' );
        $htaccessFilename = $this->siteDirPath . '../../.htaccess';
        $this->fileHashesAfterUpdate[$htaccessFilename] = md5_file($htaccessFilename);

        if ($this->fileHashesBeforeUpdate[$htaccessFilename] != $this->fileHashesAfterUpdate[$htaccessFilename]) {
            if ($this->customHtaccessRulesBeforeUpdate === '') {
                $customRules = "";
                echo "No custom htaccess rules detected before update. Thus not overwriting htaccess file after update.\n";
            } else {
                echo "Reinserting custom rules from before update in the newly updated htaccess file...";
                $customRules = $this->customHtaccessStartDelimiter . "" . $this->customHtaccessRulesBeforeUpdate . "" . $this->customHtaccessEndDelimiter . "\n";

                $htaccessAfterUpdate = file_get_contents($this->siteDirPath . '../../.htaccess');
                $htaccessWithCustomBlock = $customRules . $htaccessAfterUpdate;

                echo "\n\nthe merged htaccess after update will look like \n\n";
                echo str_repeat('-', 70) . "\n";
                echo substr($htaccessWithCustomBlock, 0, 200) . "\n\n...\n (TRUNCATED)\n";
                echo str_repeat('-', 70) . "\n";

                echo "Overwriting htaccess file with custom block from original htaccess on top...\n";
                $fp = fopen($this->siteDirPath . '../../.htaccess', 'w');
                fwrite($fp, $htaccessWithCustomBlock);
                fclose($fp);
            }
        } else {
            echo "Not overwriting htacces because hash .htacces after update {$this->fileHashesAfterUpdate[$htaccessFilename]} is not different than before update: {$this->fileHashesBeforeUpdate[$htaccessFilename]}";
        }
    }

    function getDrushPath()
    {
        return $this->drushPath;
    }

    function setCustomHtaccessStartDelimiter($v)
    {
        if (empty($v)) {
            $this->customHtaccessStartDelimiter = '#custom';
        } else {
            $this->customHtaccessStartDelimiter = $v;
        }
    }

    function setCustomHtaccessEndDelimiter($v)
    {
        if (empty($v)) {
            $this->customHtaccessEndDelimiter = '#/custom';
        } else {
            $this->customHtaccessEndDelimiter = $v;
        }
    }

    /**
     * @param bool $flagBackupSite
     */
    public function setFlagBackupSite(bool $flagBackupSite): void
    {
        $this->flagBackupSite = $flagBackupSite;
    }

    //todo
    function installCronJob()
    {

    }
}

