CREATE UNIQUE INDEX /*i*/approved_pages_page_id ON /*_*/approved_pages (ap_page_id);
CREATE INDEX /*i*/approved_pages_organization ON /*_*/approved_pages (ap_organization);
CREATE INDEX /*i*/approved_pages_review_timestamp ON /*_*/approved_pages (ap_review_timestamp);
