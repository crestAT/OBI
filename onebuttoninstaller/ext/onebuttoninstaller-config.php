<?php
/* 
    onebuttoninstaller-config.php

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
require("auth.inc");
require("guiconfig.inc");

// Dummy standard message gettext calls for xgettext retrieval!!!
$dummy = gettext("The changes have been applied successfully.");
$dummy = gettext("The configuration has been changed.<br />You must apply the changes in order for them to take effect.");
$dummy = gettext("The following input errors were detected");

bindtextdomain("nas4free", "/usr/local/share/locale-obi");
$pgtitle = array(gettext("Extensions"), gettext("OneButtonInstaller")." ".$config['onebuttoninstaller']['version'], gettext("Configuration"));

if (!isset($config['onebuttoninstaller']) || !is_array($config['onebuttoninstaller'])) $config['onebuttoninstaller'] = array();

/* Check if the directory exists, the mountpoint has at least o=rx permissions and
 * set the permission to 775 for the last directory in the path
 */
function change_perms($dir) {
    global $input_errors;

    $path = rtrim($dir,'/');                                            // remove trailing slash
    if (strlen($path) > 1) {
        if (!is_dir($path)) {                                           // check if directory exists
            $input_errors[] = sprintf(gettext("Directory %s doesn't exist!"), $path);
        }
        else {
            $path_check = explode("/", $path);                          // split path to get directory names
            $path_elements = count($path_check);                        // get path depth
            $fp = substr(sprintf('%o', fileperms("/$path_check[1]/$path_check[2]")), -1);   // get mountpoint permissions for others
            if ($fp >= 5) {                                             // transmission needs at least read & search permission at the mountpoint
                $directory = "/$path_check[1]/$path_check[2]";          // set to the mountpoint
                for ($i = 3; $i < $path_elements - 1; $i++) {           // traverse the path and set permissions to rx
                    $directory = $directory."/$path_check[$i]";         // add next level
                    exec("chmod o=+r+x \"$directory\"");                // set permissions to o=+r+x
                }
                $path_elements = $path_elements - 1;
                $directory = $directory."/$path_check[$path_elements]"; // add last level
                exec("chmod 775 {$directory}");                         // set permissions to 775
                exec("chown {$_POST['who']} {$directory}*");
            }
            else
            {
                $input_errors[] = sprintf(gettext("OneButtonInstaller needs at least read & execute permissions at the mount point for directory %s! Set the Read and Execute bits for Others (Access Restrictions | Mode) for the mount point %s (in <a href='disks_mount.php'>Disks | Mount Point | Management</a> or <a href='disks_zfs_dataset.php'>Disks | ZFS | Datasets</a>) and hit Save in order to take them effect."), $path, "/{$path_check[1]}/{$path_check[2]}");
            }
        }
    }
}

if (isset($_POST['save']) && $_POST['save']) {
    unset($input_errors);
	if (empty($input_errors)) {
        $config['onebuttoninstaller']['enable'] = isset($_POST['enable']) ? true : false;
        $config['onebuttoninstaller']['storage_path'] = !empty($_POST['storage_path']) ? $_POST['storage_path'] : $g['media_path'];
        $config['onebuttoninstaller']['storage_path'] = rtrim($config['onebuttoninstaller']['storage_path'],'/');         // ensure to have NO trailing slash
        if (!is_dir($config['onebuttoninstaller']['storage_path'])) mkdir($config['onebuttoninstaller']['storage_path'], 0775, true);
        change_perms($_POST['storage_path']);
        $config['onebuttoninstaller']['auto_update'] = isset($_POST['auto_update']) ? true : false;
        $savemsg .= get_std_save_message(write_config());
    }   // end of empty input_errors
}

$pconfig['enable'] = isset($config['onebuttoninstaller']['enable']) ? true : false;
$pconfig['storage_path'] = !empty($config['onebuttoninstaller']['storage_path']) ? $config['onebuttoninstaller']['storage_path'] : $g['media_path'];
$pconfig['auto_update'] = isset($config['onebuttoninstaller']['auto_update']) ? true : false;

bindtextdomain("nas4free", "/usr/local/share/locale");                  // to get the right main menu language
include("fbegin.inc");
bindtextdomain("nas4free", "/usr/local/share/locale-obi"); ?>
<script type="text/javascript">
<!--
function enable_change(enable_change) {
    var endis = !(document.iform.enable.checked || enable_change);
	document.iform.storage_path.disabled = endis;
	document.iform.storage_pathbrowsebtn.disabled = endis;
	document.iform.auto_update.disabled = endis;
}
//-->
</script>
<form action="onebuttoninstaller-config.php" method="post" name="iform" id="iform">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td class="tabnavtbl">
		<ul id="tabnav">
        <?php if (isset($config['onebuttoninstaller']['enable'])) { ?>
    			<li class="tabinact"><a href="onebuttoninstaller.php"><span><?=gettext("Install");?></span></a></li>
    			<li class="tabact"><a href="onebuttoninstaller-config.php"><span><?=gettext("Configuration");?></span></a></li>
    			<li class="tabinact"><a href="onebuttoninstaller-update_extension.php"><span><?=gettext("Maintenance");?></span></a></li>
        <?php } else { ?>
    			<li class="tabact"><a href="onebuttoninstaller-config.php"><span><?=gettext("Configuration");?></span></a></li>
    			<li class="tabinact"><a href="onebuttoninstaller-update_extension.php"><span><?=gettext("Maintenance");?></span></a></li>
        <?php } ?>
		</ul>
	</td></tr>
    <tr><td class="tabcont">
        <?php if (!empty($input_errors)) print_input_errors($input_errors);?>
        <?php if (!empty($savemsg)) print_info_box($savemsg);?>
        <table width="100%" border="0" cellpadding="6" cellspacing="0">
            <?php html_titleline_checkbox("enable", gettext("OneButtonInstaller"), $pconfig['enable'], gettext("Enable"), "enable_change(false)");?>
            <?php html_text("installation_directory", gettext("Installation directory"), sprintf(gettext("The extension is installed in %s."), $config['onebuttoninstaller']['rootfolder']));?>
			<?php html_filechooser("storage_path", gettext("Common directory"), $pconfig['storage_path'], gettext("Common root directory for all extensions (a persistant place where all extensions are/should be - a directory below <b>/mnt/</b>)."), $pconfig['storage_path'], true, 60);?>
            <?php html_checkbox("auto_update", gettext("Update"), $pconfig['auto_update'], gettext("Update extensions list automatically."), "", false);?>
        </table>
        <div id="submit">
			<input id="save" name="save" type="submit" class="formbtn" value="<?=gettext("Save & Restart");?>"/>
        </div>
	</td></tr>
	</table>
	<?php include("formend.inc");?>
</form>
<script type="text/javascript">
<!--
enable_change(false);
//-->
</script>
<?php include("fend.inc");?>
