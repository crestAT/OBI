<?php
/*
	onebuttoninstaller.php
	
    Copyright (c) 2015 - 2016 Andreas Schmidhuber
    All rights reserved.

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice, this
       list of conditions and the following disclaimer.
    2. Redistributions in binary form must reproduce the above copyright notice,
       this list of conditions and the following disclaimer in the documentation
       and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
    ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
    WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
    DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
    ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
    LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
    ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
    (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

    The views and conclusions contained in the software and documentation are those
    of the authors and should not be interpreted as representing official policies,
    either expressed or implied, of the FreeBSD Project.
*/
if (is_file("/usr/local/www/bar_left.gif")) $image_path = '';
else $image_path = 'images/';

require("auth.inc");
require("guiconfig.inc");
if (!isset($config['onebuttoninstaller']['enable'])) header("Location:onebuttoninstaller-config.php");

bindtextdomain("nas4free", "/usr/local/share/locale-obi");
$pgtitle = array(gettext("Extensions"), gettext("OneButtonInstaller")." ".$config['onebuttoninstaller']['version']);

$log = 0;
$loginfo = array(
    array(
    	"visible" => TRUE,
    	"desc" => gettext("Extensions"),
    	"logfile" => "{$config['onebuttoninstaller']['rootfolder']}extensions.txt",
    	"filename" => "extensions.txt",
    	"type" => "plain",
		"pattern" => "/^(.*)###(.*)###(.*)###(.*)###(.*)###(.*)###(.*)$/",
    	"columns" => array(
    		array("title" => gettext("Extensions"), "class" => "listlr", "param" => "align=\"left\"  valign=\"middle\" nowrap", "pmid" => 0),
    		array("title" => gettext("Version"), "class" => "listr", "param" => "align=\"center\" valign=\"middle\"", "pmid" => 1),
    		array("title" => gettext("Description"), "class" => "listr", "param" => "align=\"left\" valign=\"middle\"", "pmid" => 5),
    		array("title" => gettext("Install"), "class" => "listr", "param" => "align=\"center\" valign=\"middle\"", "pmid" => 4)
    	))
);

function log_get_contents($logfile) {
	$content = array();
    if (is_file($logfile)) exec("cat {$logfile}", $extensions);
    else return;
    $content = $extensions;    
	return $content;
}

function log_display($loginfo) {
    global $g;
    global $config;
    global $savemsg;
    global $image_path;
    
	if (!is_array($loginfo)) return;

	// Create table header
	echo "<tr>";
	foreach ($loginfo['columns'] as $columnk => $columnv) {
		echo "<td {$columnv['param']} class='" . (($columnk == 0) ? "listhdrlr" : "listhdrr") . "'>".htmlspecialchars($columnv['title'])."</td>\n";
	}
	echo "</tr>";

	// Get file content
	$content = log_get_contents($loginfo['logfile']);
	if (empty($content)) return;
    sort($content);

    $j = 0;

/*
 * EXTENSIONS.TXT format description: PARAMETER DELIMITER -> ###
 *                      PMID    COMMENT
 * name:                0       extension name
 * version:             1       extension version (base for config entry - could change for newer versions)
 * xmlstring:           2       config.xml or installation directory
 * command(list)1:      3       execution of SHELL commands / scripts (e.g. download installer, untar, chmod, ...)
 * command(list)2:      4       empty ("-") or PHP script name (file MUST exist)
 * description:         5       plain text which can include HTML tags
 * unsupported          6       unsupported architecture, plattform
 */

    // Create table data
	foreach ($content as $contentv) {                                   // handle each line => one extension
		unset($result);
        $result = explode("###", $contentv);                            // retrieve extension content (pmid based) 
		if ((FALSE === $result) || (0 == $result)) continue;
		echo "<tr valign=\"top\">\n";
		for ($i = 0; $i < count($loginfo['columns']); $i++) {           // handle pmids (columns)
            if ($i == count($loginfo['columns']) - 1) {
                // check if current architecture, plattform is supported
                // architectures:  x86, x64, rpi
                // platforms:      embedded, full, livecd, liveusb
                if (!empty($result[6]) && ((strpos($result[6], $g['arch']) !== false) || (strpos($result[6], $g['platform']) !== false))) {
                    echo "<td {$loginfo['columns'][$i]['param']} class='{$loginfo['columns'][$i]['class']}'> <img src='{$image_path}status_disabled.png' border='0' alt='' title='".gettext('Unsupported architecture/platform').': '.$result[6]."' /></td>\n";
                }
                else {
                    // check if extension is already installed (existing config.xml entry or, for command line tools, based on installation directory)
                    if ((isset($config[$result[2]])) || ((strpos($result[2], "/") == 0) && (is_dir("{$config['onebuttoninstaller']['storage_path']}{$result[2]}")))){ 
                        echo "<td {$loginfo['columns'][$i]['param']} class='{$loginfo['columns'][$i]['class']}'> <img src='{$image_path}status_enabled.png' border='0' alt='' title='".gettext('Enabled')."' /></td>\n";                    
                    }  
                    else {                                                  // data for installation
                        echo "<td {$loginfo['columns'][$i]['param']} class='{$loginfo['columns'][$i]['class']}' title='".gettext('Select to install')."' > 
                            <input type='checkbox' name='name[".$j."][extension]' value='".$result[2]."' />
                            <input type='hidden' name='name[".$j."][truename]' value='".$result[0]."' />
                            <input type='hidden' name='name[".$j."][command1]' value='".$result[3]."' />
                            <input type='hidden' name='name[".$j."][command2]' value='".$result[4]."' />
                        </td>\n"; 
                    }   // EOinstallation
                }   // EOsupported
            }   // EOcount
            else echo "<td {$loginfo['columns'][$i]['param']} class='{$loginfo['columns'][$i]['class']}'>" . $result[$loginfo['columns'][$i]['pmid']] . "</td>\n";
        }   // EOcolumns
		echo "</tr>\n";
		$j++;
	}
}

if (isset($_POST['install'], $_POST['name'])) {
    foreach($_POST['name'] as $line) {
        if (isset($line['extension'])) {
            $savemsg .= gettext("Installation").": <b>{$line['truename']}</b>"."<br />";
            unset($result);
            exec("cd {$config["onebuttoninstaller"]["storage_path"]} && {$line['command1']}", $result, $return_val);
            if ($return_val == 0) {
            	foreach ($result as $msg) $savemsg .= $msg."<br />";    // output on success
                unset($result);
                if ("{$line['command2']}" != "-") {                     // check if a PHP script must be executed
                    if (file_exists("{$config["onebuttoninstaller"]["storage_path"]}/{$line['command2']}")) {
                        $savemsg_old = $savemsg;                        // save messages for use after output buffering ends
                        ob_start();                                     // start output buffering
                        include("{$config['onebuttoninstaller']['storage_path']}/{$line['command2']}");
                        $ausgabe = ob_get_contents();                   // get outputs from include command
                        ob_end_clean();                                 // close output buffering 
                        $savemsg = $savemsg_old;                        // recover saved messages ...
                        $savemsg .= str_replace("\n", "<br />", $ausgabe)."<br />";     // ... and append messages from include command
                    }
                    else $errormsg .= sprintf(gettext("PHP script %s not found!"), "{$config["onebuttoninstaller"]["storage_path"]}/{$line['command2']}")."<br />";
                }
            }   // EOcommand1 OK
            else {                                                     // throw error message for command1
                $errormsg .= gettext("Installation error").": <b>{$line['truename']}</b>"."<br />";
            	foreach ($result as $msg) $errormsg .= $msg."<br />";
            }   // EOcommand1 NOK  
        }   // EOisset line
    }   // EOforeach
}   // EOinstall

if (isset($_POST['update']) || (isset($config['onebuttoninstaller']['auto_update']) && !isset($_POST['install']))) {
    $return_val = mwexec("fetch -o {$config['onebuttoninstaller']['rootfolder']}extensions.txt https://raw.github.com/crestAT/nas4free-onebuttoninstaller/master/onebuttoninstaller/extensions.txt", true);
    if ($return_val == 0) $savemsg .= gettext("New extensions list successfully downloaded!")."<br />";
    else $errormsg .= gettext("Unable to retrieve extensions list from server!")."<br />";
}   // EOupdate

if (!is_file("{$config['onebuttoninstaller']['rootfolder']}extensions.txt")) $errormsg .= sprintf(gettext("File %s not found!"), "{$config['onebuttoninstaller']['rootfolder']}extensions.txt")."<br />";

bindtextdomain("nas4free", "/usr/local/share/locale");                  // to get the right main menu language
include("fbegin.inc");
bindtextdomain("nas4free", "/usr/local/share/locale-obi"); ?>
<!-- The Spinner Elements -->
<?php include("ext/onebuttoninstaller/spinner.inc");?>
<script src="ext/onebuttoninstaller/spin.min.js"></script>
<!-- use: onsubmit="spinner()" within the form tag -->

<form action="onebuttoninstaller.php" method="post" name="iform" id="iform" onsubmit="spinner()">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
    	<tr><td class="tabnavtbl">
    		<ul id="tabnav">
    			<li class="tabact"><a href="onebuttoninstaller.php"><span><?=gettext("Install");?></span></a></li>
    			<li class="tabinact"><a href="onebuttoninstaller-config.php"><span><?=gettext("Configuration");?></span></a></li>
    			<li class="tabinact"><a href="onebuttoninstaller-update_extension.php"><span><?=gettext("Maintenance");?></span></a></li>
    		</ul>
    	</td></tr>
    	<tr><td class="tabcont">
            <?php if (!empty($savemsg)) print_info_box($savemsg);?>
            <?php if (!empty($errormsg)) print_error_box($errormsg);?>
    		<table width="100%" border="0" cellpadding="0" cellspacing="0">
                <?php 
                    log_display($loginfo[$log]);
                ?>
    		</table>
            <div id="submit">
                <input name="install" type="submit" class="formbtn" title="<?=gettext("Install extensions");?>" value="<?=gettext("Install");?>" onclick="return confirm('<?=gettext("Ready to install the selected extensions?");?>')" />
                <input name="update" type="submit" class="formbtn" title="<?=gettext("Update extensions list");?>" value="<?=gettext("Update");?>" />
            </div>
    		<?php include("formend.inc");?>
         </td></tr>
    </table>
</form>
<?php include("fend.inc");?>
