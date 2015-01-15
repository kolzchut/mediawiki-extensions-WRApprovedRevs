<?php
/**
 * Internationalization file for the Approved Revs extension.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author Dror Snir
 */
$messages['en'] = array(
	'approvedrevs-desc' => 'מערכת ניהול אישורים לדפים',
	'assigntoproject'        => 'שיוך דף זה לפרויקט',
	'action-assigntoproject' => 'לשנות את שיוך הדף לפרויקט',
	'btn-assigntoproject'	=> 'שיוך לפרויקט',
	'btn-reassigntoproject' => 'שינוי שיוך',
	'approvedrevs-form-save' => 'שמור שיוך',
	'approvedrevs-form-error-nochange' => 'שגיאה: לא עשית כל שינוי.',
	'approvedrevs-form-error-noproject' => 'הדף עדיין אינו משוייך - חובה להגדיר פרויקט.',	
	'assigntoproject-main'    => 'פרטי שיוך',

	'approveprojectpage'		  => 'אישור גרסה זו של הדף',
	'approveprojectpage-onbehalf'	=> 'אישור בשם אדם אחר (שדות חובה)',
	'approveprojectpage-comments'	=> 'יש לך הערות?',

	'group-projectassigner'         => 'מנהלי מיזם',	
	'group-projectassigner-member'  => '{{GENDER:$1|מנהל|מנהלת}} מיזם',
	#'grouppage-projectassigner'    => '{{ns:project}}:Project assigners',
	'group-projectdelegate'         => 'מינהלת מיזם ניצולי שואה',
	'group-projectdelegate-member'  => 'מינהלת מיזם ניצולי שואה',
	'group-holocaustauthority'         => 'הרשות לזכויות ניצולי השואה',
	'group-holocaustauthority-member'  => '{{GENDER:$1|נציג|נציגת}} הרשות לזכויות ניצולי השואה',

	'right-assigntoproject'         => 'שיוך דף לפרויקט',
	'right-approveprojectonbehalf'         => 'אישור גרסה של דף בשם מישהו אחר',
	'right-seeprojectstatusalways'         => 'הצגת באנר וסטטוס הדף למרות הגבלה לחברי הפרויקט',

	
	
	/* For the special page (?) */
	'approvedrevs' => 'פרויקטים',
	'approvedrevs-approvedpages' => 'דפים מאושרים',
	'approvedrevs-notlatestpages' => 'דפים ממתינים לאישור',
	'approvedrevs-unapprovedpages' => 'דפים שמעולם לא אושרו',
	'approvedrevs-allpages' => 'כל הדפים',
	'approvedrevs-view' => 'תצוגה:',
	'approvedrevs-revisionnumber' => 'גרסה $1',
	'approvedrevs-approvedby' => 'אושר על־ידי $1 ב־$2',
	'approvedrevs-approvedby-onbehalf' => 'אושר על־ידי $1 ב־$2, בשם $3 ($4)',
	'approvedrevs-difffromlatest' => 'השוואה בין הגרסה אחרונה שאושרה לבין הגרסה הנוכחית',
	'approvedrevs-approvelatest' => 'לאשר את הגרסה האחרונה',
	'approvedrevs-approvethisrev' => 'לאשר את הגרסה הזאת.',
	'action-viewapprovedrevsspecialpage' => 'לצפות בעמוד סטטוס הפרויקטים',
	'approvedrevs-filter-legend'	=> 'סינון',
	'approvedrevs-filter-field-org'	=> 'ארגון:',
	'approvedrevs-filter-field-group'	=> 'קבוצה אחראית:',
	'approvedrevs-filter-field-submit' => 'הצגה',
	
	/**
	 * Approval form		
	 */
	'ar-approvalform-generalerror' => 'לא ניתן לבצע פעולה זו בדף זה.',
	'ar-approvalform-pageunassigned' => 'לא ניתן לאשר דף זה מכיוון שאינו שייך לאף פרויקט.',
	'ar-approvalform-alreadyapproved' => 'הגרסה העדכנית ביותר של דף זה כבר אושרה.',

	'ar-approvalform-badaccess' => 'שגיאה בהרשאות',
	'ar-approvalform-badaccess-group' => 'אישור העמוד מוגבל למשתמשים בקבוצה הבאה: $1.',
	
	
	// Logging
	'log-name-approvedrevs' => 'יומן דפי פרויקטים',
	'log-description-approvedrevs' => 'יומן זה שומר שיוך של דפים לפרויקטים והיסטוריית אישורים.',
	'logentry-approvedrevs-assign' => '$1 {{GENDER:$2|שייך}} את הדף $3 לפרויקט $4.
ארגון אחראי: $6,
גורם מאשר: $5',
	'logentry-approvedrevs-reassign' => '$1 {{GENDER:$2|שינה|שינתה}} את שיוך הדף $3. השיוך החדש - 
פרויקט: $4, 
ארגון אחראי: $6, 
גורם מאשר: $5',
	'logentry-approvedrevs-unassign' => '$1 {{GENDER:$2|ביטל|ביטלה}} את שיוך הדף $3 לפרויקט $4',
	'logentry-approvedrevs-approve' => '$1 {{GENDER:$2|אישר|אישרה}} את גרסה $4 של הדף $3 בפרויקט "$5"',
	'logentry-approvedrevs-approvewithcomment' => '$1 {{GENDER:$2|אישר|אישרה}} את גרסה $4 של הדף $3 בפרויקט "$5" ($7)',
	'logentry-approvedrevs-approveonbehalf' => '$1 {{GENDER:$2|אישר|אישרה}} בשם $6 ($7) את גרסה $4 של הדף $3 בפרויקט "$5"',
	'logentry-approvedrevs-unapprovedsave' => 'הדף $3 (פרויקט "$5") יצא מאישור בגרסה $4 לאחר שנשמר ע"י {{GENDER:$2|המשתמש|המשתמשת}} $1',

	// Holocaust Project Banner
	'ar_project_banner_מיזם_ניצולי_שואה' => '<div id="ar-banner-holo"><!--
--><span class="ar-banner-logo ar-banner-logo-israel"></span><!--
--><span class="ar-banner-logo ar-banner-logo-holocaust_authority"></span><!--
--><div class="ar-banner-title">המידע מוגש כחלק [[המיזם המשותף לזכויות ניצולי השואה|מהמיזם המשותף]] עם הרשות לזכויות ניצולי השואה</div><!--
--></div>',

);

