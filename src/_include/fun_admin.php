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

     The Original Code is fun_admin.php, released on 2003-03-31.

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
    Administrative Functions

    Have Fun...
------------------------------------------------------------------------------*/

require "./_include/permissions.php";

/**
 * Change Password & Manage Users Form
 */
function admin($admin, $dir)
{
    show_header($GLOBALS["messages"]["actadmin"]);

    // Javascript functions:
    include "./_include/js_admin.php";

    $admin_link       = make_link("admin", $dir, NULL);
    $list_link        = make_link("list", $dir, NULL);
    $msg_actchpwd     = $GLOBALS["messages"]["actchpwd"];
    $msg_oldpass      = $GLOBALS["messages"]["miscoldpass"];
    $msg_newpass      = $GLOBALS["messages"]["miscnewpass"];
    $msg_confnewpass  = $GLOBALS["messages"]["miscconfnewpass"];
    $btn_change       = htmlspecialchars($GLOBALS["messages"]["btnchange"], ENT_QUOTES, 'UTF-8');
    $btn_close        = htmlspecialchars($GLOBALS["messages"]["btnclose"], ENT_QUOTES, 'UTF-8');

    echo <<<HTML
    <br>
    <hr style="width:95%">
    <table width="350">
        <tr><td colspan="2" class="header"><b>{$msg_actchpwd}:</b></td></tr>
        <form name="chpwd" action="{$admin_link}" method="post">
            <input type="hidden" name="action2" value="chpwd">
            <tr><td>{$msg_oldpass}: </td><td align="right"><input type="password" name="oldpwd" size="25"></td></tr>
            <tr><td>{$msg_newpass}: </td><td align="right"><input type="password" name="newpwd1" size="25"></td></tr>
            <tr><td>{$msg_confnewpass}: </td><td align="right"><input type="password" name="newpwd2" size="25"></td></tr>
            <tr><td colspan="2" align="right">
                <input type="submit" value="{$btn_change}" onclick="return check_pwd();">
            </td></tr>
        </form>
    </table>
    HTML;

    // Edit / Add / Remove User
    if ($admin)
    {
        $msg_actusers      = $GLOBALS["messages"]["actusers"];
        $msg_miscuseritems = $GLOBALS["messages"]["miscuseritems"];
        $btn_add           = htmlspecialchars($GLOBALS["messages"]["btnadd"], ENT_QUOTES, 'UTF-8');
        $btn_edit          = htmlspecialchars($GLOBALS["messages"]["btnedit"], ENT_QUOTES, 'UTF-8');
        $btn_remove        = htmlspecialchars($GLOBALS["messages"]["btnremove"], ENT_QUOTES, 'UTF-8');
        $add_link          = $admin_link . "&action2=adduser";

        echo <<<HTML
        <hr style="width:95%">
        <table width="350">
            <tr><td colspan="6" class="header" nowrap><b>{$msg_actusers}:</b></td></tr>
            <tr><td colspan="5">{$msg_miscuseritems}</td></tr>
            <form name="userform" action="{$admin_link}" method="post">
                <input type="hidden" name="action2" value="edituser">
        HTML;

        $cnt = count($GLOBALS["users"]);
        for ($i = 0; $i < $cnt; ++$i)
        {
            $user      = $GLOBALS["users"][$i][0];
            $user_disp = strlen($user) > 15 ? substr($user, 0, 12) . "..." : $user;
            $home      = $GLOBALS["users"][$i][2];
            $home_disp = strlen($home) > 30 ? substr($home, 0, 27) . "..." : $home;
            $user_enc  = htmlspecialchars($user, ENT_QUOTES, 'UTF-8');
            $checked   = ($i == 0) ? " checked" : "";
            $yn_hidden = $GLOBALS["users"][$i][4] ? $GLOBALS["messages"]["miscyesno"][2] : $GLOBALS["messages"]["miscyesno"][3];
            $perms_val = $GLOBALS["users"][$i][6];
            $yn_active = $GLOBALS["users"][$i][7] ? $GLOBALS["messages"]["miscyesno"][2] : $GLOBALS["messages"]["miscyesno"][3];

            echo <<<HTML
                <tr>
                    <td width="1%"><input type="radio" name="user" value="{$user_enc}"{$checked}></td>
                    <td width="30%">{$user_disp}</td>
                    <td width="60%">{$home_disp}</td>
                    <td width="3%">{$yn_hidden}</td>
                    <td width="3%">{$perms_val}</td>
                    <td width="3%">{$yn_active}</td>
                </tr>
            HTML;
        }

        echo <<<HTML
                <tr><td colspan="6" align="right">
                    <input type="button" value="{$btn_add}" onclick="location='{$add_link}';">
                    <input type="button" value="{$btn_edit}" onclick="Edit();">
                    <input type="button" value="{$btn_remove}" onclick="Delete();">
                </td></tr>
            </form>
        </table>
        HTML;
    }

    echo <<<HTML
    <hr style="width:95%">
    <input type="button" value="{$btn_close}" onclick="location='{$list_link}';"><br><br>
    <script>
        if (document.chpwd) document.chpwd.oldpwd.focus();
    </script>
    HTML;
    }

            /**
             * Change Password
             */
            function changepwd($dir)
            {
                $pwd = md5(stripslashes($GLOBALS['__POST']["oldpwd"]));
                $pw1 = $GLOBALS['__POST']["newpwd1"];
                $pw2 = $GLOBALS['__POST']["newpwd2"];
                if ($pw1 != $pw2)
                {
                    show_error($GLOBALS["error_msg"]["miscnopassmatch"]);
                }

                $data = user_find($_SESSION["s_user"], $pwd);
                if ($data == NULL)
                    show_error($GLOBALS["error_msg"]["miscnouserpass"]);

                $data[1] = md5(stripslashes($pw1));
                if (!user_update($data[0], $data))
                    show_error($data[0] . ": " . $GLOBALS["error_msg"]["chpass"]);
                user_activate($data[0], NULL);

                header("location: " . make_link("list", $dir, NULL));
            }

            /**
             * Add User
             */
            function adduser($dir)
            {
                if (isset($GLOBALS['__POST']["confirm"]) && $GLOBALS['__POST']["confirm"] == "true")
                {
                    $user = stripslashes($GLOBALS['__POST']["user"]);
                    if ($user == "" || $GLOBALS['__POST']["home_dir"] == "")
                    {
                        show_error($GLOBALS["error_msg"]["miscfieldmissed"]);
                    }
                    if ($GLOBALS['__POST']["pass1"] != $GLOBALS['__POST']["pass2"]) show_error($GLOBALS["error_msg"]["miscnopassmatch"]);
                    $data = user_find($user, NULL);
                    if ($data != NULL)
                        show_error($user . ": " . $GLOBALS["error_msg"]["miscuserexist"]);

                    // determine the user permissions
                    $permissions = _eval_permissions();

                    $data = array(
                        $user,
                        md5(stripslashes($GLOBALS['__POST']["pass1"])),
                        stripslashes($GLOBALS['__POST']["home_dir"]),
                        stripslashes($GLOBALS['__POST']["home_url"]),
                        $GLOBALS['__POST']["show_hidden"],
                        stripslashes($GLOBALS['__POST']["no_access"]),
                        $permissions,
                        $GLOBALS['__POST']["active"]
                    );

                    if (!user_add($data)) show_error($user . ": " . $GLOBALS["error_msg"]["adduser"]);
                    header("location: " . make_link("admin", $dir, NULL));
                    return;
                }

                show_header($GLOBALS["messages"]["actadmin"] . ": " . $GLOBALS["messages"]["miscadduser"]);

                // Javascript functions:
                include "./_include/js_admin2.php";

                $add_link    = make_link("admin", $dir, NULL) . "&action2=adduser";
                $cancel_link = make_link("admin", $dir, NULL);
                $home_dir_v  = htmlspecialchars($GLOBALS["home_dir"], ENT_QUOTES, 'UTF-8');
                $home_url_v  = htmlspecialchars($GLOBALS["home_url"], ENT_QUOTES, 'UTF-8');
                $lbl_user    = $GLOBALS["messages"]["miscusername"];
                $lbl_pass    = $GLOBALS["messages"]["miscpassword"];
                $lbl_conf    = $GLOBALS["messages"]["miscconfpass"];
                $lbl_hdir    = $GLOBALS["messages"]["mischomedir"];
                $lbl_hurl    = $GLOBALS["messages"]["mischomeurl"];
                $lbl_hidden  = $GLOBALS["messages"]["miscshowhidden"];
                $lbl_pat     = $GLOBALS["messages"]["mischidepattern"];
                $lbl_perms   = $GLOBALS["messages"]["miscperms"];
                $lbl_active  = $GLOBALS["messages"]["miscactive"];
                $opt_yes     = $GLOBALS["messages"]["miscyesno"][0];
                $opt_no      = $GLOBALS["messages"]["miscyesno"][1];
                $btn_add     = htmlspecialchars($GLOBALS["messages"]["btnadd"], ENT_QUOTES, 'UTF-8');
                $btn_cancel  = htmlspecialchars($GLOBALS["messages"]["btncancel"], ENT_QUOTES, 'UTF-8');

                echo <<<HTML
                <form name="adduser" action="{$add_link}" method="post">
                    <input type="hidden" name="confirm" value="true">
                    <br>
                    <table width="450">
                        <tr><td>{$lbl_user}:</td><td align="right"><input type="text" name="user" size="30"></td></tr>
                        <tr><td>{$lbl_pass}:</td><td align="right"><input type="password" name="pass1" size="30"></td></tr>
                        <tr><td>{$lbl_conf}:</td><td align="right"><input type="password" name="pass2" size="30"></td></tr>
                        <tr><td>{$lbl_hdir}:</td><td align="right"><input type="text" name="home_dir" size="30" value="{$home_dir_v}"></td></tr>
                        <tr><td>{$lbl_hurl}:</td><td align="right"><input type="text" name="home_url" size="30" value="{$home_url_v}"></td></tr>
                        <tr>
                            <td>{$lbl_hidden}:</td>
                            <td align="right"><select name="show_hidden">
                                <option value="0">{$opt_no}</option>
                                <option value="1">{$opt_yes}</option>
                            </select></td>
                        </tr>
                        <tr><td>{$lbl_pat}:</td><td align="right"><input type="text" name="no_access" size="30" value="^\.ht"></td></tr>
                        <tr><td>{$lbl_perms}:</td><td align="right">
                HTML;

                admin_print_permissions(NULL);

                echo <<<HTML
                        </td></tr>
                        <tr>
                            <td>{$lbl_active}:</td>
                            <td align="right"><select name="active">
                                <option value="1">{$opt_yes}</option>
                                <option value="0">{$opt_no}</option>
                            </select></td>
                        </tr>
                        <tr><td colspan="2" align="right">
                            <input type="submit" value="{$btn_add}" onclick="return check_pwd();">
                            <input type="button" value="{$btn_cancel}" onclick="location='{$cancel_link}';">
                        </td></tr>
                    </form>
                </table>
                <br>
                <script>
                    if (document.adduser) document.adduser.user.focus();
                </script>
                HTML;
            }

            /**
             * edit user
             */
            function edituser($dir)
            {
                // Determine the user name from the post data
                $user = stripslashes($GLOBALS['__POST']["user"]);

                // try to find the user
                $data = user_find($user, NULL);
                if ($data == NULL) show_error($user . ": " . $GLOBALS["error_msg"]["miscnofinduser"]);
                if ($self = ($user == $GLOBALS['__SESSION']["s_user"])) $dir = "";

                if (isset($GLOBALS['__POST']["confirm"]) && $GLOBALS['__POST']["confirm"] == "true")
                {
                    $nuser = stripslashes($GLOBALS['__POST']["nuser"]);
                    if ($nuser == "" || $GLOBALS['__POST']["home_dir"] == "")
                    {
                        show_error($GLOBALS["error_msg"]["miscfieldmissed"]);
                    }

                    if (isset($GLOBALS['__POST']["chpass"]) && $GLOBALS['__POST']["chpass"] == "true")
                    {
                        if ($GLOBALS['__POST']["pass1"] != $GLOBALS['__POST']["pass2"])
                            show_error($GLOBALS["error_msg"]["miscnopassmatch"]);
                        $pass = md5(stripslashes($GLOBALS['__POST']["pass1"]));
                    }
                    else
                    {
                        $pass = $data[1];
                    }

                    if ($self)
                        $GLOBALS['__POST']["active"] = 1;

                    // determine the user permissions
                    $permissions = _eval_permissions();

                    // determine the new user data
                    $data = array(
                        $nuser,
                        $pass,
                        stripslashes($GLOBALS['__POST']["home_dir"]),
                        stripslashes($GLOBALS['__POST']["home_url"]),
                        $GLOBALS['__POST']["show_hidden"],
                        stripslashes($GLOBALS['__POST']["no_access"]),
                        $permissions,
                        $GLOBALS['__POST']["active"]
                    );

                    if (!user_update($user, $data))
                        show_error($user . ": " . $GLOBALS["error_msg"]["saveuser"]);
                    if ($self)
                        user_activate($nuser, NULL);

                    header("location: " . make_link("admin", $dir, NULL));
                    return;
                }

                show_header($GLOBALS["messages"]["actadmin"] . ": " . sprintf($GLOBALS["messages"]["miscedituser"], $data[0]));

                // Javascript functions:
                include "./_include/js_admin3.php";

                $edit_link    = make_link("admin", $dir, NULL) . "&action2=edituser";
                $cancel_link  = make_link("admin", $dir, NULL);
                $user_enc     = htmlspecialchars($data[0], ENT_QUOTES, 'UTF-8');
                $home_dir_v   = htmlspecialchars($data[2], ENT_QUOTES, 'UTF-8');
                $home_url_v   = htmlspecialchars($data[3], ENT_QUOTES, 'UTF-8');
                $no_access_v  = htmlspecialchars($data[5], ENT_QUOTES, 'UTF-8');
                $lbl_user     = $GLOBALS["messages"]["miscusername"];
                $lbl_confpass = $GLOBALS["messages"]["miscconfpass"];
                $lbl_confnew  = $GLOBALS["messages"]["miscconfnewpass"];
                $lbl_chpass   = $GLOBALS["messages"]["miscchpass"];
                $lbl_hdir     = $GLOBALS["messages"]["mischomedir"];
                $lbl_hurl     = $GLOBALS["messages"]["mischomeurl"];
                $lbl_hidden   = $GLOBALS["messages"]["miscshowhidden"];
                $lbl_pat      = $GLOBALS["messages"]["mischidepattern"];
                $lbl_perms    = $GLOBALS["messages"]["miscperms"];
                $lbl_active   = $GLOBALS["messages"]["miscactive"];
                $opt_yes      = $GLOBALS["messages"]["miscyesno"][0];
                $opt_no       = $GLOBALS["messages"]["miscyesno"][1];
                $btn_save     = htmlspecialchars($GLOBALS["messages"]["btnsave"], ENT_QUOTES, 'UTF-8');
                $btn_cancel   = htmlspecialchars($GLOBALS["messages"]["btncancel"], ENT_QUOTES, 'UTF-8');
                $sel_hidden   = $data[4] ? " selected" : "";
                $sel_inactive = $data[7] ? "" : " selected";
                $disabled     = $self ? " disabled" : "";

                echo <<<HTML
                <form name="edituser" action="{$edit_link}" method="post">
                    <input type="hidden" name="confirm" value="true">
                    <input type="hidden" name="user" value="{$user_enc}">
                    <br>
                    <table width="450">
                        <tr><td>{$lbl_user}:</td><td align="right"><input type="text" name="nuser" size="30" value="{$user_enc}"></td></tr>
                        <tr><td>{$lbl_confpass}:</td><td align="right"><input type="password" name="pass1" size="30"></td></tr>
                        <tr><td>{$lbl_confnew}:</td><td align="right"><input type="password" name="pass2" size="30"></td></tr>
                        <tr><td>{$lbl_chpass}:</td><td align="right"><input type="checkbox" name="chpass" value="true"></td></tr>
                        <tr><td>{$lbl_hdir}:</td><td align="right"><input type="text" name="home_dir" size="30" value="{$home_dir_v}"></td></tr>
                        <tr><td>{$lbl_hurl}:</td><td align="right"><input type="text" name="home_url" size="30" value="{$home_url_v}"></td></tr>
                        <tr>
                            <td>{$lbl_hidden}:</td>
                            <td align="right"><select name="show_hidden">
                                <option value="0">{$opt_no}</option>
                                <option value="1"{$sel_hidden}>{$opt_yes}</option>
                            </select></td>
                        </tr>
                        <tr><td>{$lbl_pat}:</td><td align="right"><input type="text" name="no_access" size="30" value="{$no_access_v}"></td></tr>
                        <tr><td>{$lbl_perms}:</td><td align="right">
                HTML;

                admin_print_permissions($data[0]);

                echo <<<HTML
                        </td></tr>
                        <tr>
                            <td>{$lbl_active}:</td>
                            <td align="right"><select name="active"{$disabled}>
                                <option value="1">{$opt_yes}</option>
                                <option value="0"{$sel_inactive}>{$opt_no}</option>
                            </select></td>
                        </tr>
                        <tr><td colspan="2" align="right">
                            <input type="submit" value="{$btn_save}" onclick="return check_pwd();">
                            <input type="button" value="{$btn_cancel}" onclick="location='{$cancel_link}';">
                        </td></tr>
                    </form>
                </table>
                <br>
                HTML;
            }

            /**
             * remove user
             */
            function removeuser($dir)
            {
                $user = stripslashes($GLOBALS['__POST']["user"]);
                if ($user == $GLOBALS['__SESSION']["s_user"]) show_error($GLOBALS["error_msg"]["miscselfremove"]);
                if (!user_remove($user)) show_error($user . ": " . $GLOBALS["error_msg"]["deluser"]);

                header("location: " . make_link("admin", $dir, NULL));
            }
            //------------------------------------------------------------------------------
            function show_admin($dir)
            {
                $admin = permissions_grant(NULL, NULL, "admin");

                if (!login_is_user_logged_in())
                    show_error($GLOBALS["error_msg"]["miscnofunc"]);
                if (!$admin && !permissions_grant(NULL, NULL, "password"))
                    show_error($GLOBALS["error_msg"]["accessfunc"]);

                if (isset($GLOBALS['__GET']["action2"])) $action2 = $GLOBALS['__GET']["action2"];
                elseif (isset($GLOBALS['__POST']["action2"])) $action2 = $GLOBALS['__POST']["action2"];
                else $action2 = "";

                switch ($action2)
                {
                    case "chpwd":
                        changepwd($dir);
                        break;
                    case "adduser":
                        if (!$admin) show_error($GLOBALS["error_msg"]["accessfunc"]);
                        adduser($dir);
                        break;
                    case "edituser":
                        if (!$admin) show_error($GLOBALS["error_msg"]["accessfunc"]);
                        edituser($dir);
                        break;
                    case "rmuser":
                        if (!$admin) show_error($GLOBALS["error_msg"]["accessfunc"]);
                        removeuser($dir);
                        break;
                    default:
                        admin($admin, $dir);
                }
            }
//------------------------------------------------------------------------------
            /**
    print out the html permission table to modify user permissions.

    the name of the permission values are determined via the language
    interface. In case of there is no entry in the language table for
    this permission, the function uses the original permission name.
             */
            function admin_print_permissions($username)
            {
                $permvalues = permissions_get();
                echo "<table>\n";
                foreach ($permvalues as $name => $value)
                {
                    $checked  = permissions_grant_user($username, NULL, NULL, $name) ? " checked" : "";
                    $disabled = (($username == "admin") && ($name == "admin")) ? " disabled" : "";
                    $desc     = $GLOBALS["messages"]["miscpermissions"][$name][0] ?? $name;
                    $tooltip  = htmlspecialchars($GLOBALS["messages"]["miscpermissions"][$name][1] ?? '', ENT_QUOTES, 'UTF-8');
                    $val_enc  = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');

                    echo <<<HTML
                    <tr><td>
                        <input type="checkbox" title="{$tooltip}" name="permsettings[]" value="{$val_enc}"{$checked}{$disabled}>
                        {$desc}
                    </td></tr>
                    HTML;
                }
                echo "</table>\n";
            }

            /**
  this function evaluates the changed permissions out of the html input form and convert this permissions
  into the permission values for storing them into the user database.
             */
            function    _eval_permissions()
            {
                $perms = $GLOBALS['__POST']["permsettings"];
                $permissions = 0;
                if (!isset($perms))
                    return $permissions;

                foreach ($perms as $values)
                {
                    $permissions += $values;
                }

                return $permissions;
            }

                ?>