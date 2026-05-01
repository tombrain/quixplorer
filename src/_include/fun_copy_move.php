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
//------------------------------------------------------------------------------
function dir_list($dir) {            // make list of directories
    // this list is used to copy/move items to a specific location
    $dir_list=array();
    $handle = @opendir(get_abs_dir($dir));
    if($handle===false) return;        // unable to open dir

    while(($new_item=readdir($handle))!==false) {
        //if(!@file_exists(get_abs_item($dir, $new_item))) continue;

        if(!get_show_item($dir, $new_item)) continue;
        if(!get_is_dir($dir,$new_item)) continue;
        $dir_list[$new_item] = $new_item;
    }

    // sort
    if(is_array($dir_list)) ksort($dir_list);
    return $dir_list;
}
//------------------------------------------------------------------------------
function dir_print($dir_list, $new_dir) {
    $dir_up = dirname($new_dir);
    if ($dir_up == ".") $dir_up = "";

    $up_icon = $GLOBALS["baricons"]["up"];
    $up_link = addslashes($dir_up);

    echo <<<HTML
    <tr><td><a href="javascript:NewDir('{$up_link}');"><img width="16" height="16" align="absmiddle" src="{$up_icon}" alt="">&nbsp;..</a></td></tr>
    HTML;

    if (!is_array($dir_list)) return;
    foreach ($dir_list as $new_item => $unused)
    {
        $s_item   = strlen($new_item) > 40 ? substr($new_item, 0, 37) . "..." : $new_item;
        $rel_path = addslashes(get_rel_item($new_dir, $new_item));
        $s_enc    = htmlspecialchars($s_item, ENT_QUOTES, 'UTF-8');

        echo <<<HTML
        <tr><td><a href="javascript:NewDir('{$rel_path}');"><img width="16" height="16" align="absmiddle" src="_img/dir.gif" alt="">&nbsp;{$s_enc}</a></td></tr>
        HTML;
    }
}
//------------------------------------------------------------------------------
    // copy/move file/dir
function copy_move_items ($dir)
{
    // copy and move are only allowed if the user may read and change files
    if ($GLOBALS["action"] == "copy"
    && !permissions_grant_all($dir, NULL, array("read", "create")))
        show_error($GLOBALS["error_msg"]["accessfunc"]);
    if ($GLOBALS["action"] == "move"
    && !permissions_grant($dir, NULL, "change"))
        show_error($GLOBALS["error_msg"]["accessfunc"]);

    // Vars
    $first = $GLOBALS['__POST']["first"];
    if($first=="y") $new_dir=$dir;
    else $new_dir = $GLOBALS['__POST']["new_dir"];
    if($new_dir==".") $new_dir="";
    $cnt=count($GLOBALS['__POST']["selitems"]);

    // Copy or Move?
    if($GLOBALS["action"]!="move") {
        $_img="_img/__copy.gif";
    } else {
        $_img="_img/__cut.gif";
    }

    // Get New Location & Names
    if (!isset($GLOBALS['__POST']["confirm"]) || $GLOBALS['__POST']["confirm"]!="true")
    {
        $msg = $GLOBALS["action"] != "move"
            ?  $GLOBALS["messages"]["actcopyitems"]
            : $GLOBALS["messages"]["actmoveitems"];

        show_header($msg);

        $s_dir  = strlen($dir)     > 40 ? "..." . substr($dir, -37)     : $dir;
        $s_ndir = strlen($new_dir) > 40 ? "..." . substr($new_dir, -37) : $new_dir;
        $action_msg  = $GLOBALS["action"] != "move"
            ? $GLOBALS["messages"]["actcopyfrom"]
            : $GLOBALS["messages"]["actmovefrom"];
        $from_to     = htmlspecialchars(sprintf($action_msg, $s_dir, $s_ndir), ENT_QUOTES, 'UTF-8');
        $post_link   = make_link("post", $dir, NULL);
        $list_link   = make_link("list", $dir, NULL);
        $action_val  = htmlspecialchars($GLOBALS["action"], ENT_QUOTES, 'UTF-8');
        $new_dir_enc = htmlspecialchars($new_dir, ENT_QUOTES, 'UTF-8');
        $btn_action  = $GLOBALS["action"] != "move"
            ? htmlspecialchars($GLOBALS["messages"]["btncopy"], ENT_QUOTES, 'UTF-8')
            : htmlspecialchars($GLOBALS["messages"]["btnmove"], ENT_QUOTES, 'UTF-8');
        $btn_cancel  = htmlspecialchars($GLOBALS["messages"]["btncancel"], ENT_QUOTES, 'UTF-8');

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
        <br>
        <img src="{$_img}" align="absmiddle" alt="">&nbsp;{$from_to}
        <img src="_img/__paste.gif" align="absmiddle" alt="">
        <br><br>
        <form name="selform" method="post" action="{$post_link}">
            <input type="hidden" name="do_action" value="{$action_val}">
            <input type="hidden" name="confirm" value="false">
            <input type="hidden" name="first" value="n">
            <input type="hidden" name="new_dir" value="{$new_dir_enc}">
            <table>
        HTML;

        dir_print(dir_list($new_dir), $new_dir);

        echo "        </table>\n        <br>\n        <table>\n";

        for ($i = 0; $i < $cnt; ++$i)
        {
            $selitem = $GLOBALS['__POST']["selitems"][$i];
            if (isset($GLOBALS['__POST']["newitems"][$i]))
            {
                $newitem = $GLOBALS['__POST']["newitems"][$i];
                if ($first == "y") $newitem = $selitem;
            }
            else $newitem = $selitem;

            $s_item     = strlen($selitem) > 50 ? substr($selitem, 0, 47) . "..." : $selitem;
            $sel_enc    = htmlspecialchars($selitem, ENT_QUOTES, 'UTF-8');
            $s_enc      = htmlspecialchars($s_item, ENT_QUOTES, 'UTF-8');
            $new_enc    = htmlspecialchars($newitem, ENT_QUOTES, 'UTF-8');

            echo <<<HTML
                <tr>
                    <td><img src="_img/_info.gif" align="absmiddle" alt="">
                        <input type="hidden" name="selitems[]" value="{$sel_enc}">&nbsp;{$s_enc}&nbsp;
                    </td>
                    <td><input type="text" size="25" name="newitems[]" value="{$new_enc}"></td>
                </tr>
            HTML;
        }

        echo <<<HTML
        </table>
        <br>
        <table>
            <tr>
                <td><input type="submit" value="{$btn_action}" onclick="Execute();"></td>
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
    if(!@file_exists(get_abs_dir($new_dir))) show_error($new_dir.": ".$GLOBALS["error_msg"]["targetexist"]);
    if(!get_show_item($new_dir,"")) show_error($new_dir.": ".$GLOBALS["error_msg"]["accesstarget"]);
    if(!down_home(get_abs_dir($new_dir))) show_error($new_dir.": ".$GLOBALS["error_msg"]["targetabovehome"]);

    // copy / move files
    $err=false;
    for($i=0;$i<$cnt;++$i) {
        $tmp = $GLOBALS['__POST']["selitems"][$i];
        $new = basename($GLOBALS['__POST']["newitems"][$i]);
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

        // Copy / Move
        if($GLOBALS["action"]=="copy") {
            if(@is_link($abs_item) || @is_file($abs_item)) {
                // check file-exists to avoid error with 0-size files (PHP 4.3.0)
                $ok=@copy($abs_item,$abs_new_item);    //||@file_exists($abs_new_item);
            } elseif(@is_dir($abs_item)) {
                $ok=copy_dir($abs_item,$abs_new_item);
            }
        } else {
            $ok=@rename($abs_item,$abs_new_item);
        }

        if($ok===false) {
            $error[$i]=($GLOBALS["action"]=="copy"?
                $GLOBALS["error_msg"]["copyitem"]:
                $GLOBALS["error_msg"]["moveitem"]
            );
            $err=true;    continue;
        }

        $error[$i]=NULL;
    }

    if($err) {            // there were errors
        $err_msg="";
        for($i=0;$i<$cnt;++$i) {
            if($error[$i]==NULL) continue;

            $err_msg .= $items[$i]." : ".$error[$i]."<BR>\n";
        }
        show_error($err_msg);
    }

    header("Location: ".make_link("list",$dir,NULL));
}
//------------------------------------------------------------------------------
?>
