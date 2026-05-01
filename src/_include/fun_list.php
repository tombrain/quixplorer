<?php

require_once("./_include/permissions.php");
require_once("./_include/login.php");
require_once("./_include/qxpath.php");

function make_list($_list1, $_list2)
{        // make list of files: directories always first (like Windows Explorer)
    $list = array();

    if (is_array($_list1))
    {
        foreach ($_list1 as $key => $val)
        {
            $list[$key] = $val;
        }
    }

    if (is_array($_list2))
    {
        foreach ($_list2 as $key => $val)
        {
            $list[$key] = $val;
        }
    }

    return $list;
}
/**
 make table of files in dir
 make tables & place results in reference-variables passed to function
 also 'return' total filesize & total number of items
 */
function make_tables($dir, &$dir_list, &$file_list, &$tot_file_size, &$num_items)
{
    $tot_file_size = $num_items = 0;

    // Open directory
    $handle = @opendir(get_abs_dir($dir));
    if ($handle === false)
        show_error($dir . ": " . $GLOBALS["error_msg"]["opendir"]);

    // Read directory
    while (($new_item = readdir($handle)) !== false)
    {
        $abs_new_item = get_abs_item($dir, $new_item);

        if (!get_show_item($dir, $new_item))
            continue;

        $new_file_size = is_link($abs_new_item) ? 0 : @filesize($abs_new_item);
        $tot_file_size += $new_file_size;
        $num_items++;

        if (is_dir($abs_new_item))
        {
            if ($GLOBALS["order"] == "mod")
            {
                $dir_list[$new_item] = @filemtime($abs_new_item);
            }
            else
            {
                // order == "size", "type" or "name"
                $dir_list[$new_item] = $new_item;
            }
        }
        else
        {
            if ($GLOBALS["order"] == "size")
            {
                $file_list[$new_item] = $new_file_size;
            }
            elseif ($GLOBALS["order"] == "mod")
            {
                $file_list[$new_item] = @filemtime($abs_new_item);
            }
            elseif ($GLOBALS["order"] == "type")
            {
                $file_list[$new_item] = get_mime_type($dir, $new_item, "type");
            }
            else
            {
                // order == "name"
                $file_list[$new_item] = $new_item;
            }
        }
    }
    closedir($handle);

    // sort
    if (is_array($dir_list))
    {
        if ($GLOBALS["order"] == "mod")
        {
            if ($GLOBALS["srt"] == "yes")
                arsort($dir_list);
            else
                asort($dir_list);
        }
        else
        {
            // order == "size", "type" or "name"
            if ($GLOBALS["srt"] == "yes")
                ksort($dir_list);
            else
                krsort($dir_list);
        }
    }

    // sort
    if (is_array($file_list))
    {
        if ($GLOBALS["order"] == "mod")
        {
            if ($GLOBALS["srt"] == "yes")
                arsort($file_list);
            else
                asort($file_list);
        }
        elseif ($GLOBALS["order"] == "size" || $GLOBALS["order"] == "type")
        {
            if ($GLOBALS["srt"] == "yes")
                asort($file_list);
            else
                arsort($file_list);
        }
        else
        {
            // order == "name"
            if ($GLOBALS["srt"] == "yes")
                ksort($file_list);
            else
                krsort($file_list);
        }
    }
}

/**
  print table of files
 */
function print_table($dir, $list)
{
    if (!is_array($list))
        return;

    $icon_none = $GLOBALS["baricons"]["none"];

    foreach ($list as $item => $sort_value)
    {
        // link to dir / file
        $abs_item = get_abs_item($dir, $item);
        $target = "";
        if (is_dir($abs_item))
        {
            $link = make_link("list", get_rel_item($dir, $item), NULL);
        }
        else
        {
            $link = $GLOBALS["home_url"] . "/" . get_rel_item($dir, $item);
            $target = "_blank";
        }

        $item_encoded    = htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
        $s_item          = strlen($item) > 50 ? substr($item, 0, 47) . "..." : $item;
        $s_item_encoded  = htmlspecialchars($s_item, ENT_QUOTES, 'UTF-8');
        $mime_img        = get_mime_type($dir, $item, "img");
        $can_read        = permissions_grant($dir, $item, "read");
        $can_change_item = permissions_grant($dir, $item, "change");
        $can_change_dir  = permissions_grant($dir, NULL, "change");

        // Pre-compute all column values before rendering
        $link_open  = $can_read ? "<a href=\"{$link}\" target=\"{$target}\">" : "";
        $link_close = $can_read ? "</a>" : "";
        $parse_size = parse_file_size(get_file_size($dir, $item)) . sprintf("%10s", "&nbsp;");
        $type_info  = _get_link_info($dir, $item, "type");
        $mod_date   = parse_file_date(get_file_date($dir, $item));

        // Permissions column
        $perms_content = parse_file_type($dir, $item) . parse_file_perms(get_file_perms($dir, $item));
        if ($can_change_dir)
        {
            $chmod_link = make_link("chmod", $dir, $item);
            $perm_title = htmlspecialchars($GLOBALS["messages"]["permlink"], ENT_QUOTES, 'UTF-8');
            $perms_content = "<a href=\"{$chmod_link}\" title=\"{$perm_title}\">{$perms_content}</a>";
        }

        echo <<<HTML
        <tr class="rowdata">
            <td><input type="checkbox" name="selitems[]" value="{$item_encoded}" onclick="Toggle(this);"></td>
            <td nowrap>{$link_open}<img width="16" height="16" align="absmiddle" src="_img/{$mime_img}" alt="">&nbsp;{$s_item_encoded}{$link_close}</td>
            <td>{$parse_size}</td>
            <td>{$type_info}</td>
            <td>{$mod_date}</td>
            <td>{$perms_content}</td>
            <td>
                <table>

        HTML;

        // Actions column: edit or unzip
        if (get_is_editable($dir, $item))
        {
            _print_link("edit", $can_change_item, $dir, $item);
        }
        elseif (get_is_unzipable($dir, $item))
        {
            _print_link("unzip", permissions_grant($dir, $item, "create"), $dir, $item);
        }
        else
        {
            echo <<<HTML
                    <td><img width="16" height="16" align="absmiddle" src="{$icon_none}" alt=""></td>

            HTML;
        }

        // Download action
        if (get_is_file($dir, $item))
        {
            _print_link("download", $can_read, $dir, $item);
        }
        else
        {
            echo <<<HTML
                    <td><img width="16" height="16" align="absmiddle" src="{$icon_none}" alt=""></td>

            HTML;
        }

        echo <<<HTML
                </table>
            </td>
        </tr>

        HTML;
    }
}

/**
 MAIN FUNCTION
 */
function list_dir($dir)
{
    _debug("list_dir: displaying directory $dir");

    if (! get_show_item($dir, NULL))
        show_error($GLOBALS["error_msg"]["accessdir"] . " : '$dir'");

    // make file & dir tables, & get total filesize & number of items
    make_tables($dir, $dir_list, $file_list, $tot_file_size, $num_items);

    $s_dir = $dir;
    if (strlen($s_dir) > 50)
        $s_dir = "..." . substr($s_dir, -47);
    show_header($GLOBALS["messages"]["actdir"] . ": " . _breadcrumbs($dir));
    // show_header($GLOBALS["messages"]["actdir"].": /".get_rel_item("",$s_dir));

    // Javascript functions:
    include "./_include/javascript.php";

    // Sorting: arrow image + flipped sort direction
    $arrow_img = $GLOBALS["srt"] == "yes"
        ? "_img/_arrowup.gif\" alt=\"^\""
        : "_img/_arrowdown.gif\" alt=\"v\"";
    $_srt = $GLOBALS["srt"] == "yes" ? "no" : "yes";
    $sort_img = "&nbsp;<img width=\"10\" height=\"10\" border=\"0\" align=\"absmiddle\" src=\"{$arrow_img}\">";

    // Pre-compute all toolbar links
    $up_link     = make_link("list", path_up($dir), NULL);
    $home_link   = make_link("list", NULL, NULL);
    $search_link = make_link("search", $dir, NULL);
    $post_link   = make_link("post", $dir, NULL);
    $mkitem_link = make_link("mkitem", $dir, NULL);
    $up_icon     = $GLOBALS["baricons"]["up"];
    $home_icon   = $GLOBALS["baricons"]["home"];
    $reload_icon = $GLOBALS["baricons"]["reload"];
    $search_icon = $GLOBALS["baricons"]["search"];
    $add_icon    = $GLOBALS["baricons"]["add"];
    $up_title    = htmlspecialchars($GLOBALS["messages"]["uplink"],     ENT_QUOTES, 'UTF-8');
    $home_title  = htmlspecialchars($GLOBALS["messages"]["homelink"],   ENT_QUOTES, 'UTF-8');
    $reload_title= htmlspecialchars($GLOBALS["messages"]["reloadlink"], ENT_QUOTES, 'UTF-8');
    $search_title= htmlspecialchars($GLOBALS["messages"]["searchlink"], ENT_QUOTES, 'UTF-8');
    $btn_create  = htmlspecialchars($GLOBALS["messages"]["btncreate"],  ENT_QUOTES, 'UTF-8');
    $mime_file   = htmlspecialchars($GLOBALS["mimes"]["file"], ENT_QUOTES, 'UTF-8');
    $mime_dir    = htmlspecialchars($GLOBALS["mimes"]["dir"],  ENT_QUOTES, 'UTF-8');

    echo <<<HTML
    <br>
    <table width="95%"><tr><td><table><tr>
        <td><a href="{$up_link}"><img border="0" width="16" height="16" align="absmiddle" src="{$up_icon}" alt="{$up_title}" title="{$up_title}"></a></td>
        <td><a href="{$home_link}"><img border="0" width="16" height="16" align="absmiddle" src="{$home_icon}" alt="{$home_title}" title="{$home_title}"></a></td>
        <td><a href="javascript:location.reload();"><img border="0" width="16" height="16" align="absmiddle" src="{$reload_icon}" alt="{$reload_title}" title="{$reload_title}"></a></td>
        <td><a href="{$search_link}"><img border="0" width="16" height="16" align="absmiddle" src="{$search_icon}" alt="{$search_title}" title="{$search_title}"></a></td>
        <td>::</td>
    HTML;

    // print the download button
    _print_link("download_selected", permissions_grant($dir, NULL, "read"), $dir, NULL);

    // print the edit buttons
    _print_edit_buttons($dir);

    // ADMIN & LOGOUT
    if (login_is_user_logged_in())
    {
        echo "    <td>::</td>\n";
        _print_link(
            "admin",
            permissions_grant(NULL, NULL, "admin")
                || permissions_grant(NULL, NULL, "password"),
            $dir,
            NULL
        );
        _print_link("logout", true, $dir, NULL);
    }

    echo "    <td>::</td>\n";

    foreach ($GLOBALS["langs"] as $langs)
    {
        $lang_link = make_link("list", $dir, NULL, NULL, NULL, $langs[0]);
        if (!file_exists($langs[1]))
        {
            echo "    <td><a href=\"{$lang_link}\">&nbsp;{$langs[0]} </a></td>\n";
        }
        else
        {
            $lang_alt = htmlspecialchars($langs[0], ENT_QUOTES, 'UTF-8');
            $lang_tit = htmlspecialchars($langs[2], ENT_QUOTES, 'UTF-8');
            echo <<<HTML
            <td><a href="{$lang_link}"><img border="0" width="16" height="11" align="absmiddle" src="{$langs[1]}" alt="{$lang_alt}" title="{$lang_tit}"/></a></td>
            HTML;
        }
    }

    echo "    </tr></table></td>\n";

    // Create File / Dir
    if (permissions_grant($dir, NULL, "create"))
    {
        echo <<<HTML
        <td align="right"><table><form action="{$mkitem_link}" method="post">
            <tr><td>
                <img border="0" width="16" height="16" align="absmiddle" src="{$add_icon}"/>
                <select name="mktype">
                    <option value="file">{$mime_file}</option>
                    <option value="dir">{$mime_dir}</option>
                </select>
                <input name="mkname" type="text" size="15">
                <input type="submit" value="{$btn_create}">
            </td></tr>
        </form></table></td>
        HTML;
    }

    echo "</tr></table>\n";

    // Begin Table + Form for checkboxes
    // Compute sort links
    $new_srt_name = $GLOBALS["order"] == "name" ? $_srt : "yes";
    $new_srt_size = $GLOBALS["order"] == "size" ? $_srt : "yes";
    $new_srt_type = $GLOBALS["order"] == "type" ? $_srt : "yes";
    $new_srt_mod  = $GLOBALS["order"] == "mod"  ? $_srt : "yes";

    $link_name = make_link("list", $dir, NULL, "name", $new_srt_name);
    $link_size = make_link("list", $dir, NULL, "size", $new_srt_size);
    $link_type = make_link("list", $dir, NULL, "type", $new_srt_type);
    $link_mod  = make_link("list", $dir, NULL, "mod",  $new_srt_mod);

    $hdr_name   = $GLOBALS["messages"]["nameheader"];
    $hdr_size   = $GLOBALS["messages"]["sizeheader"];
    $hdr_type   = $GLOBALS["messages"]["typeheader"];
    $hdr_mod    = $GLOBALS["messages"]["modifheader"];
    $hdr_perm   = $GLOBALS["messages"]["permheader"];
    $hdr_action = $GLOBALS["messages"]["actionheader"];

    $img_name = $GLOBALS["order"] == "name" ? $sort_img : "";
    $img_size = $GLOBALS["order"] == "size" ? $sort_img : "";
    $img_type = $GLOBALS["order"] == "type" ? $sort_img : "";
    $img_mod  = $GLOBALS["order"] == "mod"  ? $sort_img : "";

    echo <<<HTML
    <table width="95%"><form name="selform" method="POST" action="{$post_link}">
        <input type="hidden" name="do_action"><input type="hidden" name="first" value="y">
        <tr><td colspan="7"><hr></td></tr>
        <tr>
            <td width="2%" class="header"><input type="checkbox" name="toggleAllC" onclick="ToggleAll(this);"></td>
            <td width="44%" class="header"><b><a href="{$link_name}">{$hdr_name}{$img_name}</a></b></td>
            <td width="10%" class="header"><b><a href="{$link_size}">{$hdr_size}{$img_size}</a></b></td>
            <td width="16%" class="header"><b><a href="{$link_type}">{$hdr_type}{$img_type}</a></b></td>
            <td width="14%" class="header"><b><a href="{$link_mod}">{$hdr_mod}{$img_mod}</a></b></td>
            <td width="8%"  class="header"><b>{$hdr_perm}</b></td>
            <td width="6%"  class="header"><b>{$hdr_action}</b></td>
        </tr>
        <tr><td colspan="7"><hr></td></tr>
    HTML;

    // make & print Table using lists
    // when sorting by name: dirs first; for all other orders: files first
    if ($GLOBALS["order"] == "name")
        print_table($dir, make_list($dir_list, $file_list));
    else
        print_table($dir, make_list($file_list, $dir_list));

    // print number of items & total filesize
    $free        = parse_file_size(disk_free_space("/"));
    $total_size  = parse_file_size($tot_file_size);
    $lbl_items   = $GLOBALS["messages"]["miscitems"];
    $lbl_free    = $GLOBALS["messages"]["miscfree"];

    echo <<<HTML
        <tr><td colspan="7"><hr></td></tr>
        <tr>
            <td class="header"></td>
            <td class="header">{$num_items} {$lbl_items} ({$lbl_free}: {$free})</td>
            <td class="header">{$total_size}</td>
            <td class="header" colspan="4"></td>
        </tr>
        <tr><td colspan="7"><hr></td></tr>
    </form></table>
    <script>
        // Uncheck all items (to avoid problems with new items)
        var ml = document.selform;
        var len = ml.elements.length;
        for (var i = 0; i < len; ++i) {
            var e = ml.elements[i];
            if (e.name == "selitems[]" && e.checked == true) {
                e.checked = false;
            }
        }

        $("tr.rowdata")
            .hover(
                function() {
                    $(this).addClass("hover");
                },
                function() {
                    $(this).removeClass("hover");
                }
            )
            .click(
                function(event) {
                    if (event.target.nodeName === 'TD') {
                        a = $(this).find('input');
                        a.prop('checked', function() {
                            return !this.checked;
                        })
                    }
                }
            );
    </script>
    HTML;
    }

            // *** HELPER FUNCTIONS

            function _print_edit_buttons($dir)
            {
                // for the copy button the user must have create and read rights
                _print_link("copy", permissions_grant_all($dir, NULL, array("create", "read")), $dir, NULL);
                _print_link("move", permissions_grant($dir, NULL, "change"), $dir, NULL);
                _print_link("delete", permissions_grant($dir, NULL, "delete"), $dir, NULL);
                _print_link("upload", permissions_grant($dir, NULL, "create") && ini_get("file_uploads"), $dir, NULL);
                _print_link(
                    "archive",
                    permissions_grant_all($dir, NULL, array("create", "read"))
                        && ($GLOBALS["zip"] || $GLOBALS["tar"] || $GLOBALS["tgz"]),
                    $dir,
                    NULL
                );
            }

            /**
  print out an button link in the toolbar.

  if $allow is set, make this button active and work, otherwise print
  an inactive button.
             */
            function _print_link($function, $allow, $dir, $item)
            {
                // the list of all available button and the coresponding data
                $functions = array(
                    "copy" => array(
                        "jfunction" => "javascript:Copy();",
                        "image" => $GLOBALS["baricons"]["copy"],
                        "imagedisabled" => $GLOBALS["baricons"]["notcopy"],
                        "message" => $GLOBALS["messages"]["copylink"]
                    ),
                    "move" => array(
                        "jfunction" => "javascript:Move();",
                        "image" => $GLOBALS["baricons"]["move"],
                        "imagedisabled" => $GLOBALS["baricons"]["notmove"],
                        "message" => $GLOBALS["messages"]["movelink"]
                    ),
                    "delete" => array(
                        "jfunction" => "javascript:Delete();",
                        "image" => $GLOBALS["baricons"]["delete"],
                        "imagedisabled" => $GLOBALS["baricons"]["notdelete"],
                        "message" => $GLOBALS["messages"]["dellink"]
                    ),
                    "upload" => array(
                        "jfunction" => make_link("upload", $dir, NULL),
                        "image" => $GLOBALS["baricons"]["upload"],
                        "imagedisabled" => $GLOBALS["baricons"]["notupload"],
                        "message" => $GLOBALS["messages"]["uploadlink"]
                    ),
                    "archive" => array(
                        "jfunction" => "javascript:Archive();",
                        "image" => $GLOBALS["baricons"]["archive"],
                        "message" => $GLOBALS["messages"]["comprlink"]
                    ),
                    "admin" => array(
                        "jfunction" => make_link("admin", $dir, NULL),
                        "image" => $GLOBALS["baricons"]["admin"],
                        "message" => $GLOBALS["messages"]["adminlink"]
                    ),
                    "logout" => array(
                        "jfunction" => make_link("logout", NULL, NULL),
                        "image" => $GLOBALS["baricons"]["logout"],
                        "imagedisabled" => "_img/_logout_.gif",
                        "message" => $GLOBALS["messages"]["logoutlink"]
                    ),
                    "edit" => array(
                        "jfunction" => make_link("edit", $dir, $item),
                        "image" => $GLOBALS["baricons"]["edit"],
                        "imagedisabled" => $GLOBALS["baricons"]["notedit"],
                        "message" => $GLOBALS["messages"]["editlink"]
                    ),
                    "unzip" => array(
                        "jfunction" => make_link("unzip", $dir, $item),
                        "image" => $GLOBALS["baricons"]["unzip"],
                        "imagedisabled" => $GLOBALS["baricons"]["notunzip"],
                        "message" => $GLOBALS["messages"]["unziplink"]
                    ),
                    "download" => array(
                        "jfunction" => make_link("download", $dir, $item),
                        "image" => $GLOBALS["baricons"]["download"],
                        "imagedisabled" => $GLOBALS["baricons"]["notdownload"],
                        "message" => $GLOBALS["messages"]["downlink"]
                    ),
                    "download_selected" => array(
                        "jfunction" => "javascript:DownloadSelected();",
                        "image" => $GLOBALS["baricons"]["download"],
                        "imagedisabled" => $GLOBALS["baricons"]["notdownload"],
                        "message" => $GLOBALS["messages"]["download_selected"]
                    ),
                );

                // determine the functio nof this button and it's data
                $values = $functions[$function];

                // make an active link if the access is allowed
                if ($allow)
                {
                    echo "<TD><A HREF=\"" . $values["jfunction"] . "\"><IMG border=\"0\" width=\"16\" height=\"16\" ";
                    echo "align=\"ABSMIDDLE\" src=\"" . $values["image"] . "\" ALT=\"" . $values["message"];
                    echo "\" TITLE=\"" . $values["message"] . "\"></A></TD>\n";
                    return;
                }

                if (!isset($values["imagedisabled"]))
                    return;

                // make an inactive link if the access is forbidden
                echo "<TD><IMG border=\"0\" width=\"16\" height=\"16\" align=\"ABSMIDDLE\" ";
                echo "src=\"" . $values["imagedisabled"] . "\" ALT=\"" . $values["message"] . "\" TITLE=\"";
                echo $values["message"] . "\"></TD>\n";
            }

            function _get_link_info($dir, $item)
            {
                $type = get_mime_type($dir, $item, "type");
                if (is_array($type))
                {
                    $type = $type[0];
                }

                if (! file_exists(get_abs_item($dir, $item)))
                {
                    return '<span style="background:red;">' . $type . '</span>';
                }
                return $type;
            }

            /**
  The breadcrumbs function will take the user's current path and build a breadcrumb.

  A breadcrums is a list of links for each directory in the current path.

  @param    $curdir is a string containing what will usually be the users
            current directory.  %displayseparator is optional and contains a
            string that will be displayed betweenach crumb.

 Typical syntax:

     echo breadcrumbs($dir, ">>");
     show_header($GLOBALS["messages"]["actdir"].":".breadcrumbs($dir));
             */
            function _breadcrumbs($curdir, $displayseparator = ' &raquo; ')
            {
                //Get localized name for the Home directory
                $homedir = $GLOBALS["messages"]["homelink"];

                // Initialize first crumb and set it to the home directory.
                $breadcrumbs[] = "<a href=\"" . make_link("list", "", NULL) . "\">$homedir</a>";

                // Take the current directory and split the string into an array at each '/'.
                $patharray = explode('/', $curdir);

                // Find out the index for the last value in our path array
                $lastx = array_keys($patharray);
                $last = end($lastx);

                // Build the rest of the breadcrumbs
                $crumbdir = "";
                foreach ($patharray as $x => $crumb)
                {
                    // Add a new directory to the directory list so the link has the
                    // correct path to the current crumb.
                    $crumbdir = $crumbdir . $crumb;
                    if ($x != $last):
                        // If we are not on the last index, then create a link using $crumb
                        // as the text.

                        $breadcrumbs[] = "<a href=\"" . make_link("list", $crumbdir, NULL) . "\">" . htmlspecialchars($crumb) . "</a>";

                        // Add a separator between our crumbs.
                        $crumbdir = $crumbdir . DIRECTORY_SEPARATOR;
                    else:
                        // Don't create a link for the final crumb.  Just display the crumb name.
                        $breadcrumbs[] = htmlspecialchars($crumb);
                    endif;
                }

                // Build temporary array into one string.
                return implode($displayseparator, $breadcrumbs);
            }

                ?>