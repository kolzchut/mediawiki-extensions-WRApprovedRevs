<?php

/**
 * Functions for the Approved Revs extension called by hooks in the MediaWiki
 * code.
 *
 * @file
 * @ingroup Extensions
 *
 * @author Dror Snir
 */
class ApprovedRevsHooks {
	
	const mStatusMarker = 'x--ApprovedRevs-status-marker--x';
	
	public static function describeDBSchema( DatabaseUpdater $updater = null ) {
		$wgDBtype = $updater->getDB()->getType();
		$dir = __DIR__ ;

		// For now, there's just a single SQL file for all DB types.
		$updater->addExtensionUpdate( array( 'addTable', 'approved_pages', "$dir/ApprovedRevs.sql", true ) );
		//Patching:
		$updater->modifyExtensionField( 'approved_pages', 'ap_user_group', "$dir/patch-ap_group-length-increase-255.sql" );

		return true;
	}

	public static function onPageContentSaveComplete( WikiPage $article, User $user, $content, $summary, $isMinor,
        $isWatch, $section, $flags, $revision, $status, $baseRevId ) {

 		if ( !is_null( $revision ) ) { // The user actually changed the text
			$title = $article->getTitle();
            $oldRevisionId = $title->getPreviousRevisionID( $revision->getId() );

			if( ApprovedRevs::getApprovedRevID( $title ) == $oldRevisionId ) {
				ApprovedRevs::logUnapprovedSave( $title, $user, $revision->getId() );
			}
		}
		
		return true;
	}
	


	/**
	 * Display a message
	 *
	 * @param Article &$article
	 * @param boolean $outputDone
	 * @param boolean $useParserCache
	 *
	 * @return true
	 */
	public static function onArticleViewHeader( Article &$article, &$outputDone, &$useParserCache ) {
		global $wgOut;
		
		$title = $article->getTitle();
		if ( !ApprovedRevs::isAssignedToProject( $title ) ) {
			return true;
		}

        //$wgParser->addtrackingcategory( 'messagekey' );

		$user = $article->getContext()->getUser();
		$banner = self::showProjectBanner( $title, $user );
		if( !is_null( $banner ) ) {
			$wgOut->addModuleStyles( 'ext.wrApprovedRevs.main' );
			$wgOut->addHTML( $banner );
        }

		return true;
	}
	
	public static function showProjectBanner( Title $title, User $user ) {
		global $wgApprovedRevsShowOnlyToMembers;
		
		$projectName = ApprovedRevs::getProjectName( $title );
        $banner = null;
		if( !$wgApprovedRevsShowOnlyToMembers || ApprovedRevs::userInProjectGroup( $title, $user ) || 
				$title->quickUserCan( 'seeprojectstatusalways', $user ) ) {
			$msgName = 'ar_project_banner_' . $projectName;
            $status = self::showProjectStatus( $title, $user );

			$banner = wfMessage( $msgName, $projectName );	
			$banner = '<div class="approvedrevs-banner clearfix">' . $banner;
            $banner .= $status . '</div>';
		}

		return $banner;
	}

	
	public static function showProjectStatus( Title $title, User $user ) {
		global $wgApprovedRevsShowOnlyToMembers;
		
		if( !ApprovedRevs::isAssignedToProject( $title ) ) {
			return '';
		}
		if( $wgApprovedRevsShowOnlyToMembers && !ApprovedRevs::userInProjectGroup( $title, $user ) && !$title->quickUserCan( 'seeprojectstatusalways', $user ) ) {
			return '';
		}
	
		$statusCssClass = 'ar-status-' . ( ApprovedRevs::isThisRevisionApproved( $title ) ? 'approved' : 'unapproved'  );
		
		$status = ApprovedRevs::getRevStatusMsg( $title );	
	
		$statusBox = '<div class="ar-page-status ' . $statusCssClass . '">' .  $status;
		//$statusBox .= '<div class="ar_project_status_org">ארגון אחראי: ' . ApprovedRevs::getOrganizationName( $title ) . '</div>';
		
		if( ApprovedRevs::isUserAllowedAdvancedView( $title ) ) {
			//$statusBox .= '<div class="ar_project_group">קבוצה: ' . ApprovedRevs::getGroupName( $title ) . '</div>';
			if( ApprovedRevs::isThisRevisionApproved( $title ) ) {
				$statusBox .= ' <span class="ar-status-approval-info">[' . ApprovedRevs::getApprovalStatusMsg( $title, $user ) . ']</span>';
			}
		}

		$statusBox .= '</div>';

		return $statusBox;
	}

    public static function onSkinTemplateNavigation( SkinTemplate &$sktemplate, array &$links ) {
        self::addAssignButton( $sktemplate, $links );
        self::addApprovalButton( $sktemplate, $links );

        return true;
    }
	
	public static function addAssignButton( SkinTemplate &$sktemplate, array &$links ) {
		$title = $sktemplate->getRelevantTitle();
		$user = $sktemplate->getUser();
		
		if( $title->quickUserCan( 'assigntoproject', $user ) ) {
			/* This is somewhat a replication of code from SkinTemplate::buildContentNavigationUrls() */
			$onPage = $title->equals( $sktemplate->getTitle() );
			$request = $sktemplate->getRequest();
			$action = $request->getVal( 'action', 'view' );
			/* /Code Replication */
			$isAssigned = ApprovedRevs::isAssignedToProject( $title );
			$isAssigning = ($onPage && $action == 'assigntoproject' );
			$msg = $isAssigned ? 'btn-reassigntoproject' : 'btn-assigntoproject';
			
			$links['actions']['assigntoproject'] = array(
				'text'	=> $sktemplate->msg( $msg )->text(),
				'href'	=>  $title->getLocalURL( 'action=assigntoproject' ),
				'class' => $isAssigning ? 'selected' : '',
			);
		};
		
		return true;
	}
	
	public static function addApprovalButton( SkinTemplate &$sktemplate, array &$links ) {
		$title = $sktemplate->getRelevantTitle();
		$user = $sktemplate->getUser();

		if( $sktemplate->isRevisionCurrent() &&
			ApprovedRevs::isAssignedToProject( $title ) && 
			ApprovedRevs::userCanApprovePage( $title , $user ) &&
			! ApprovedRevs::isLatestRevisionApproved( $title )
			) {
				/* This is somewhat a replication of code from SkinTemplate::buildContentNavigationUrls() */
				$onPage = $title->equals( $sktemplate->getTitle() );
				$request = $sktemplate->getRequest();
				$action = $request->getVal( 'action', 'view' );
				/* /Code Replication */
				$isInAction = ($onPage && $action == 'approveprojectpage' );
				
				$links['actions']['approveprojectpage'] = array(
					'text'	=> 'אישור הדף',
					'href'	=>  $title->getLocalURL( 'action=approveprojectpage' ),
					'class' => $isInAction ? 'selected' : '',
				);
			}
		
		return true;

	}
	
	
}
