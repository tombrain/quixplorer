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

     The Original Code is fun_up.php, released on 2003-03-31.

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
    File-Upload Functions
    
    Have Fun...
------------------------------------------------------------------------------*/
require_once("./_include/permissions.php");
//------------------------------------------------------------------------------
// upload file
function upload_items($dir)
{
    if (!permissions_grant($dir, NULL, "create"))
        show_error($GLOBALS["error_msg"]["accessfunc"]);

    // Execute
    if (isset($GLOBALS['__POST']["confirm"]) && $GLOBALS['__POST']["confirm"] == "true")
    {
        $cnt = count($GLOBALS['__FILES']['userfile']['name']);
        $err = false;
        $err_avaliable = isset($GLOBALS['__FILES']['userfile']['error']);

        // upload files & check for errors
        for ($i = 0; $i < $cnt; $i++)
        {
            $errors[$i] = NULL;
            $tmp = $GLOBALS['__FILES']['userfile']['tmp_name'][$i];
            $items[$i] = $GLOBALS['__FILES']['userfile']['name'][$i];
            if ($err_avaliable) $up_err = $GLOBALS['__FILES']['userfile']['error'][$i];
            else $up_err = (file_exists($tmp) ? 0 : 4);
            $abs = get_abs_item($dir, $items[$i]);

            if ($items[$i] == "" || $up_err == 4) continue;
            if ($up_err == 1 || $up_err == 2)
            {
                $errors[$i] = $GLOBALS["error_msg"]["miscfilesize"];
                $err = true;
                continue;
            }
            if ($up_err == 3)
            {
                $errors[$i] = $GLOBALS["error_msg"]["miscfilepart"];
                $err = true;
                continue;
            }
            if (!@is_uploaded_file($tmp))
            {
                $errors[$i] = $GLOBALS["error_msg"]["uploadfile"];
                $err = true;
                continue;
            }
            if (@file_exists($abs))
            {
                $errors[$i] = $GLOBALS["error_msg"]["itemdoesexist"];
                $err = true;
                continue;
            }

            // Upload
            if (function_exists("move_uploaded_file"))
            {
                $ok = @move_uploaded_file($tmp, $abs);
            }
            else
            {
                $ok = @copy($tmp, $abs);
                @nlink($tmp);    // try to delete...
            }

            if ($ok === false)
            {
                $errors[$i] = $GLOBALS["error_msg"]["uploadfile"];
                $err = true;
                continue;
            }
        }

        if ($err)
        {            // there were errors
            $err_msg = "";
            for ($i = 0; $i < $cnt; $i++)
            {
                if ($errors[$i] == NULL) continue;
                $err_msg .= $items[$i] . " : " . $errors[$i] . "<BR>\n";
            }
            show_error($err_msg);
        }

        header("Location: " . make_link("list", $dir, NULL));
        return;
    }

    show_header($GLOBALS["messages"]["actupload"]);

    $upload_link = make_link("upload", $dir, NULL);
    $list_link   = make_link("list", $dir, NULL);
    $max_size    = get_max_file_size();
    $btn_upload  = htmlspecialchars($GLOBALS["messages"]["btnupload"], ENT_QUOTES, 'UTF-8');
    $btn_cancel  = htmlspecialchars($GLOBALS["messages"]["btncancel"], ENT_QUOTES, 'UTF-8');

    // Build file input rows
    $file_inputs = "";
    $filecount = 10;
    for ($ii = 0; $ii < $filecount; $ii++)
        $file_inputs .= "            <tr><td nowrap align=\"center\"><input name=\"userfile[]\" type=\"file\" size=\"40\"></td></tr>\n";

    echo <<<HTML
    <br>
    <form enctype="multipart/form-data" action="{$upload_link}" method="post">
        <input type="hidden" name="MAX_FILE_SIZE" value="{$max_size}">
        <input type="hidden" name="confirm" value="true">
        <table>
    HTML;
    echo $file_inputs;
    echo <<<HTML
        </table>
        <br>
        <table>
            <tr>
                <td><input type="submit" value="{$btn_upload}"></td>
                <td><input type="button" value="{$btn_cancel}" onclick="location='{$list_link}';"></td>
            </tr>
        </form>
        </table>
        <br>
    HTML;

    return;
}
//------------------------------------------------------------------------------
