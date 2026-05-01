<?php

/**
 footer for html-page
 */
function show_footer()
{
?>
    <hr>
    <small>
        <a class="title" href="https://github.com/realtimeprojects/quixplorer" target="_blank"> QuiXplorer Version 2.6.0</a>
    </small>
    <small>Thanks for usage!</small>
    <?php show_login(); ?>
    </center>
    </body>

    </html>
<?php
}

/**
  If no user is logged in, show the login option
 */
function show_login()
{
    if (login_is_user_logged_in())
        return;
    $login_link  = make_link("login", NULL);
    $btn_login   = htmlspecialchars($GLOBALS['messages']['btnlogin'], ENT_QUOTES, 'UTF-8');
    echo <<<HTML
    <small> - <a href="{$login_link}">{$btn_login}</a></small>
    HTML;
}
?>