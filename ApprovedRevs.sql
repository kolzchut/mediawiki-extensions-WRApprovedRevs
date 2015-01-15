-- Replace /*_*/ with the proper prefix
-- Replace /*$wgDBTableOptions*/ with the correct options

-- Add page tracking table for approved revisions
CREATE TABLE IF NOT EXISTS /*_*/approved_pages (
  -- Foreign key to page.page_id
  ap_page_id integer unsigned NOT NULL PRIMARY KEY,
  -- page approval project name
  ap_project varchar(255) binary NOT NULL,
  -- responsible organization name
  ap_organization varchar(255) binary NOT NULL,
  -- page approval group
  ap_user_group varbinary(255) NOT NULL,
  -- Reviewing:
  -- Foreign key to approved_revs.ar_rev_id
  ap_approved_rev_id integer unsigned NULL,
  -- Reviewing user  
  ap_review_user integer unsigned NULL,
  -- Timestamp of review
  ap_review_timestamp varbinary(14) NULL,
  -- When reviewing on behalf of a non-user:
  ap_review_on_behalf varchar(255) binary,
  ap_review_on_behalf_comments varchar(255) binary
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/ap_project_name ON /*_*/approved_pages (ap_project,ap_page_id);
