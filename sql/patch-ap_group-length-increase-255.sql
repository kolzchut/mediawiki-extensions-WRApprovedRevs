ALTER TABLE /*_*/approved_pages
	MODIFY COLUMN ap_user_group varbinary(255) NOT NULL default '';
