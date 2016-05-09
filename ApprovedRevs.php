<?php

if ( !defined( 'MEDIAWIKI' ) ) die();

/**
 *
 * WikiRights ApprovedRevs extension
 * Loosely based on https://www.mediawiki.org/wiki/Extension:ApprovedRevs by Yaron Koren
 *
 * @file
 * @ingroup Extensions
 * @compat >= MW 1.21
 *
 * @author Dror S. [FFS]
 * @license GNU General Public Licence 2.0 or later
 * @copyright © 2015 Dror S. & Kol-Zchut Ltd.
 */

define( 'APPROVED_REVS_VERSION', '0.1.2' );

// credits
$wgExtensionCredits['other'][] = array(
	'path'            => __FILE__,
	'name'            => 'Approved Revisions For Kol-Zchut',
	'version'         => APPROVED_REVS_VERSION,
	'author'          => 'Dror S. [FFS] ([http://www.kolzchut.org.il Kol-Zchut])',
	'url'             => 'https://github.com/kolzchut/mediawiki-extensions-WRApprovedRevs',
	'descriptionmsg'  => 'approvedrevs-desc',
	'license-name'    => 'GPL-2.0+'
);

// global variables
$wgApprovedRevsShowOnlyToMembers = false;
$wgApprovedRevsNamespaces = false;
//$wgApprovedRevsNamespaces = array( NS_MAIN, NS_USER );

// internationalization
$wgExtensionMessagesFiles['ApprovedRevs'] = __DIR__ . '/ApprovedRevs.i18n.php';
$wgExtensionMessagesFiles['ApprovedRevsAlias'] = __DIR__ . '/ApprovedRevs.i18n.alias.php';

// register all classes
$wgAutoloadClasses['ApprovedRevs'] = __DIR__ . '/ApprovedRevs_body.php';
$wgAutoloadClasses['ApprovedRevsHooks'] = __DIR__ . '/ApprovedRevs.hooks.php';
$wgAutoloadClasses['AssignToProjectAction'] = __DIR__ . '/ApprovedRevs_AssignToProjectAction.php';
$wgAutoloadClasses['ApproveProjectPageAction'] = __DIR__ . '/ApprovedRevs_ApproveProjectPageAction.php';
$wgAutoloadClasses['SpecialApprovedRevs'] = __DIR__ . '/SpecialApprovedRevs.php';
	$wgSpecialPages['ApprovedRevs'] = 'SpecialApprovedRevs';
	$wgSpecialPageGroups['ApprovedRevs'] = 'pages';

// hooks
$wgHooks['LoadExtensionSchemaUpdates'][] = 'ApprovedRevsHooks::describeDBSchema';
$wgHooks['ArticleViewHeader'][] = 'ApprovedRevsHooks::onArticleViewHeader';
$wgHooks['SkinTemplateNavigation'][] = 'ApprovedRevsHooks::onSkinTemplateNavigation';
$wgHooks['PageContentSaveComplete'][] = 'ApprovedRevsHooks::onPageContentSaveComplete';
//$wgHooks['ParserAfterTidy'][] = 'ApprovedRevsHooks::onParserAfterTidy';
$wgHooks['ContentAlterParserOutput'][] = 'ApprovedRevsHooks::onContentAlterParserOutput';




// page actions
$wgActions['assigntoproject'] = 'AssignToProjectAction';
$wgActions['approveprojectpage'] = 'ApproveProjectPageAction';

// user rights
$wgAvailableRights[] = 'assigntoproject'; # assign a page to a project
$wgAvailableRights[] = 'approveprojectonbehalf'; # assign a page to a project on behalf of someone else
$wgAvailableRights[] = 'seeprojectstatusalways'; # see status and banner even if $wgApprovedRevsShowOnlyToMembers is true
$wgAvailableRights[] = 'useholocaustsearchfilter'; # Have a search filter by Holocaust category
$wgAvailableRights[] = 'viewapprovedrevsspecialpage'; # Have a search filter by Holocaust category
$wgAvailableRights[] = 'auto-reapproval-on-save'; # Automatically update revision number when making changes to an already-approved rev. Useful for e.g. bots.



// user groups
$wgGroupPermissions['projectassigner']['assigntoproject'] = true;
$wgGroupPermissions['projectassigner']['seeprojectstatusalways'] = true;
$wgGroupPermissions['projectassigner']['useholocaustsearchfilter'] = true;
$wgGroupPermissions['projectassigner']['viewapprovedrevsspecialpage'] = true;




$wgGroupPermissions['projectdelegate']['approveprojectonbehalf'] = true;
$wgGroupPermissions['projectdelegate']['seeprojectstatusalways'] = true;
$wgGroupPermissions['projectdelegate']['useholocaustsearchfilter'] = true;
$wgGroupPermissions['projectdelegate']['viewapprovedrevsspecialpage'] = true;


$wgGroupPermissions['holocaustauthority'] = array();	// Just creating the group
$wgGroupPermissions['holocaustauthority']['seeprojectstatusalways'] = true;
$wgGroupPermissions['holocaustauthority']['useholocaustsearchfilter'] = true;
$wgGroupPermissions['holocaustauthority']['viewapprovedrevsspecialpage'] = true;


$wgGroupPermissions['editor']['seeprojectstatusalways'] = true;

// logging
$wgLogTypes[] = 'approvedrevs';
$wgLogRestrictions['approvedrevs'] = 'edit';
$wgLogActionsHandlers['approvedrevs/*'] = 'LogFormatter';


// Tracking category for pages
$wgTrackingCategories[] = 'approvedrevs-tracking-category';

// resources
$wrApprovedRevsResourceTemplate = array(
	'localBasePath' => __DIR__ . '/modules',
	'remoteExtPath' => 'WikiRights/WRApprovedRevs/modules',
	'group' => 'ext.wrApprovedRevs',
);

$wgResourceModules['ext.wrApprovedRevs.main'] = $wrApprovedRevsResourceTemplate + array(
	'styles'    => 'ext.wrApprovedRevs.less',
	'position'  => 'top',
);

$wgResourceModules['ext.wrApprovedRevs.projectSearch'] = $wrApprovedRevsResourceTemplate + array(
	'scripts'      => 'ext.wrApprovedRevs.projectSearch.js',
	'dependencies' => array( 'jquery.cookie' ),
);

unset( $wrApprovedRevsResourceTemplate );

