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

     The Original Code is fun_search.php, released on 2003-03-31.

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
    File-Search Functions

    Have Fun...
------------------------------------------------------------------------------*/
//------------------------------------------------------------------------------
function find_item($dir, $pat, &$list, $recur)
{    // find items
    $handle = @opendir(get_abs_dir($dir));
    if ($handle === false) return;        // unable to open dir

    while (($new_item = readdir($handle)) !== false)
    {
        if (!@file_exists(get_abs_item($dir, $new_item))) continue;
        if (!get_show_item($dir, $new_item)) continue;

        // match?
        if (@preg_match('/' . $pat . '/i', $new_item)) $list[] = array($dir, $new_item);

        // search sub-directories
        if (get_is_dir($dir, $new_item) && $recur)
        {
            find_item(get_rel_item($dir, $new_item), $pat, $list, $recur);
        }
    }

    closedir($handle);
}
//------------------------------------------------------------------------------
function make_list($dir, $item, $subdir)
{    // make list of found items
    // convert shell-wildcards to PCRE Regex Syntax
    if ($item === null || $item === "")
    {
        return array();
    }
    $pat = "^" . str_replace("?", ".", str_replace("*", ".*", str_replace(".", "\\.", $item))) . "$";

    // search
    $list = array();
    find_item($dir, $pat, $list, $subdir);
    if (is_array($list)) sort($list);
    return $list;
}
//------------------------------------------------------------------------------
function print_table($list)
{
    if (!is_array($list))
        return;

    $cnt = count($list);
    for ($i = 0; $i < $cnt; ++$i)
    {
        $dir  = $list[$i][0];
        $item = $list[$i][1];

        $s_dir  = strlen($dir)  > 65 ? substr($dir, 0, 62)  . "..." : $dir;
        $s_item = strlen($item) > 45 ? substr($item, 0, 42) . "..." : $item;

        if (get_is_dir($dir, $item))
        {
            $img  = "dir.gif";
            $link = make_link("list", get_rel_item($dir, $item), NULL);
        }
        else
        {
            $img  = get_mime_type($dir, $item, "img");
            $link = make_link("download", $dir, $item);
        }

        $dir_link    = make_link("list", $dir, NULL);
        $item_enc    = htmlspecialchars($s_item, ENT_QUOTES, 'UTF-8');
        $dir_enc     = htmlspecialchars($s_dir, ENT_QUOTES, 'UTF-8');

        echo <<<HTML
        <tr>
            <td><img width="16" height="16" align="absmiddle" src="_img/{$img}" alt="">&nbsp;<a href="{$link}">{$item_enc}</a></td>
            <td><a href="{$dir_link}"> /{$dir_enc}</a></td>
        </tr>
        HTML;
    }
}
//------------------------------------------------------------------------------
function search_items($dir)
{            // search for item
    if (isset($GLOBALS['__POST']["searchitem"]))
    {
        $searchitem = $GLOBALS['__POST']["searchitem"];
        $subdir = (isset($GLOBALS['__POST']["subdir"]) && $GLOBALS['__POST']["subdir"] == "y");
        $list = make_list($dir, $searchitem, $subdir);
    }
    else
    {
        $searchitem = NULL;
        $subdir = true;
    }

    $msg = $GLOBALS["messages"]["actsearchresults"];
    if ($searchitem != NULL) $msg .= ": (/" . get_rel_item($dir, $searchitem) . ")";
    show_header(htmlspecialchars($msg));

    $search_link  = make_link("search", $dir, NULL);
    $list_link    = make_link("list", $dir, NULL);
    $search_val   = htmlspecialchars($searchitem !== NULL ? $searchitem : "", ENT_QUOTES, 'UTF-8');
    $btn_search   = htmlspecialchars($GLOBALS["messages"]["btnsearch"], ENT_QUOTES, 'UTF-8');
    $btn_close    = htmlspecialchars($GLOBALS["messages"]["btnclose"], ENT_QUOTES, 'UTF-8');
    $lbl_subdirs  = $GLOBALS["messages"]["miscsubdirs"];
    $subdir_chk   = $subdir ? " checked" : "";

    echo <<<HTML
    <br>
    <table>
        <form name="searchform" action="{$search_link}" method="post">
            <tr><td>
                <input name="searchitem" type="text" size="25" value="{$search_val}">
                <input type="submit" value="{$btn_search}">
                &nbsp;<input type="button" value="{$btn_close}" onclick="location='{$list_link}';">
            </td></tr>
            <tr><td>
                <input type="checkbox" name="subdir" value="y"{$subdir_chk}> {$lbl_subdirs}
            </td></tr>
        </form>
    </table>
    HTML;

    // Results
    if ($searchitem != NULL)
    {
        $hdr_name = $GLOBALS["messages"]["nameheader"];
        $hdr_path = $GLOBALS["messages"]["pathheader"];

        echo <<<HTML
        <table width="95%">
            <tr><td colspan="2"><hr></td></tr>
        HTML;

        if (is_array($list) && count($list) > 0)
        {
            echo <<<HTML
            <tr>
                <td width="42%" class="header"><b>{$hdr_name}</b></td>
                <td width="58%" class="header"><b>{$hdr_path}</b></td>
            </tr>
            <tr><td colspan="2"><hr></td></tr>
            HTML;

            print_table($list);

            $item_count = count($list);
            $misc_items = $GLOBALS["messages"]["miscitems"];
            echo <<<HTML
            <tr><td colspan="2"><hr></td></tr>
            <tr>
                <td class="header">{$item_count} {$misc_items}.</td>
                <td class="header"></td>
            </tr>
            HTML;
        }
        else
        {
            $no_result = $GLOBALS["messages"]["miscnoresult"];
            echo "<tr><td>{$no_result}</td></tr>\n";
        }

        echo <<<HTML
            <tr><td colspan="2"><hr></td></tr>
        </table>
        <script>
            if (document.searchform) document.searchform.searchitem.focus();
        </script>
        HTML;
    }
}