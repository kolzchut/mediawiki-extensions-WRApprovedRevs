<?php

/**
 * Functions for the Approved Revs extension called by hooks in the MediaWiki
 * code.
 *
 * @file
 * @ingroup Extensions
 *
 */
class ApprovedRevsHooks {

	private static $categoryAdded = false;
	const STATUSMARKER = 'x--ApprovedRevs-status-marker--x';

	public static function describeDBSchema( DatabaseUpdater $updater = null ) {
		$dir = __DIR__ . '/sql';

		// For now, there's just a single SQL file for all DB types.
		$updater->addExtensionUpdate(
			array( 'addTable', 'approved_pages', "$dir/ApprovedRevs.sql", true )
		);
		$updater->addExtensionUpdate(
			array( 'modifyField', 'approved_pages', 'ap_user_group', "$dir/patch-ap_group-length-increase-255.sql", true )
		);
		$updater->addExtensionUpdate(
			array( 'addIndex', 'approved_pages', 'approved_pages_page_id', "$dir/patch-add-indices.sql", true )
		);

		return true;
	}

	/**
	 * @param WikiPage $article
	 * @param User $user
	 * @param $content
	 * @param $summary
	 * @param Bool $isMinor
	 * @param Bool $isWatch
	 * @param $section
	 * @param $flags
	 * @param Revision $revision
	 * @param $status
	 * @param $baseRevId
	 *
	 * @return bool
	 */
	public static function onPageContentSaveComplete(
		WikiPage $article, User $user, $content, $summary, $isMinor,
		$isWatch, $section, $flags, $revision, $status, $baseRevId
	) {

		if( is_null( $revision ) ) {
			// Ignore null edits
			return true;
		}

		$title = $article->getTitle();
		$oldRevisionId = $revision->getParentId();

		if ( $oldRevisionId !== null // There's actually a previous revision
		    && ApprovedRevs::isAssignedToProject( $title )
			&& ApprovedRevs::getApprovedRevID( $title ) === $oldRevisionId  // said prev revision was approved
		) {
			if ( $user->isAllowed( 'auto-reapproval-on-save' ) ) {
				ApprovedRevs::performAutoReapproval( $title, $revision->getId() );
			} else {
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

		$user = $article->getContext()->getUser();
		$banner = self::showProjectBanner( $title, $user );
		if ( !is_null( $banner ) ) {
			$wgOut->addModuleStyles( 'ext.wrApprovedRevs.main' );
			$wgOut->addHTML( $banner );
		}

		return true;
	}

	public static function showProjectBanner( Title $title, User $user ) {
		global $wgApprovedRevsShowOnlyToMembers;

		$projectName = ApprovedRevs::getProjectName( $title );
		$banner = null;
		if ( !$wgApprovedRevsShowOnlyToMembers
		    || ApprovedRevs::userInProjectGroup( $title, $user )
		    || $title->quickUserCan( 'seeprojectstatusalways', $user )
		) {
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

		if ( !ApprovedRevs::isAssignedToProject( $title ) ) {
			return '';
		}
		if ( $wgApprovedRevsShowOnlyToMembers
		     && !ApprovedRevs::userInProjectGroup( $title, $user )
		     && !$title->quickUserCan( 'seeprojectstatusalways', $user )
		) {
			return '';
		}

		$statusCssClass = 'ar-status-'
			. ( ApprovedRevs::isThisRevisionApproved( $title ) ? 'approved' : 'unapproved'  );

		$status = ApprovedRevs::getRevStatusMsg( $title );

		$statusBox = '<div class="ar-page-status ' . $statusCssClass . '">' .  $status;

		//$statusBox .= '<div class="ar_project_status_org">ארגון אחראי: '
		//	. ApprovedRevs::getOrganizationName( $title ) . '</div>';

		if ( ApprovedRevs::isUserAllowedAdvancedView( $title ) ) {
			//$statusBox .= '<div class="ar_project_group">קבוצה: '
			//  . ApprovedRevs::getGroupName( $title ) . '</div>';
			if ( ApprovedRevs::isThisRevisionApproved( $title ) ) {
				$statusBox .= ' <span class="ar-status-approval-info">['
				              . ApprovedRevs::getApprovalStatusMsg( $title, $user ) . ']</span>';
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

		if ( $title->quickUserCan( 'assigntoproject', $user ) ) {
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

		if ( $sktemplate->isRevisionCurrent() &&
			ApprovedRevs::isAssignedToProject( $title ) &&
			ApprovedRevs::userCanApprovePage( $title, $user ) &&
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


	/**
	 * @param ParserOutput $parserOutput
	 *
	 * @return true
	 *
	 */
	public static function onContentAlterParserOutput( $content, $title, $parserOutput ) {
		if ( ApprovedRevsHooks::$categoryAdded === true || $title === null ||
		     !ApprovedRevs::isAssignedToProject( $title ) )
		{
			return true;
		}


		// Add tracking categories, one general for all assigned pages and another for (un?)approved
		$trackingCat = 'approvedrevs-tracking-category';
		$parserOutput->addTrackingCategory( $trackingCat, $title );
		$trackingCat .= ApprovedRevs::isLatestRevisionApproved( $title ) ? '-approved' : '-unapproved';
		$parserOutput->addTrackingCategory( $trackingCat, $title );
		//ApprovedRevsHooks::$categoryAdded = true;

		return true;

	}


	/**
	 * @param $content
	 * @param Title $title
	 * @param $revId
	 * @param $options
	 * @param $generateHtml
	 * @param ParserOutput $output
	 *
	 * @return bool
	 */
	public static function onContentGetParserOutput(
		$content, $title, $revId, $options, $generateHtml, &$output
	) {
		$output->addCategory( 'approvedrevs-tracking-category', '' );

		return true;
	}

}
