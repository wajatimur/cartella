CREATE TABLE docmgr.object_view (
object_id integer not null,
account_id integer not null,
view text default 'list'
);
  
CREATE INDEX object_view_object_object_id_idx ON docmgr.object_view USING btree(object_id);
  
CREATE INDEX object_view_object_account_id_idx ON docmgr.object_view USING btree(account_id);
  