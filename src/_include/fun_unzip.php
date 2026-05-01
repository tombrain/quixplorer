<?php
/*------------------------------------------------------------------------------
     The contents of this file are subject to the Mozilla Public License
     Version 1.1 (the "License"); you may not use this file except in
     compliance with the License. You may obtain a copy of the License at
     http://www.mozilla.org/MPL/

     Software distributed under the License is distributed on an "AS IS"
     basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
     License for the specific language governing rights and limitations
     under the License.

     The Original Code is fun_copy_move.php, released on 2003-03-31.

     The Initial Developer of the Original Code is The QuiX project.

     Alternatively, the contents of this file may be used under the terms
     of the GNU General Public License Version 2 or later (the "GPL"), in
     which case the provisions of the GPL are applicable instead of
     those above. If you wish to allow use of your version of this file only
     under the terms of the GPL and not to allow others to use
     your version of this file under the MPL, indicate your decision by
     deleting  the provisions above and replace  them with the notice and
     other provisions required by the GPL.  If you do not delete
     the provisions above, a recipient may use your version of this file
     under either the MPL or the GPL."
------------------------------------------------------------------------------*/
/*------------------------------------------------------------------------------
Author: The QuiX project
    quix@free.fr
    http://www.quix.tk
    http://quixplorer.sourceforge.net

Comment:
    QuiXplorer Version 2.3
    File/Directory Copy & Move Functions
    
    Have Fun...
------------------------------------------------------------------------------*/
require_once("./_include/permissions.php");
require_once("./_include/debug.php");
//------------------------------------------------------------------------------
// File Clone of fun_copy_move.php
//------------------------------------------------------------------------------
function dir_list($dir)
{            // make list of directories
    // this list is used to copy/move items to a specific location
    $dir_list = array();
    $handle = @opendir(get_abs_dir($dir));
    if ($handle === false) return;        // unable to open dir

    while (($new_item = readdir($handle)) !== false)
    {
        //if(!@file_exists(get_abs_item($dir, $new_item))) continue;

        if (!get_show_item($dir, $new_item)) continue;
        if (!get_is_dir($dir, $new_item)) continue;
        $dir_list[$new_item] = $new_item;
    }

    // sort
    if (is_array($dir_list)) ksort($dir_list);
    return $dir_list;
}
//------------------------------------------------------------------------------
function dir_print($dir_list, $new_dir)
{    // print list of directories
    // this list is used to copy/move items to a specific location

    // Link to Parent Directory
    $dir_up = dirname($new_dir);
    if ($dir_up == ".") $dir_up = "";

    $up_icon = $GLOBALS["baricons"]["up"];
    $dir_up_js = addslashes($dir_up);
    echo <<<HTML
    <tr><td><a href="javascript:NewDir('{$dir_up_js}');"><img border="0" width="16" height="16" align="absmiddle" src="{$up_icon}" alt="">&nbsp;..</a></td></tr>
    HTML;

    // Print List Of Target Directories
    if (!is_array($dir_list)) return;
    foreach ($dir_list as $new_item => $unused)
    {
        $s_item   = strlen($new_item) > 40 ? substr($new_item, 0, 37) . "..." : $new_item;
        $rel_js   = addslashes(get_rel_item($new_dir, $new_item));
        $s_enc    = htmlspecialchars($s_item, ENT_QUOTES, 'UTF-8');
        echo <<<HTML
        <tr><td><a href="javascript:NewDir('{$rel_js}');"><img border="0" width="16" height="16" align="absmiddle" src="_img/dir.gif" alt="">&nbsp;{$s_enc}</a></td></tr>
        HTML;
    }
}
//------------------------------------------------------------------------------
// copy/move file/dir
function unzip_item($dir)
{
    _debug("unzip_item($dir)");

    global $home_dir;

    // copy and move are only allowed if the user may read and change files
    if (!permissions_grant_all($dir, NULL, array("read", "create")))
    {
        show_error($GLOBALS["error_msg"]["accessfunc"]);
    }

    // Vars

    $new_dir = (isset($GLOBALS['__POST']["new_dir"])) ? $GLOBALS['__POST']["new_dir"] : $dir;

    $_img = $GLOBALS["baricons"]["unzip"];

    // Get Selected Item
    if (!isset($GLOBALS['__POST']["item"]) && isset($GLOBALS['__GET']["item"]))
    {
        $s_item = $GLOBALS['__GET']["item"];
    }
    elseif (isset($GLOBALS['__POST']["item"]))
    {
        $s_item = $GLOBALS['__POST']["item"];
    }

    $dir_extract = "$home_dir/$new_dir";

    if ($new_dir != "")
    {
        $dir_extract .= "/";
    }

    $zip_name = "$home_dir/$dir/$s_item";

    // Get New Location & Names
    if (! isset($GLOBALS['__POST']["confirm"]) || $GLOBALS['__POST']["confirm"] != "true")
    {
        show_header($GLOBALS["messages"]["actunzipitem"]);

        $post_link    = make_link("post", $dir, NULL);
        $list_link    = make_link("list", $dir, NULL);
        $action_val   = htmlspecialchars($GLOBALS["action"], ENT_QUOTES, 'UTF-8');
        $new_dir_enc  = htmlspecialchars($new_dir, ENT_QUOTES, 'UTF-8');
        $item_enc     = htmlspecialchars($s_item, ENT_QUOTES, 'UTF-8');
        $item_disp    = htmlspecialchars($s_item, ENT_QUOTES, 'UTF-8');
        $zip_icon     = $GLOBALS["baricons"]["zip"];
        $unzipto_icon = $GLOBALS["baricons"]["unzipto"];
        $btn_unzip    = htmlspecialchars($GLOBALS["messages"]["btnunzip"], ENT_QUOTES, 'UTF-8');
        $btn_cancel   = htmlspecialchars($GLOBALS["messages"]["btncancel"], ENT_QUOTES, 'UTF-8');
        $dirextr_enc  = htmlspecialchars($dir_extract, ENT_QUOTES, 'UTF-8');
        $zipname_enc  = htmlspecialchars($zip_name, ENT_QUOTES, 'UTF-8');

        echo <<<HTML
        <script>
            function NewDir(newdir) {
                document.selform.new_dir.value = newdir;
                document.selform.submit();
            }
            function Execute() {
                document.selform.confirm.value = "true";
            }
        </script>
        <!-- dirextr = {$dirextr_enc} -->
        <!-- zipname = {$zipname_enc} -->
        <br>
        <img src="{$_img}" align="absmiddle" alt="">&nbsp;<img src="{$unzipto_icon}" align="absmiddle" alt="">
        <br><br>
        <form name="selform" method="post" action="{$post_link}">
            <input type="hidden" name="do_action" value="{$action_val}">
            <input type="hidden" name="confirm" value="false">
            <input type="hidden" name="new_dir" value="{$new_dir_enc}">
            <table>
        HTML;

        dir_print(dir_list($new_dir), $new_dir);

        echo <<<HTML
            </table>
            <br>
            <table>
                <tr><td>
                    <img src="{$zip_icon}" align="absmiddle" alt="">
                    <input type="hidden" name="item" value="{$item_enc}">&nbsp;{$item_disp}&nbsp;
                </td></tr>
            </table>
            <br>
            <table>
                <tr>
                    <td><input type="submit" value="{$btn_unzip}" onclick="Execute();"></td>
                    <td><input type="button" value="{$btn_cancel}" onclick="location='{$list_link}';"></td>
                </tr>
            </form>
            </table>
            <br>
        HTML;
        return;
        }

                // DO COPY/MOVE

                // ALL OK?
                if (!@file_exists(get_abs_dir($new_dir))) show_error(htmlspecialchars($new_dir) . ": " . $GLOBALS["error_msg"]["targetexist"]);
                if (!get_show_item($new_dir, "")) show_error(htmlspecialchars($new_dir) . ": " . $GLOBALS["error_msg"]["accesstarget"]);
                if (!down_home(get_abs_dir($new_dir))) show_error(htmlspecialchars($new_dir) . ": " . $GLOBALS["error_msg"]["targetabovehome"]);

                // copy / move files
                $err = false;
                /*for($i=0;$i<$cnt;++$i) {
        $tmp = stripslashes($GLOBALS['__POST']["selitems"][$i]);
        $new = basename(stripslashes($GLOBALS['__POST']["newitems"][$i]));
        $abs_item = get_abs_item($dir,$tmp);
        $abs_new_item = get_abs_item($new_dir,$new);
        $items[$i] = $tmp;
    
        // Check
        if($new=="") {
            $error[$i]= $GLOBALS["error_msg"]["miscnoname"];
            $err=true;    continue;
        }
        if(!@file_exists($abs_item)) {
            $error[$i]= $GLOBALS["error_msg"]["itemexist"];
            $err=true;    continue;
        }
        if(!get_show_item($dir, $tmp)) {
            $error[$i]= $GLOBALS["error_msg"]["accessitem"];
            $err=true;    continue;
        }
        if(@file_exists($abs_new_item)) {
            $error[$i]= $GLOBALS["error_msg"]["targetdoesexist"];
            $err=true;    continue;
        }
    */
                // Copy / Move
                //if($GLOBALS["action"]=="copy") {
                //if($GLOBALS["action"]=="unzip") {
                /*
            if(@is_link($abs_item) || @is_file($abs_item)) {
                // check file-exists to avoid error with 0-size files (PHP 4.3.0)
                $ok=@copy($abs_item,$abs_new_item);    //||@file_exists($abs_new_item);
            } elseif(@is_dir($abs_item)) {
                $ok=copy_dir($abs_item,$abs_new_item);
            }
        */

                //----------------------------------          print_r($GLOBALS);

                _debug("unzip_item(): Extracting $zip_name to $dir_extract");

                //$dir_extract[0]='/';
                //$dir_extract = '.'. $dir_extract;
                //------------------------------------------------------echo $zip_name.' aa'.$dir_extract.'aa';
                $exx = pathinfo($zip_name, PATHINFO_EXTENSION);

                if ($exx == 'zip')
                {
                    $zip = new ZipArchive;
                    $res = $zip->open($zip_name);
                    if ($res === TRUE)
                    {
                        $zip->extractTo($dir_extract);
                        $zip->close();
                    }
                    else
                    {
                    }
                }
                else
                {
                    // gz, tar, bz2, ....
                    include_once './_lib/archive.php';
                    extArchive::extract($zip_name, $dir_extract);
                }

                // FIXME: $res may be unset if non-zip extracted via extArchive without returning a value
                if (isset($res) && $res === false)
                {
                    show_error($GLOBALS["error_msg"]["unzip"]);
                }

                header("Location: " . make_link("list", $dir, NULL));
            }
            //------------------------------------------------------------------------------
                    ?>