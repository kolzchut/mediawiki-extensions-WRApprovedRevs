<?php

/**
 * Main class for the Approved Revs extension.
 *
 * @file
 * @ingroup Extensions
 *
 * @author Dror Snir
 */
class ApprovedRevs {

	static $mProjectForPage = array();
	static $mGroupForPage = array();
	static $mOrganizationForPage = array();
	static $mApprovedRevIdForPage = array();
	
	static $mApprovalStatusForPage = array();
	/**
	 * Returns whether this page is assigned to a project.
	 * Also stores the boolean answer as a field in the page
	 * object, to speed up processing if it's called more than once.
	 */
	public static function isAssignedToProject( Title $title ) {
		// if this function was already called for this page, the
		// value should have been stored as a field in the $title object
		if( isset( $title->isAssignedToProject ) ) {
			return $title->isAssignedToProject;
		}

		if( self::getProjectName( $title ) !== false ) {
			$title->isAssignedToProject = true;
		} else {
			$title->isAssignedToProject = false;
		};

		return $title->isAssignedToProject;
	}

	public static function getProjectName( Title $title ) {
		// if this function was already called for this page, the
		// value should have been stored as a field in the $title object
		if ( isset( $title->approvedRevsProject ) ) {
			return $title->approvedRevsProject;
		}

		$pageID = $title->getArticleID();
		if ( array_key_exists( $pageID, self::$mProjectForPage ) ) {
			$title->approvedRevsProject = self::$mProjectForPage[$pageID];
			return $title->approvedRevsProject;
		}
		
		if ( !$title->exists() ) {
			$title->approvedRevsProject = false;
			return $title->approvedRevsProject;			
		}

		$dbr = wfGetDB( DB_SLAVE );
		$projectName = $dbr->selectField( 'approved_pages', 'ap_project', array( 'ap_page_id' => $pageID ) );
		self::$mProjectForPage[$pageID] = $projectName;
		return $projectName;
	}

	public static function getOrganizationName( Title $title ) {
		if ( isset( $title->approvedRevsOrganization ) ) {
			return $title->approvedRevsOrganization;
		}
		$pageID = $title->getArticleID();
		if ( array_key_exists( $pageID, self::$mOrganizationForPage ) ) {
			$title->approvedRevsOrganization = self::$mOrganizationForPage[$pageID];
			return $title->approvedRevsOrganization;
		}
		if ( !$title->exists() ) {
			$title->approvedRevsOrganization = false;
			return $title->approvedRevsOrganization;			
		}
		
		// else, we need to check the database
	$dbr = wfGetDB( DB_SLAVE );
		$organizationName = $dbr->selectField( 'approved_pages', 'ap_organization', array( 'ap_page_id' => $pageID ) );
		self::$mOrganizationForPage[$pageID] = $organizationName;
		return $organizationName;
	}

	public static function getGroup( Title $title ) {
		// if this function was already called for this page, the
		// value should have been stored as a field in the $title object
		if ( isset( $title->approvedRevsGroup ) ) {
			return $title->approvedRevsGroup;
		}
		
		$pageID = $title->getArticleID();
		if ( array_key_exists( $pageID, self::$mGroupForPage ) ) {
			$title->approvedRevsGroup = self::$mGroupForPage[$pageID];
			return $title->approvedRevsGroup;
		}
		
		if ( !$title->exists() ) {
			$title->approvedRevsGroup = false;
			return $title->approvedRevsGroup;
		}

		$dbr = wfGetDB( DB_SLAVE );
		$userGroup = $dbr->selectField( 'approved_pages', 'ap_user_group', array( 'ap_page_id' => $pageID ) );
		self::$mGroupForPage[$pageID] = $userGroup;
		return $userGroup;
	}
	
	public static function getGroupName( Title $title) {
		return User::getGroupName( self::getGroup( $title ) );
	}
	
	public static function getApprovalStatus( Title $title ) {
			
		// if this function was already called for this page, the
		// value should have been stored as a field in the $title object
		if ( isset( $title->approvedRevsStatus ) ) {
			return $title->approvedRevsStatus;
		}
		
		$pageID = $title->getArticleID();
		if ( array_key_exists( $pageID, self::$mApprovalStatusForPage ) ) {
			$title->approvedRevsStatus = self::$mApprovalStatusForPage[$pageID];
			return $title->approvedRevsStatus;
		}
		
		if ( !$title->exists() ) {
			$title->approvedRevsStatus = false;
			return $title->approvedRevsStatus;
		}
		
		$approvalStatus = array();
		$dbr = wfGetDB( DB_SLAVE );
		$approvalStatus['user'] = $dbr->selectField( 'approved_pages', 'ap_review_user', array( 'ap_page_id' => $pageID ) );
		$approvalStatus['time'] = $dbr->selectField( 'approved_pages', 'ap_review_timestamp', array( 'ap_page_id' => $pageID ) );
		$approvalStatus['on_behalf'] = $dbr->selectField( 'approved_pages', 'ap_review_on_behalf', array( 'ap_page_id' => $pageID ) );
		$approvalStatus['on_behalf_text'] = $dbr->selectField( 'approved_pages', 'ap_review_on_behalf_comments', array( 'ap_page_id' => $pageID ) );
		
		self::$mApprovalStatusForPage[$pageID] = $approvalStatus;
		return $approvalStatus;
	}
	
	public static function getApprovalStatusMsg( Title $title, User $user ) {
		global $wgLang;	// Necessary evil?

		if(	self::isThisRevisionApproved( $title ) ) {
			$approvalStatus = self::getApprovalStatus( $title );
			$datetime = $wgLang->userTimeAndDate( $approvalStatus['time'], $user );
			
			$msg = 'אושר ע"י ' . User::whoIs( $approvalStatus['user'] );
			$msg .= ' ב-' . $datetime;

			return $msg;
		}
		
		return '';
		
		
	}


	public static function userInProjectGroup( Title &$title, User &$user ) {
		if(	$user->isLoggedIn() && 
			in_array( self::getGroup( $title ), $user->getGroups() )
		) {
			return true;	
		}
		
		return false;
	}
	
	public static function userCanApprovePage( Title &$title, User &$user ) {
		return ( ApprovedRevs::userInProjectGroup( $title , $user ) );
	}

	public static function saveProjectAssociationInDB( Title $title, $action, $project, $organization, $group) {
		$dbr = wfGetDB( DB_MASTER );
		$page_id = $title->getArticleID();

		SWITCH( $action ) {
		case 'assign':
			$dbr->insert( 'approved_pages', 
				array( 
					'ap_page_id' => $page_id,
					'ap_project' => $project,
					'ap_organization' => $organization,
					'ap_user_group' => $group 
				)
			);
			break;
		case 'reassign':
			$dbr->update( 'approved_pages',
				array(
					'ap_project'                   => $project,
					'ap_user_group'                => $group,
					'ap_organization'              => $organization,
					// Reset approval on reassign
					'ap_approved_rev_id'           => null,
					'ap_review_user'               => null,
					'ap_review_timestamp'          => null,
					'ap_review_on_behalf'          => null,
					'ap_review_on_behalf_comments' => null,
				),
				array( 'ap_page_id' => $page_id ) );
			break;
		case 'unassign':
			self::deleteProjectAssignment( $title );
			break;
		}
		
		// Update "cache" in memory
		unset( self::$mApprovedRevIdForPage[$page_id] );
		if( $action != 'unassign' ) {
			self::$mProjectForPage[$page_id] = $project;
			self::$mOrganizationForPage[$page_id] = $group;
			self::$mGroupForPage[$page_id] = $group;
		} else {
			unset( self::$mProjectForPage[$page_id] );
			unset( self::$mOrganizationForPage[$page_id] );
			unset( self::$mGroupForPage[$page_id] );
		}
	}
	
	
	public static function deleteProjectAssignment( Title $title ) {
		$dbr = wfGetDB( DB_MASTER );
		$page_id = $title->getArticleID();
		$dbr->delete( 'approved_pages', array( 'ap_page_id' => $page_id ) );
	}
	
	public static function logProjectAssignment( Title $title, $action, User $user, $project, $organization, $group, $reason = null ) {
		// is this a valid log action for us?
		if( !in_array( $action, array( 'assign', 'reassign', 'unassign' ) ) ) {
			return false;
		}
		$logEntry = new ManualLogEntry( 'approvedrevs', $action );
		
		$logEntry->setPerformer( $user ); // User object, the user who did this action
		$logEntry->setTarget( $title ); // The page that this log entry affects
		$logEntry->setComment( $reason ); // User provided comment
		$logEntry->setParameters( array(
		  '4::project' => ( self::isAssignedToProject( $title ) ? self::getProjectName( $title ) : $project ),
		  '5::group' => User::getGroupName( $group ),
		  '6::organization' => $organization,
		) );
		 
		$logid = $logEntry->insert();
		$logEntry->publish( $logid );
			
	}
	
	
	public static function logPageApproval( Title $title, User $user, $on_behalf, $comments ) {
		
		$article = Article::newFromId( $title->getArticleID() );
		$approved_rev_id = ( $article->isCurrent() ? $article->getRevIdFetched() : $article->getOldID() );
		$approved_rev_link = Linker::link( $title, $approved_rev_id, array(), array( 'oldid' => $approved_rev_id ) );
		
		$action = empty( $on_behalf ) ? 'approve' : 'approveonbehalf';
		if( $action == 'approve' && !empty( $comments ) ) { $action = 'approvewithcomment'; };
		$logEntry = new ManualLogEntry( 'approvedrevs', $action );
		
		$logEntry->setPerformer( $user ); // User object, the user who did this action
		$logEntry->setTarget( $title ); // The page that this log entry affects
		$logEntry->setParameters( 
			array(
			  '4::revision_link' => Message::rawParam( $approved_rev_link ),
			  '5::project' => self::getProjectName( $title ),
			  '6::on_behalf' => $on_behalf,
			  '7::comments' => $comments,
		  	)
		);
		 
		$logid = $logEntry->insert();
		$logEntry->publish( $logid );	
	}

	public static function savePageApprovalInDB( Title $title, User $user, $on_behalf = null, $on_behalf_comments = null ) {
		$dbr = wfGetDB( DB_MASTER );
		$page_id = $title->getArticleID();
		$current_rev_id = self::getThisRevisionID( $title );
		
		$approvalStatus = array();
		$approvalStatus['user'] = $user->getId();
		$approvalStatus['time'] = wfTimestampNow();
		$approvalStatus['on_behalf'] = $on_behalf;
		$approvalStatus['on_behalf_text'] = $on_behalf_comments;
	
		$dbr->update( 'approved_pages',
			array(
				'ap_approved_rev_id'           => $current_rev_id,
				'ap_review_user'               => $approvalStatus['user'],
				'ap_review_timestamp'          => $approvalStatus['time'],
				'ap_review_on_behalf'          => $approvalStatus['on_behalf'],
				'ap_review_on_behalf_comments' => $approvalStatus['on_behalf_text'],
			),
			array( 'ap_page_id' => $page_id )
		);
		
		// Update "cache" in memory
		self::$mApprovedRevIdForPage[$page_id] = $current_rev_id;
		self::$mApprovalStatusForPage[$page_id] = $approvalStatus;

		return true;
	}
	
	public static function logUnapprovedSave( Title $title, User $user, $rev_id ) {
		
		$article = Article::newFromId( $title->getArticleID() );
		
		$new_rev_link = Linker::linkKnown( $title, $rev_id, array(), array( 'oldid' => $rev_id ) );
		
		$action = 'unapprovedsave';
		$logEntry = new ManualLogEntry( 'approvedrevs', $action );
		
		$logEntry->setPerformer( $user ); // User object, the user who did this action
		$logEntry->setTarget( $title ); // The page that this log entry affects
		$logEntry->setParameters( 
			array(
			  '4::revision_link' => Message::rawParam( $new_rev_link ),
			  '5::project' => self::getProjectName( $title ),
		  	)
		);
		 
		$logid = $logEntry->insert();
		$logEntry->publish( $logid );	
	}
	
	protected static function getThisRevisionID( Title $title ) {
		$article = Article::newFromId( $title->getArticleID() );
		return ( $article->isCurrent() ? $article->getRevIdFetched() : $article->getOldID() );
	}


	public static function isThisRevisionApproved( Title $title ) {
		if( ! self::isAssignedToProject( $title ) ) {
			return false;
		}
		return ( self::getThisRevisionID( $title ) == self::getApprovedRevID( $title ) );
	}


	public static function isLatestRevisionApproved( Title $title ) {
		if( ! self::isAssignedToProject( $title ) ) {
			return false;
		}
		return ( $title->getLatestRevID() == self::getApprovedRevID( $title ) );
	}


	public static function getRevStatusMsg( Title $title ) {
		$article = Article::newFromId( $title->getArticleID() );
		
		$approved_rev_id = self::getApprovedRevID( $title );
		$current_rev = self::getThisRevisionID( $title );
		
		$latest_rev_link = Linker::link( $title, 'לחצו לגרסה העדכנית של הדף' );
		$approved_rev_link = Linker::link( $title, 'לחצו לגרסה האחרונה שאושרה', array(), array( 'oldid' => $approved_rev_id ) );
		
		$status = '';
		if( $article->isCurrent() ) {
			// this is the latest revision
			if ( self::isThisRevisionApproved( $title ) ) {
				$status = 'הדף מאושר ע"י ' . self::getOrganizationName( $title );
			} else {
				$status = 'הדף ממתין לאישור ' . self::getOrganizationName( $title );
				if( self::hasApprovedRevision( $title ) ) {
					$rev_link = $approved_rev_link;
				} else {
					//This is needless: $rev_link =	'אין גרסה מאושרת';
				}
			}
		} else {
			// this is an old revision
			if ( self::isThisRevisionApproved( $title ) ) {
				$status .= 'גרסה מאושרת ישנה'; 	
			} else {
				$status .= 'זוהי גרסה ישנה של הדף';
			}
			$rev_link = $latest_rev_link;
		}
		
		// Log link for this page - only for some (currently all editors)
		if( self::isUserAllowedAdvancedView( $title ) ) {
			$status = Linker::link(
				SpecialPage::getTitleFor( 'Log' ),
				$status,
				array(),
				array( 'type' => 'approvedrevs','page' => $title->getPrefixedText() )
			);
		}
		
		$rev_link = isset( $rev_link ) ? " ({$rev_link})" : '';
		$msg = $status . $rev_link;
		
		return $msg;
	}
	
	public static function isUserAllowedAdvancedView( Title $title ) {
		return $title->quickUserCan( 'edit' );
	}
	
	/**
	 * Gets the approved revision ID for this page, or null if there isn't
	 * one.
	 */
	public static function getApprovedRevID( Title $title ) {
        if( !self::isAssignedToProject( $title ) ) {
            return false;
        }

		$pageID = $title->getArticleID();
		if ( array_key_exists( $pageID, self::$mApprovedRevIdForPage ) ) {
			return self::$mApprovedRevIdForPage[$pageID];
		}

		$dbr = wfGetDB( DB_SLAVE );
		$revID = $dbr->selectField( 'approved_pages', 'ap_approved_rev_id', array( 'ap_page_id' => $pageID ) );
		self::$mApprovedRevIdForPage[$pageID] = $revID;
		return $revID;
	}

	/**
	 * Returns whether or not this page has a revision ID.
	 */
	public static function hasApprovedRevision( Title $title ) {
		$revision_id = self::getApprovedRevID( $title );
		return ( ! empty( $revision_id ) );
	}

}
