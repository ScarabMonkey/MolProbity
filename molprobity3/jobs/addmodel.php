<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file takes a 'raw' PDB file and prepares it to be a new model for
    the session.

INPUTS (via $_SESSION['bgjob']):
    tmpPdb          the (temporary) file where the upload is stored.
    origName        the name of the file on the user's system.
    pdbCode         the PDB or NDB code for the molecule
    (EITHER pdbCode OR tmpPdb and origName will be set)
    
    isCnsFormat     true if the user thinks he has CNS atom names
    ignoreSegID     true if the user wants to never map segIDs to chainIDs

OUTPUTS (via $_SESSION['bgjob']):
    Adds a new entry to $_SESSION['models'].
    newModel        the ID of the model just added, or null on failure
    labbookEntry    the labbook entry number for adding this new model

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
    require_once(MP_BASE_DIR.'/lib/labbook.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    session_id( $_SERVER['argv'][1] );
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    #mpSessReadOnly();
// 5. Set up reasonable values to emulate CLI behavior if we're CGI
    set_time_limit(0); // don't want to bail after 30 sec!
// 6. Record this PHP script's PID in case it needs to be killed.
    $_SESSION['bgjob']['processID'] = posix_getpid();
    mpSaveSession();
    
#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################
if(isset($_SESSION['bgjob']['pdbCode']))
{
    // Better upper case it to make sure we find the file in the database
    $code = strtoupper($_SESSION['bgjob']['pdbCode']);
    
    if(preg_match('/^[0-9A-Z]{4}$/i', $code))
    {
        setProgress(array("pdb" => "Retrieve PDB file $code over the network"), "pdb");
        $tmpfile = getPdbModel($code);
        $fileSource = "http://www.rcsb.org/pdb/";
    }
    else if(preg_match('/^[0-9A-Z]{6,10}$/i', $code))
    {
        setProgress(array("pdb" => "Retrieve NDB file $code over the network (takes more than 30 sec)"), "pdb");
        $tmpfile = getNdbModel($code);
        $fileSource = "http://ndbserver.rutgers.edu/";
    }
    else $tmpfile == null;
    
    if($tmpfile == null)
    {
        $_SESSION['bgjob']['newModel'] = null;
    }
    else
    {
        $id = addModel($tmpfile,
            strtolower("$code.pdb"), // lower case is nicer for readability
            $_SESSION['bgjob']['isCnsFormat'],
            $_SESSION['bgjob']['ignoreSegID']);
        
        $_SESSION['bgjob']['newModel'] = $id;
        
        // Clean up temp files
        unlink($tmpfile);
    }
}
else
{
    // Remove illegal chars from the upload file name
    $origName = censorFileName($_SESSION['bgjob']['origName']);
    $fileSource = "local disk";
    
    $id = addModel($_SESSION['bgjob']['tmpPdb'],
        $origName,
        $_SESSION['bgjob']['isCnsFormat'],
        $_SESSION['bgjob']['ignoreSegID']);
    
    $_SESSION['bgjob']['newModel'] = $id;
    
    // Clean up temp files
    unlink($_SESSION['bgjob']['tmpPdb']);
}

// Automatic labbook entry
if($_SESSION['bgjob']['newModel'])
{
    $id = $_SESSION['bgjob']['newModel'];
    $model = $_SESSION['models'][ $id ];
    
    // Make a thumbnail kin for the lab notebook
    $modelDir = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
    $kinDir = $_SESSION['dataDir'].'/'.MP_DIR_KINS;
    $kinURL = $_SESSION['dataURL'].'/'.MP_DIR_KINS;
    if(!file_exists($kinDir)) mkdir($kinDir, 0777);
    exec("prekin -cass -colornc $modelDir/$model[pdb] > $kinDir/$model[prefix]thumbnail.kin");
    
    $s = "";
    $s .= "<div class='side_options'>\n";
    $s .= "<applet code='Magelet.class' archive='magejava.jar' width='150' height='150'>\n";
    $s .= "  <param name='kinemage' value='$kinURL/$model[prefix]thumbnail.kin'>\n";
    $s .= "  <param name='buttonpanel' value='no'>\n";
    $s .= "</applet>\n";
    $s .= "</div>\n";

    $s .= "Your file from $fileSource was uploaded as $model[pdb]\n";
    $details = describePdbStats($model['stats'], true);
    $s .= "<ul>\n";
    foreach($details as $detail) $s .= "<li>$detail</li>\n";
    $s .= "</ul>\n";
    
    if($model['segmap'])
    {
        $s .= "<p><div class='alert'>Because this model had more segment IDs than chainIDs,\n";
        $s .= "the segment IDs were automagically turned into new chain IDs for this model.\n";
        $s .= "If you would prefer the original chain IDs, please check the <b>Ignore segID field</b>\n";
        $s .= "on the file upload page.</div></p>";
    }
    
    $_SESSION['bgjob']['labbookEntry'] = addLabbookEntry(
        "$model[pdb] added",
        $s,
        $id,
        "auto"
    );
}

############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
