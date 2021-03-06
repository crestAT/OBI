<?php
/* 
    onebuttoninstaller-install.php
    
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
/* 
Version Date        Description
0.1-b4  2016.04.07  2451 compatibility
0.1-b3  2016.02.06  N: add language support
0.1-b2  2016.02.02  N: check for supported architecture, plattform
                    N: auto update of extensions list
0.1-b1  2016.01.28  real install/update from GitHub
0.1-a6  2016.01.28  include spinner with overlay
0.1-a5  2016.01.27  output buffering working now
0.1-a4  2016.01.25  initial release
0.1-a1  2016.01.16  initial release
                    
*/
$v = "v0.1-b4";      // extension version
$appname = "OneButtonInstaller";

require_once("config.inc");

$arch = $g['arch'];
$platform = $g['platform'];
if (($arch != "i386" && $arch != "amd64") && ($arch != "x86" && $arch != "x64" && $arch != "rpi" && $arch != "rpi2")) { echo "\f{$arch} is an unsupported architecture!\n"; exit(1);  }
if ($platform != "embedded" && $platform != "full" && $platform != "livecd" && $platform != "liveusb") { echo "\funsupported platform!\n";  exit(1); }

// install extension
global $input_errors;
global $savemsg;

$install_dir = dirname(__FILE__)."/";                           // get directory where the installer script resides
if (!is_dir("{$install_dir}log")) { mkdir("{$install_dir}log", 0775, true); }

// check FreeBSD release for fetch options >= 9.3
$release = explode("-", exec("uname -r"));
if ($release[0] >= 9.3) $verify_hostname = "--no-verify-hostname";
else $verify_hostname = "";
// create stripped version name
$vs = str_replace(".", "", $v);
// fetch release archive
$return_val = 0;//mwexec("fetch {$verify_hostname} -vo {$install_dir}master.zip 'https://github.com/crestAT/nas4free-onebuttoninstaller/releases/download/{$v}/onebuttoninstaller-{$vs}.zip'", true);
if ($return_val == 0) {
    $return_val = 0;//mwexec("tar -xf {$install_dir}master.zip -C {$install_dir} --exclude='.git*' --strip-components 2", true);
    if ($return_val == 0) {
        //exec("rm {$install_dir}master.zip");
        exec("chmod -R 775 {$install_dir}");
        if (is_file("{$install_dir}version.txt")) { $file_version = exec("cat {$install_dir}version.txt"); }
        else { $file_version = "n/a"; }
        $savemsg = sprintf(gettext("Update to version %s completed!"), $file_version);
    }
    else { 
        $input_errors[] = sprintf(gettext("Archive file %s not found, installation aborted!"), "master.zip corrupt /"); 
        return;
    }
}
else { 
    $input_errors[] = sprintf(gettext("Archive file %s not found, installation aborted!"), "master.zip"); 
    return;
}

// install / update application on NAS4Free
if ( !isset($config['onebuttoninstaller']) || !is_array($config['onebuttoninstaller'])) { 
// new installation
    $config['onebuttoninstaller'] = array();      
    $config['onebuttoninstaller']['appname'] = $appname;
    $config['onebuttoninstaller']['version'] = exec("cat {$install_dir}version.txt");
    $config['onebuttoninstaller']['rootfolder'] = $install_dir;
    $i = 0;
    if ( is_array($config['rc']['postinit'] ) && is_array( $config['rc']['postinit']['cmd'] ) ) {
        for ($i; $i < count($config['rc']['postinit']['cmd']);) {
            if (preg_match('/onebuttoninstaller/', $config['rc']['postinit']['cmd'][$i])) break; ++$i; }
    }
    $config['rc']['postinit']['cmd'][$i] = $config['onebuttoninstaller']['rootfolder']."onebuttoninstaller_start.php";
    $i =0;
    if ( is_array($config['rc']['shutdown'] ) && is_array( $config['rc']['shutdown']['cmd'] ) ) {
        for ($i; $i < count($config['rc']['shutdown']['cmd']); ) {
            if (preg_match('/onebuttoninstaller/', $config['rc']['shutdown']['cmd'][$i])) break; ++$i; }
    }
    $config['rc']['shutdown']['cmd'][$i] = $config['onebuttoninstaller']['rootfolder']."onebuttoninstaller_stop.php";
    write_config();
    require_once("{$config['onebuttoninstaller']['rootfolder']}onebuttoninstaller-start.php");
    echo "\n".$appname." Version ".$config['onebuttoninstaller']['version']." installed";
    echo "\n\nInstallation completed, use WebGUI | Extensions | ".$appname." to configure \nthe application (don't forget to refresh the WebGUI before use)!\n";
}
else {
// update release
    $config['onebuttoninstaller']['version'] = exec("cat {$install_dir}version.txt");
    $config['onebuttoninstaller']['rootfolder'] = $install_dir;
    $i = 0;
    if ( is_array($config['rc']['postinit'] ) && is_array( $config['rc']['postinit']['cmd'] ) ) {
        for ($i; $i < count($config['rc']['postinit']['cmd']);) {
            if (preg_match('/onebuttoninstaller/', $config['rc']['postinit']['cmd'][$i])) break; ++$i; }
    }
    $config['rc']['postinit']['cmd'][$i] = $config['onebuttoninstaller']['rootfolder']."onebuttoninstaller_start.php";
    $i =0;
    if ( is_array($config['rc']['shutdown'] ) && is_array( $config['rc']['shutdown']['cmd'] ) ) {
        for ($i; $i < count($config['rc']['shutdown']['cmd']); ) {
            if (preg_match('/onebuttoninstaller/', $config['rc']['shutdown']['cmd'][$i])) break; ++$i; }
    }
    $config['rc']['shutdown']['cmd'][$i] = $config['onebuttoninstaller']['rootfolder']."onebuttoninstaller_stop.php";
    write_config();
    require_once("{$config['onebuttoninstaller']['rootfolder']}onebuttoninstaller-start.php");
}
?>
