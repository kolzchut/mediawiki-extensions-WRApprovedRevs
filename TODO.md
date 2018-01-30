# Todo

- Fix the logs: currently the extension pushes an entire 
  link (anchor) into the log, instead of just the
  revision ID and using a custom LogFormatter to display
  a link.
  Such a change might require editing the previous log
  entries in the DB, or separating "legacy" log items
  from new ones and dealing with each in the LogFormatter.

- Send an automatic notification to the project manager
  and a last approver when a page is changed.
