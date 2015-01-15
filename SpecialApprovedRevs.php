<?php

/**
 * Special page that displays various lists of pages that either do or do
 * not have an approved revision.
 *
 * @author Yaron Koren
 */
class SpecialApprovedRevs extends SpecialPage {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( 'ApprovedRevs' );
	}

	function execute( $query ) {
		if ( !$this->getUser()->isAllowed( 'viewapprovedrevsspecialpage' ) ) {
			throw new PermissionsError( 'viewapprovedrevsspecialpage' );
		}

		$this->setHeaders();
		list( $limit, $offset ) = wfCheckLimits();
		
		$mode = $this->getRequest()->getVal( 'show' );
		$organization = $this->getRequest()->getVal( 'organization' );
		//$group = $this->getRequest()->getVal( 'group' );
		$group = null;
		
		$rep = new SpecialApprovedRevsPage( $mode, $organization, $group );
		
		if ( method_exists( $rep, 'execute' ) ) {
			return $rep->execute( $query );
		} else {
			return $rep->doQuery( $offset, $limit );
		}
	}
	
}

class SpecialApprovedRevsPage extends QueryPage {
	
	protected $mMode;
	protected $mOrganization;
	protected $mGroup;

	/** @const */
	protected $mAllowedModes = array( 'notlatest', 'approved', 'unapproved', 'all' );

	public function __construct( $mode, $organization, $group ) {
		if ( $this instanceof SpecialPage ) {
			parent::__construct( 'ApprovedRevs' );
		}
		
		if( !in_array( $mode, $this->mAllowedModes ) ) {
			$mode = 'notlatest';
		}
		$this->mMode = $mode;
		$this->mOrganization = $organization;
		$this->mGroup = $group;		
		//@TODO: need to get group ID from group name! Is there such a function?
		//Maybe get the entire group list with names, and create a select in the filter form.
	}

	function getName() {
		return 'ApprovedRevs';
	}

	function isExpensive() { return false; }

	function isSyndicated() { return false; }

	/** get count(*) for list */
	function getListCount( $mode, $organization, $group ) {
		$query = self::getQueryStructure( $mode, $organization, $group );
		$query['fields'] = 'COUNT(*) AS count';
		$dbr = wfGetDB( DB_SLAVE );
		
		$res = $dbr->select(
			$query['tables'],
			$query['fields'], 
			$query['conds'],
			__METHOD__,
			array(),
			$query['join_conds']
		);
		
		$row = $res->fetchRow();
		
		return $row['count'];
	}
	
	
	function getPageHeader() {
		global $wgScript;

		// show the names of the three lists of pages, with the one
		// corresponding to the current "mode" not being linked
		
		$t = $this->getTitle();
		$organization = $this->mOrganization;
		$group = $this->mGroup;

		$navLine = wfMessage( 'approvedrevs-view' ) . ' ';
		
		
		foreach( $this->mAllowedModes as $mode ) {
			$msg = wfMessage( 'approvedrevs-' . $mode . 'pages' );
			if( $this->mMode == $mode ) {
				$navLine .= Xml::element( 'strong',
					null,
					$msg
				);
			} else {
				$navLine .= Xml::element( 'a',
						array( 'href' => $t->getLocalURL( array( 'show' => $mode, 'organization' => $organization, 'group' => $group ) ) ),
						$msg
					);
			}
			
			$navLine .= ' (' . $this->getListCount( $mode, $organization, $group ) . ')';	
			
			if( $mode !== end( $this->mAllowedModes ) ) {
				$navLine .= ' | ';
			}
		}
		
		$navLine = Xml::tags( 'p', null, $navLine ) . "\n";
		
		// Show a filtering option
		$filterForm = Xml::openElement( 'form', array( 'method' => 'get', 'action' => $wgScript ) ) .
			Xml::openElement( 'fieldset' ) .
			Xml::element( 'legend', null, $this->msg( 'approvedrevs-filter-legend' )->text() ) .
			Html::hidden( 'title', $t->getPrefixedText() ) .
			Html::hidden( 'show', $this->mMode ) .
			Xml::inputLabel( $this->msg( 'approvedrevs-filter-field-org' )->text(), 'organization', 'filter-field-org', null, $organization ) . ' ' .
			//Xml::inputLabel( $this->msg( 'approvedrevs-filter-field-group' )->text(), 'group', 'filter-field-group', null, $group ) . ' ' .
			Xml::submitButton( $this->msg( 'approvedrevs-filter-field-submit' )->text() ) .
			Xml::closeElement( 'fieldset' ) .
			Xml::closeElement( 'form' );

		return $filterForm . $navLine;
		
	}

	/**
	 * Set parameters for standard navigation links.
	 */
	function linkParameters() {
		$params = array();
		$params['show'] = $this->mMode;	
		
		return $params;
	}

	function getPageFooter() {
	}

	public static function getNsConditionPart( $ns ) {
		return 'p.page_namespace = ' . $ns;
	}

	static function getBaseQueryStructure() {
		$baseQuery = array(
			'tables' => array(
				'ap' => 'approved_pages',
				'p' => 'page',
			),
			'fields' => array(
				'p.page_id AS id',
				'ap.ap_approved_rev_id AS rev_id',
				'p.page_latest AS latest_id',
				'ap.ap_review_on_behalf as on_behalf',
				'ap.ap_review_on_behalf_comments as on_behalf_comments',
			),
			'join_conds' => array(
				'p' => array(
					'JOIN', 'ap.ap_page_id=p.page_id',
				),
			),
		);
		
		return $baseQuery;
	}
	
	static function getQueryStructure( $mode, $organization = null, $group = null ) {
	  $baseQuery = self::getBaseQueryStructure();
	  $conds = array();

	  switch( $mode ) {
			case 'unapproved':	$conds[] = 'ap.ap_approved_rev_id IS NULL'; break;
			case 'approved': 	$conds[] = 'ap.ap_approved_rev_id = p.page_latest'; break;
			case 'notlatest':	$conds[] = '(ap.ap_approved_rev_id IS NULL) OR (p.page_latest != ap.ap_approved_rev_id)'; $break; 
			case 'all': 		/* Fall through to default */
			default:
		}

		$nsConds = self::getNamespacesConds();
		if( $nsConds ) {
			$conds[] = $nsConds;
		}
		
		if ( $organization ) {
			$conds[] = "ap.ap_organization = '{$organization}'";
		}
		
		if ( $group ) {
			$conds[] = "ap.ap_user_group = '{$group}'";
		}

		$query = $baseQuery + array( 'conds' => $conds );

		return $query;
	}

	function getNamespacesConds() {
		global $wgApprovedRevsNamespaces;
		
		$conds = null;
		if( !empty( $wgApprovedRevsNamespaces ) ) {
			$namespacesString = '(' . implode( ',', $wgApprovedRevsNamespaces ) . ')';
			$conds = "p.page_namespace IN $namespacesString";
		}

		return $conds;
	}
	
	/**
	 * Used as of MW 1.17.
	 * 
	 * (non-PHPdoc)
	 * @see QueryPage::getSQL()
	 */	
	function getQueryInfo() {		
		return $this->getQueryStructure( $this->mMode, $this->mOrganization, $this->mGroup );
	}

	function getOrder() {
		return ' ORDER BY p.page_namespace, p.page_title ASC';
	}

	function getOrderFields() {
		return array( 'p.page_namespace', 'p.page_title' );
	}

	function sortDescending() {
		return false;
	}

	function formatResult( $skin, $result ) {
		$title = Title::newFromId( $result->id );
		$pageLink = Linker::link( $title );
		
		if ( $this->mMode == 'unapproved' ) {
			global $egApprovedRevsShowApproveLatest;
			
			$line = $pageLink;
			if ( $egApprovedRevsShowApproveLatest &&
				ApprovedRevs::userCanApprovePage( $title , $skin->getUser() ) ) {
				$line .= ' (' . Xml::element( 'a',
					array( 'href' => $title->getLocalUrl(
						array(
							'action' => 'approveprojectpage',
							//'oldid' => $result->latest_id
						)
					) ),
					wfMessage( 'approvedrevs-approvelatest' )
				) . ')';
			}
			
			return $line;
		} elseif( $this->mMode == 'notlatest' ) {
			if( !is_null( $result->rev_id ) ) {  
				$diffLink = Xml::element( 'a',
					array( 'href' => $title->getLocalUrl(
						array(
							'diff' => $result->latest_id,
							'oldid' => $result->rev_id
						)
					) ),
					wfMessage( 'approvedrevs-difffromlatest' )
				);
				return "$pageLink ($diffLink)";
			} else {
				return $pageLink;	
			}
		} elseif( $this->mMode == 'approved' ) { // main mode (pages with an approved revision)
			global $wgOut, $wgLang;
			
			$additionalInfo = Xml::element( 'span',
				array (
					'class' => $result->rev_id == $result->latest_id ? 'approvedRevIsLatest' : 'approvedRevNotLatest'
				)
				//wfMessage( 'approvedrevs-revisionnumber', $result->rev_id )
			);

			// Get data on the most recent approval from the
			// 'approval' log, and display it if it's there.
			$sk = $this->getSkin();
			$loglist = new LogEventsList( $sk, $wgOut );
			$pager = new LogPager( $loglist, 'approvedrevs', '', $title->getText() );
			$pager->mLimit = 1;
			$pager->doQuery();
			$row = $pager->mResult->fetchObject();
			
			if ( !empty( $row ) ) {
				$timestamp = $wgLang->timeanddate( wfTimestamp( TS_MW, $row->log_timestamp ), true );
				$date = $wgLang->date( wfTimestamp( TS_MW, $row->log_timestamp ), true );
				$time = $wgLang->time( wfTimestamp( TS_MW, $row->log_timestamp ), true );
				$userLink = Linker::userLink( $row->log_user, $row->user_name );
				$additionalInfo .= wfMessage(
					( is_null( $result->on_behalf ) ? 'approvedrevs-approvedby' : 'approvedrevs-approvedby-onbehalf' ),
					$userLink,
					$timestamp,
					$result->on_behalf,
					$result->on_behalf_comments
					//$row->user_name,
					//$date,
					//$time
				)->text();
			}
			
			return "$pageLink ($additionalInfo)";
		}
		// implicit else:
		return "$pageLink";
	}
	
}
