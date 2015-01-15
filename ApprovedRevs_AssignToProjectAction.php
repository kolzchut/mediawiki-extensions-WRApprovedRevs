<?php
/**
 * Displays information about a page.
 *
 * Copyright © 2012 Dror Snir
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
 *
 * @file
 * @ingroup Actions
 */

class AssignToProjectAction extends FormAction {
	private $old_project;
	private $old_group;
	private $project;
	private $group;
	private $reason;
	
	/**
	 * Returns the name of the action this object responds to.
	 *
	 * @return string lowercase
	 */
	public function getName() {
		return 'assigntoproject';
	}
	
	/*
	protected function getDescription() {
		return $this->msg( 'addwatch' )->escaped();
	}
	*/

	/**
	 * Whether this action requires the wiki not to be locked.
	 *
	 * @return bool
	 */
	public function requiresWrite() {
		return true;
	}
	
	public function getRestriction() {
		return $this->getName();
	}

	protected function preText() {
		if( ApprovedRevs::isAssignedToProject( $this->getTitle() ) ) {
			$text = 'דף זה משוייך כבר לפרויקט <strong>' . 	ApprovedRevs::getProjectName( $this->getTitle() ) . '</strong>.';
			$text .= ' בטופס זה ניתן לשנות את שיוך הדף.';
			$text .= '<ul>';
			$text .= '<li><strong>שימו לב:</strong> שינוי אחד הפרטים כאן יגרום למחיקת כל היסטוריית האישורים של הדף!</li>';
			$text .= '<li>כדי לבטל את שיוך הדף לפרויקט, יש למחוק את תוכן שדה "שם הפרויקט" ולשמור את הטופס</li>';
			$text .= '</ul><br />';
		}
		else {
			$text = 'דף זה אינו משוייך עדיין לפרויקט. יש לציין את הפרטים הבאים:';
		}
		
		return $text;
	}
	
	protected function getFormFields() {
		$fields = array(
            'project-name' => array(
                'class' => 'HTMLTextField',
                'label' => 'שם הפרויקט',
                //'label-message' => 'field1',
                'cssclass' => '',
                'section'	=> 'main',
                'default' => ApprovedRevs::getProjectName( $this->getTitle() ) ?: 'מיזם ניצולי שואה',
				'required' => ApprovedRevs::isAssignedToProject( $this->getTitle() ) ? false : true,
                'validation-callback'	=> 'AssignToProjectAction::validateProjectField',
            ),
            'organization-name' => array(
                'class' => 'HTMLTextField',
                'label' => 'ארגון אחראי',
                //'label-message' => 'field1',
                'cssclass' => '',
				'section'	=> 'main',
                'default' => ApprovedRevs::getOrganizationName( $this->getTitle() ) ?: 'הרשות לזכויות ניצולי השואה',
                'required' => ApprovedRevs::isAssignedToProject( $this->getTitle() ) ? false : true,
                'validation-callback'	=> 'AssignToProjectAction::validateOrganizationField',
            ),
            'group-name' => array(
                'class' => 'HTMLSelectField',
                'label' => 'גורם מאשר',
                'options' => array( 'בחר...' => '' ) + self::getAllGroups(),
				'section'	=> 'main',
                'default' => ApprovedRevs::getGroup( $this->getTitle() ),
                'required' => ApprovedRevs::isAssignedToProject( $this->getTitle() ) ? false : true,
                'validation-callback'	=> 'AssignToProjectAction::validateGroupField',
			),
		   'reason' => array(
                'class' => 'HTMLTextField',
                'label' => 'סיבה',
            ),
			
        );

		return $fields;
	}
	
	public function onSubmit( $data ) {
		//Validation is already mostly done by the form class. Sweet.
		
		$this->project = $data['project-name'];
		$this->organization = $data['organization-name'];
		$this->group = $data['group-name'];
		$this->reason = $data['reason'];

		$this->old_project = ApprovedRevs::getProjectName( $this->getTitle() );
		$this->old_organization = ApprovedRevs::getOrganizationName( $this->getTitle() );
		$this->old_group = ApprovedRevs::getGroup( $this->getTitle() );

		// no change?
		if( $this->project == $this->old_project &&
			$this->organization == $this->old_organization &&
			$this->group == $this->old_group ) {
				return array( 'approvedrevs-form-error-nochange' );
		}

		if( empty( $this->old_project ) ) {
			$action = 'assign';	
		} elseif( empty( $this->project ) ) { 	// Already assigned to project
			$action = 'unassign';
		} else {
			$action = 'reassign';	
		}
		
		ApprovedRevs::logProjectAssignment( $this->getTitle(), $action, $this->getUser(), $this->project, $this->organization, $this->group, $this->reason );
		ApprovedRevs::saveProjectAssociationInDB( $this->getTitle(), $action, $this->project, $this->organization, $this->group );
		return true;		
	}
	
	public function onSuccess() {
		$this->getTitle()->invalidateCache();
		$this->getOutput()->redirect( $this->getTitle()->getFullUrl( $this->redirectParams ) );
		//$this->getOutput()->redirect( $this->getTitle()->getFullUrl() );
	}
	
	protected function alterForm( HTMLForm $form ) {
		$form->setSubmitTextMsg( 'approvedrevs-form-save' );
	}

	/**
	 * Get a list of all explicit groups
	 * Do we need implicit groups as well?
	 * @return array
	 */
	private static function getAllGroups() {
		$result = array();
		foreach( User::getAllGroups() as $group ) {
			$result[User::getGroupName( $group )] = $group;
		}
		asort( $result );
		return $result;
	}

	static function validateProjectField( $value, $alldata, $form ) {
		if( empty( $value ) && !ApprovedRevs::isAssignedToProject( $form->getTitle() ) ) {
			return wfMessage( 'approvedrevs-form-error-noproject' )->parse();
		}

		return true;
	}
	
	static function validateGroupField( $value, $alldata ) {
		if( empty( $value ) && !empty( $alldata['project-name'] ) ) {
			return wfMessage( 'htmlform-select-badoption' )->parse();
		}

		return true;
	}

	static function validateOrganizationField( $value, $alldata ) {
		if( empty( $value ) && !empty( $alldata['project-name'] ) ) {
			return wfMessage( 'htmlform-required' )->parse();
		}

		return true;
	}
	
}


