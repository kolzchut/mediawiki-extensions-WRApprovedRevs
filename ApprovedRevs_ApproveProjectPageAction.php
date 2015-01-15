<?php
/**
 * Approve a project page
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

class ApproveProjectPageAction extends FormAction {
	private $on_behalf;
	private $on_behalf_comments;
	
	/**
	 * Returns the name of the action this object responds to.
	 *
	 * @return string lowercase
	 */
	public function getName() {
		return 'approveprojectpage';
	}

	protected function preText() {
		$title = $this->getTitle();
		$user = $this->getUser();
		
		$text = '<p><strong>האם את/ה מאשר את הגרסה הנוכחית של הערך?</strong></p>';

		return $text;
	}
	
	protected function getForm() {
		if( ! $this->isPageAssignedToProject() ) {	
			throw new ErrorPageError( 'ar-approvalform-generalerror', 'ar-approvalform-pageunassigned' );
		}
		if( ! $this->userCanApprovePage() ) {	
			throw new ErrorPageError( 'ar-approvalform-badaccess', 'ar-approvalform-badaccess-group', ApprovedRevs::getGroupName( $this->getTitle() ) );
		}

		if( ApprovedRevs::isLatestRevisionApproved( $this->getTitle() ) ) {
			throw new ErrorPageError( 'ar-approvalform-generalerror', 'ar-approvalform-alreadyapproved', ApprovedRevs::getGroupName( $this->getTitle() ) );
		}
		
		return parent::getForm();
	}
	
	protected function getFormFields() {
		$fields = array();
		$isDelegate = $this->userCanApprovePageOnBehalf();
		
		if( $isDelegate ) {
			$fields['on-behalf'] = array(
				'class' => 'HTMLTextField',
				'label' => 'אישור בשם',
				'section'	=> 'onbehalf',
				'required' => true,
				//'validation-callback'	=> 'ApproveProjectPageAction::validateOnBehalfField',
			);
		}
		
		$fields['comments'] = array(
			'class' => 'HTMLTextField',
			'label' => 'הערות',
			'section'	=> $isDelegate ? 'onbehalf' : 'comments',
			'required' => $isDelegate,
			//'validation-callback'	=> 'ApproveProjectPageAction::validateOnBehalfCommentsField',
        );

		return $fields;
	}
	
	public function onSubmit( $data ) {
		//Validation is already mostly done by the form class. Sweet.
		
		$this->on_behalf = $data['on-behalf'];
		$this->comments = $data['comments'];
		
		ApprovedRevs::logPageApproval( $this->getTitle(), $this->getUser(), $this->on_behalf, $this->comments );
		ApprovedRevs::savePageApprovalInDB( $this->getTitle(), $this->getUser(), $this->on_behalf, $this->on_behalf_comments );
		return true;
	}
	
	public function onSuccess() {
		$this->getTitle()->invalidateCache();
		$redirectParams = isset( $this->redirectParams ) ? $this->redirectParams : null;
		$this->getOutput()->redirect( $this->getTitle()->getFullUrl( $redirectParams ) );
	}
	
	protected function alterForm( HTMLForm $form ) {
		$form->setSubmitText( 'אישור' );
	}
	
	protected function userCanApprovePage() {
		$title = $this->getTitle();
		$user = $this->getUser();

		return ( ApprovedRevs::userCanApprovePage( $title , $user ) );
	}
	
	protected function userCanApprovePageOnBehalf() {
		return ( $this->userCanApprovePage() && $this->getTitle()->quickUserCan( 'approveprojectonbehalf', $this->getUser() ) );
		
	}
	
	protected function isPageAssignedToProject() {
		return ( ApprovedRevs::isAssignedToProject( $this->getTitle() ) );
	}
	
}


