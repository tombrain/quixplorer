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

     The Original Code is fun_chmod.php, released on 2003-03-31.

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
    Permission-Change Functions
    
    Have Fun...
------------------------------------------------------------------------------*/
require_once("./_include/permissions.php");
//------------------------------------------------------------------------------
// change permissions
function chmod_item($dir, $item)
{
    if (!permissions_grant($dir, NULL, "change"))
        show_error($GLOBALS["error_msg"]["accessfunc"]);
    if (!file_exists(get_abs_item($dir, $item))) show_error($item . ": " . $GLOBALS["error_msg"]["fileexist"]);
    if (!get_show_item($dir, $item)) show_error($item . ": " . $GLOBALS["error_msg"]["accessfile"]);

    // Execute
    if (isset($GLOBALS['__POST']["confirm"]) && $GLOBALS['__POST']["confirm"] == "true")
    {
        $bin = '';
        for ($i = 0; $i < 3; $i++) for ($j = 0; $j < 3; $j++)
        {
            $tmp = "r_" . $i . $j;
            if (isset($GLOBALS['__POST'][$tmp]) && $GLOBALS['__POST'][$tmp] == "1") $bin .= '1';
            else $bin .= '0';
        }

        if (!@chmod(get_abs_item($dir, $item), bindec($bin)))
        {
            show_error($item . ": " . $GLOBALS["error_msg"]["permchange"]);
        }
        header("Location: " . make_link("link", $dir, NULL));
        return;
    }

    $mode = parse_file_perms(get_file_perms($dir, $item));
    if ($mode === false) show_error($item . ": " . $GLOBALS["error_msg"]["permread"]);
    $pos = "rwx";

    $s_item = get_rel_item($dir, $item);
    if (strlen($s_item) > 50) $s_item = "..." . substr($s_item, -47);
    show_header($GLOBALS["messages"]["actperms"] . ": /" . $s_item);

    $chmod_link = make_link("chmod", $dir, $item);
    $list_link  = make_link("list", $dir, NULL);
    $btn_change = htmlspecialchars($GLOBALS["messages"]["btnchange"], ENT_QUOTES, 'UTF-8');
    $btn_cancel = htmlspecialchars($GLOBALS["messages"]["btncancel"], ENT_QUOTES, 'UTF-8');

    echo <<<HTML
    <br>
    <table width="175">
        <form method="post" action="{$chmod_link}">
            <input type="hidden" name="confirm" value="true">
    HTML;

    // print table with current perms & checkboxes to change
    $pos = "rwx";
    for ($i = 0; $i < 3; ++$i)
    {
        $label = $GLOBALS["messages"]["miscchmod"][$i];
        echo "        <tr><td>{$label}</td>";
        for ($j = 0; $j < 3; ++$j)
        {
            $char    = $pos[$j];
            $checked = $mode[(3 * $i) + $j] != "-" ? " checked" : "";
            $name    = "r_{$i}{$j}";
            echo "<td>{$char}&nbsp;<input type=\"checkbox\" name=\"{$name}\" value=\"1\"{$checked}></td>";
        }
        echo "</tr>\n";
    }

    echo <<<HTML
        </table>
        <br>
        <table>
            <tr>
                <td><input type="submit" value="{$btn_change}"></td>
                <td><input type="button" value="{$btn_cancel}" onclick="location='{$list_link}';"></td>
            </tr>
        </form>
        </table>
        <br>
    HTML;
}
//------------------------------------------------------------------------------
