<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page allows users to bookmark their session or clean up their files.
*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// PHP's working dir is set by the script that is begins execution with.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(getcwd().'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/labbook.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

    echo mpPageHeader("Finish session", "logout");
?>

<p>You can bookmark this page and come back later!
<br>Data good until: <?php echo formatDayTime( time() + mpSessTimeToLive(session_id()) ); ?>

<form method="post" action="finish_destroy.php">
<?php echo postSessionID(); ?>
<input type="hidden" name="confirm" value="1">
<br>Thank you for helping us reclaim disk space!
<br>This action cannot be undone:
<input type="submit" name="cmd" value="Destroy all my files and log me out">
</form>

<?php echo mpPageFooter(); ?>