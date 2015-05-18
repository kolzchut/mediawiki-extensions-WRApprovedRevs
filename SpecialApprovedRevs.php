<?php

/**
 * Special page that displays various lists of pages that either do or do
 * not have an approved revision.
 *
 */
class SpecialApprovedRevs extends SpecialPage {

	protected $pager;

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
		$this->getOutput()->addModuleStyles( 'mediawiki.special' );

		$request = $this->getRequest();

		$mode = $request->getVal( 'show' );
		$organization = $request->getVal( 'organization' );
		//$group = $request->getVal( 'group' );
		$group = null;

		$this->pager = new ApprovedRevsPager( $this, $mode, $organization, $group );

		$this->getOutput()->addHTML( $this->pager->makeFilterForm() );

		if ( $this->pager->getNumRows() ) {
			$this->getOutput()->addParserOutputContent( $this->pager->getFullOutput() );
		} else {
			$this->getOutput()->addWikiMsg( 'approvedrevs-pager-empty' );
		}

	}

}

class ApprovedRevsPager extends TablePager {
	protected $mMode;
	protected $mOrganization;
	protected $mGroup;

	/** @const */
	protected $mAllowedModes = array( 'all', 'approved', 'notlatest', 'unapproved' );

	public function __construct( $page, $mode, $organization, $group ) {
		parent::__construct( $page->getContext() );

		$this->mMode = in_array( $mode, $this->mAllowedModes ) ? $mode : 'notlatest';
		$this->mOrganization = $organization;
		$this->mGroup = $group;

		$this->mDefaultDirection = IndexPager::DIR_ASCENDING;
		if ( $this instanceof SpecialPage ) {
			parent::__construct( 'ApprovedRevs' );
		}



		//@TODO: need to get group ID from group name! Is there such a function?
		//Maybe get the entire group list with names, and create a select in the filter form.
	}

	function makeFilterForm() {
		$filterForm = Xml::openElement( 'form', array(
			'method' => 'get',
			'action' => $this->getConfig()->get( 'Script' )
		) );
		$filterForm .=
			Xml::openElement( 'fieldset' ) .
			Xml::element(
			  'legend', null, $this->msg( 'approvedrevs-filter-legend' )->text()
			) .
			Html::hidden( 'title', $this->getTitle()->getPrefixedText() ) .
			// Html::hidden( 'show', $this->mMode ) .
			Xml::inputLabel(
			  $this->msg( 'approvedrevs-filter-field-org' )->text(),
			  'organization', 'filter-field-org', null, $this->mOrganization
			) . ' ';
			/*
                Xml::inputLabel(
                    $this->msg('approvedrevs-filter-field-group' )->text(),
                    'group', 'filter-field-group', null, $group ) . ' ' .
			*/

		$filterForm .= $this->getModeSelector();
		$filterForm .=
			Xml::submitButton( $this->msg( 'approvedrevs-filter-field-submit' )->text() ) .
			Xml::closeElement( 'fieldset' ) .
			Xml::closeElement( 'form' );

		return $filterForm;
	}

	/**
	 * Creates the <select> input of the mode
	 * @return string Formatted HTML
	 */
	protected function getModeSelector() {
		$options = array();


		foreach ( $this->mAllowedModes as $mode ) {
			$text = $this->msg( "approvedrevs-{$mode}pages" );

			$options[] = Html::element(
				'option', array(
					'value'    => $mode,
					'selected' => ( $mode === $this->mMode ),
				), $text
			);
		}

		$ret = '';
		$ret .= Html::element(
				'label', array(
				'for' => 'filter-mode-selector',
			), 'סטטוס'
		) . '&#160;';

		// Wrap options in a <select>
		$ret .= Html::rawElement(
			'select',
			array( 'id' => 'filter-mode-selector', 'name' => 'show' ),
			implode( "\n", $options )
		);

		return $ret;
	}


	/**
	 * Mostly copied from SpecialProtectedPages
	 *
	 * @param string $field
	 * @param string $value
	 * @return string
	 * @throws MWException
	 */
	function formatValue( $field, $value ) {
		/** @var $row object */
		$row = $this->mCurrentRow;
		$title = Title::newFromId( $row->id );
		$pageLink = Linker::link( $title );

		switch ( $field ) {
			case 'id':
				$formatted = $pageLink;
				break;
			case 'rev_id':
				if ( $value === null ) {
					$formatted = 'לא אושר אף פעם';
				} else {
					$formatted = ( $row->rev_id === $row->latest_id ) ? 'מאושר'
						: 'לא מאושר ' . $this->getDiffLink( $title, $row->latest_id, $row->rev_id );
				}
				break;
			case 'ap_review_timestamp':
				$formatted = ( $row->rev_id === $row->latest_id )
					? $formatted = $this->getLanguage()->userTimeAndDate( $value, $this->getUser() )
					: '-';
				break;

			default:
				$formatted = $value;
		}

		return $formatted;
	}

	function getFieldNames() {
		$headers = null;

		$headers = array(
			'id' => 'approvedrevs-header-page',
			'rev_id' => 'approvedrevs-header-status',
			'ap_organization' => 'approvedrevs-header-organization',
			'ap_review_timestamp' => 'approvedrevs-header-timestamp',
			//'log_approvedby' => 'approvedrevs-header-approvedby',
		);

		if ( $this->mMode === 'notlatest' || $this->mMode === 'unapproved' ) {
			unset( $headers['ap_review_timestamp'] );
		}

		foreach ( $headers as $key => $val ) {
			$headers[$key] = $this->msg( $val )->text();
		}

		return $headers;
	}

	static function getBaseQueryStructure() {
		$baseQuery = array(
			'tables' => array(
				'ap' => 'approved_pages',
				'p' => 'page',
			),
			'fields' => array(
				'p.page_id AS id',
				'ap_approved_rev_id AS rev_id',
				'p.page_latest AS latest_id',
				'ap_organization',
				'ap_review_timestamp',
				'ap_review_user',
				'ap_review_on_behalf as on_behalf',
				'ap_review_on_behalf_comments as on_behalf_comments',
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

		switch ( $mode ) {
			case 'unapproved':
				$conds[] = 'ap.ap_approved_rev_id IS NULL';
				break;
			case 'approved':
				$conds[] = 'ap.ap_approved_rev_id = p.page_latest';
				break;
			case 'notlatest':
				$conds[] = '(ap.ap_approved_rev_id IS NULL) OR (p.page_latest != ap.ap_approved_rev_id)';
				break;
			case 'all': // Fall through to default
			default:
		}

		$nsConds = self::getNamespacesConds();
		if ( $nsConds ) {
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
		if ( !empty( $wgApprovedRevsNamespaces ) ) {
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


	/**
	 * get count(*) for list
	 *
	 * @param $mode
	 * @param $organization
	 * @param $group
	 *
	 * @return mixed
	 */
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


	function getDefaultSort() {
		return 'ap_organization';
	}

	function isFieldSortable( $field ) {
		/*
		 switch ( $field ) {
			case 'ap_review_timestamp':
			case 'ap_organization':
				return true;
		}
		*/
		// Since there is a small number of entries for now, we use JS sorting instead
		// no index for sorting exists
		return false;
	}

	public function getTableClass() {
		return parent::getTableClass() . ' sortable';
	}

	protected static function getDiffLink( Title $title, $current_rev_id, $approved_rev_id ) {
		$href = $title->getLocalUrl( array(
				'diff' => $current_rev_id,
				'oldid' => $approved_rev_id
			)
		);
		$diffLink = Xml::element(
			'a',
			array( 'href' => $href ),
			wfMessage( 'approvedrevs-special-difflink' )
		);

		return $diffLink;
	}

}
