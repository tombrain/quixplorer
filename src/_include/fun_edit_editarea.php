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

     The Original Code is fun_edit.php, released on 2003-03-31.

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
    File-Edit Functions

    Have Fun...
------------------------------------------------------------------------------*/
require_once("./_include/permissions.php");
//------------------------------------------------------------------------------
function savefile($file_name)
{            // save edited file
    //$code = stripslashes($GLOBALS['__POST']["code"]);
    $code = $GLOBALS['__POST']["code"];
    $fp = @fopen($file_name, "w");
    if ($fp === false) show_error(htmlspecialchars(basename($file_name)) . ": " . $GLOBALS["error_msg"]["savefile"]);
    fputs($fp, $code);
    @fclose($fp);
}
//------------------------------------------------------------------------------
// edit file

function edit_file($dir, $item)
{
    if (!permissions_grant($dir, $item, "change"))
        show_error($GLOBALS["error_msg"]["accessfunc"]);

    if (!get_is_file($dir, $item)) show_error(htmlspecialchars($item) . ": " . $GLOBALS["error_msg"]["fileexist"]);
    if (!get_show_item($dir, $item)) show_error(htmlspecialchars($item) . ": " . $GLOBALS["error_msg"]["accessfile"]);

    $fname = get_abs_item($dir, $item);

    if (isset($GLOBALS['__POST']["dosave"]) && $GLOBALS['__POST']["dosave"] == "yes")
    {
        // Save / Save As
        $item = basename($GLOBALS['__POST']["fname"]);
        $fname2 = get_abs_item($dir, $item);
        if (!isset($item) || $item == "") show_error($GLOBALS["error_msg"]["miscnoname"]);
        if ($fname != $fname2 && @file_exists($fname2)) show_error(htmlspecialchars($item) . ": " . $GLOBALS["error_msg"]["itemdoesexist"]);
        savefile($fname2);
        $fname = $fname2;
    }

    // open file
    $fp = @fopen($fname, "r");
    if ($fp === false) show_error(htmlspecialchars($item) . ": " . $GLOBALS["error_msg"]["openfile"]);

    // header
    $s_item = get_rel_item($dir, $item);
    if (strlen($s_item) > 50) $s_item = "..." . substr($s_item, -47);
    show_header($GLOBALS["messages"]["actedit"] . ": /" . htmlspecialchars($s_item));

    // Read file contents
    $buffer = "";
    while (!feof($fp))
    {
        $buffer .= fgets($fp, 4096);
    }
    @fclose($fp);

    // Pre-compute values for output
    $language     = htmlspecialchars($GLOBALS["language"], ENT_QUOTES, 'UTF-8');
    $syntax       = htmlspecialchars(get_mime_type($dir, $item, "ext") ?? '', ENT_QUOTES, 'UTF-8');
    $form_action  = make_link("edit", $dir, $item);
    $list_link    = make_link("list", $dir, NULL);
    $item_encoded = htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
    $code_encoded = htmlspecialchars($buffer, ENT_QUOTES, 'UTF-8');
    $btn_save     = htmlspecialchars($GLOBALS["messages"]["btnsave"], ENT_QUOTES, 'UTF-8');
    $btn_reset    = htmlspecialchars($GLOBALS["messages"]["btnreset"], ENT_QUOTES, 'UTF-8');
    $btn_close    = htmlspecialchars($GLOBALS["messages"]["btnclose"], ENT_QUOTES, 'UTF-8');

    echo <<<HTML
    <script>
        editAreaLoader.init({
            id:              "txtedit",
            start_highlight: true,
            allow_resize:    "both",
            allow_toggle:    true,
            word_wrap:       true,
            language:        "{$language}",
            syntax:          "{$syntax}"
        });

        function toggleWrap() {
            var ta = document.editfrm.code;
            ta.wrap = ta.wrap === "off" ? "soft" : "off";
        }
    </script>
    <br>
    <form name="editfrm" method="post" action="{$form_action}">
        <input type="hidden" name="dosave" value="yes">
        <textarea name="code" id="txtedit" rows="25" cols="120" wrap="off">{$code_encoded}</textarea>
        <br>
        <label><input type="checkbox" onclick="toggleWrap();"> Wordwrap</label>
        <br>
        <table>
            <tr>
                <td><input type="text" name="fname" value="{$item_encoded}"></td>
                <td><input type="submit" value="{$btn_save}"></td>
                <td><input type="reset" value="{$btn_reset}"></td>
                <td><input type="button" value="{$btn_close}" onclick="location='{$list_link}';"></td>
            </tr>
        </form>
        </table>
    <script>
        if (document.editfrm) document.editfrm.code.focus();
    </script>
    HTML;
}
//------------------------------------------------------------------------------
?>