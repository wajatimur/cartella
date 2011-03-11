<?php

class DATABASE
{

  private $file;
  private $errorMessage;
    
  /******************************************************
    FUNCTION:	getError
    PURPOSE:	returns an existing class error
  ******************************************************/  
  public function getError()
  {
    return $this->errorMessage;
  }
  
  /******************************************************
    FUNCTION:	throwError
    PURPOSE:	throws a class error
  ******************************************************/  
  public function throwError($err)
  {
    $this->errorMessage = $err;
  }
  
  /******************************************************
    FUNCTION:	display
    PURPOSE:	displays the form for entering config
              information
  ******************************************************/  
  public function display()
  {

    $content = "<h3>Database Setup</h3>
                <p>The installer will now create or update your database.</p>
                <p>If you are upgrading your DocMGR installation, please backup your database before continuing.</p>
                ";
                
    return $content;  
  
  }

  /******************************************************
    FUNCTION:	process
    PURPOSE:	writes our submitted values to the
              config file and saves the file
  ******************************************************/  
  public function process()
  {

    $this->checkDB();
    
  }

  protected function checkDB()
  {

    //get our stored config values
    $dbhost = $_SESSION["config"]["Required"]["DBHOST"][0];
    $dbuser = $_SESSION["config"]["Required"]["DBUSER"][0];
    $dbpassword = $_SESSION["config"]["Required"]["DBPASSWORD"][0];
    $dbport = $_SESSION["config"]["Required"]["DBPORT"][0];
    $dbname = $_SESSION["config"]["Required"]["DBNAME"][0];

    //conect to the database    
    $DB = new POSTGRESQL($dbhost,$dbuser,$dbpassword,$dbport,$dbname);

    //see if the database already exists
    $sql = "SELECT tablename FROM pg_tables WHERE tablename NOT LIKE 'pg%'
                                            AND	tablename NOT LIKE 'sql%'";
    $info = $DB->fetch($sql);
    
    if ($info["count"] > 0)
    {

      $dbVersion = 0;

      //keep going until all upgrades are done      
      while ($dbVersion < DB_VERSION)
      {
      
        //get the database's version
        $sql = "SELECT * FROM db_version";
        $ver = $DB->single($sql);
      
        $dbVersion = $ver["version"];

        //stop here.  mission accomplished
        if ($dbVersion==DB_VERSION) break;

        //upgrade if they don't match
        if ($dbVersion > DB_VERSION)
        {

          $this->throwError("You appear to be running a later database version than this installation contains.  Abandoning setup");
          return false;      
      
        }
        else if ($dbVersion < DB_VERSION)
        {
      
          $this->upgradeDB($DB,$dbVersion);
        
        }
     
      //end while loop 
      }
    
    }
    //create from scratch
    else
    {
      $this->createDB($DB);    
    }
  
    return true;
      
  }

  protected function createDB($DB)
  {

    //create the database from scratch
    $sql = file_get_contents("install/docmgr.pgsql");
    $DB->query($sql);

  }

  protected function upgradeDB($DB,$version)
  {
	
    if (!$version || $version<2010041401) 
    {
      $this->upgradeRC10($DB);
      $version = 2010041401;
    }
    
    if ($version<2010101001)
    {
      $this->upgradeRC14($DB);
      $version = 2010101001;
    }
    
    //set our new database version
    $sql = "UPDATE db_version SET version='".DB_VERSION."'";
    $DB->query($sql);
    
  }

  //upgrades from RC9 to RC10
  protected function upgradeRC10($DB)
  {

    //recreate the db_version table and put in the current database version
    $sql = "
            DROP TABLE db_version;
            CREATE TABLE db_version (
              version integer NOT NULL
            );
            INSERT INTO db_version (version) VALUES ('".DB_VERSION."');
            ";
    $DB->query($sql);

    //see if they've setup dashboard for the everyone group
    $sql = "SELECT group_id FROM group_dashboard WHERE group_id='0'";
    $info = $DB->single($sql);

    //nope.  make our own    
    if (!$info)
    {
    
      $sql = "INSERT INTO group_dashboard VALUES ('0','1','1','home','bkmodlet','bkmodlet1');
            INSERT INTO group_dashboard VALUES ('0','1','2','home','taskmodlet','taskmodlet3');
            INSERT INTO group_dashboard VALUES ('0','2','1','home','currentsubscribe','currentsubscribe2');
            INSERT INTO group_dashboard VALUES ('0','2','2','home','subscribealert','subscribealert4');
            ";

            
      $DB->query($sql);
    
    }
    
  }

  //upgrade RC11-13 to RC14
  protected function upgradeRC14($DB)
  {

    //check for path_to_id function
    $sql = "SELECT * FROM docmgr.dm_share LIMIT 1";
    $info = @$DB->single($sql);
    
    //if no error is thrown, we need to stop here
    if (!$DB->error())
    {
      return false;
    }

    $DB->begin();
  
    $sql = "DROP VIEW docmgr.dm_view_collections;
              CREATE VIEW docmgr.dm_view_collections AS
                SELECT dm_object.id, dm_object.name, dm_object.summary, dm_object.object_type, dm_object.create_date, 
                dm_object.object_owner, dm_object.status, dm_object.status_date, dm_object.status_owner, dm_object.version, 
                dm_object.reindex, dm_object.hidden, dm_object_parent.object_id, dm_object_parent.parent_id, dm_object_perm.account_id, 
                dm_object_perm.group_id, dm_object_perm.bitset,dm_object_perm.bitmask
                FROM docmgr.dm_object
                LEFT JOIN docmgr.dm_object_parent ON dm_object.id = dm_object_parent.object_id
                LEFT JOIN docmgr.dm_object_perm ON dm_object.id = dm_object_perm.object_id
                WHERE dm_object.object_type = 'collection'::text;";
            
    $DB->query($sql);
  
    //change alert names
    $sql = "UPDATE docmgr.dm_subscribe SET event_type='OBJ_LOCK_ALERT' WHERE event_type='OBJ_CHECKOUT_ALERT';";
    $sql .= "UPDATE docmgr.dm_subscribe SET event_type='OBJ_UNLOCK_ALERT' WHERE event_type='OBJ_CHECKIN_ALERT';";
    $sql .= "UPDATE docmgr.dm_alert SET alert_type='OBJ_LOCK_ALERT' WHERE alert_type='OBJ_CHECKOUT_ALERT';";
    $sql .= "UPDATE docmgr.dm_alert SET alert_type='OBJ_UNLOCK_ALERT' WHERE alert_type='OBJ_CHECKIN_ALERT';";
    $DB->query($sql);

    //function update
    $sql = "
            CREATE FUNCTION docmgr.path_to_id(path text) RETURNS text
            LANGUAGE plpgsql IMMUTABLE
            AS \$\$
            DECLARE arr text[];
            DECLARE parent integer;
            DECLARE i integer;
            DECLARE parentstr text;

            BEGIN

            arr := string_to_array(path,'/');
            parent := 0;
            parentstr := 0;

            FOR i IN array_lower(arr,1)+1 .. array_upper(arr,1) LOOP
 
              SELECT INTO parent object_id FROM docmgr.dm_view_objects WHERE parent_id=parent AND name=arr[i];
 
              SELECT INTO parentstr (parentstr || ',' || parent);

            END LOOP;
 
            RETURN parentstr;

            END;
            \$\$;
            ";
            
    $DB->query($sql);

    //share upgrades
    $sql = "CREATE TABLE docmgr.dm_share (
              object_id integer NOT NULL,
              account_id integer not null,
              share_account_id integer not null,
              bitmask text
            );
            ALTER TABLE docmgr.dm_object_parent ADD COLUMN account_id integer;
            ALTER TABLE docmgr.dm_object_parent ADD COLUMN share boolean DEFAULT FALSE;
            ALTER TABLE docmgr.dm_object_perm ADD COLUMN share boolean DEFAULT FALSE;
            ALTER TABLE docmgr.dm_object_parent ADD COLUMN workflow_id integer DEFAULT 0;
            ALTER TABLE docmgr.dm_object_perm ADD COLUMN workflow_id integer DEFAULT 0;
            UPDATE docmgr.dm_object_parent SET account_id=(SELECT object_owner FROM docmgr.dm_object WHERE id=dm_object_parent.object_id);
            ";
    $DB->query($sql);

    //view for folder recursion
    $sql = "CREATE VIEW docmgr.dm_view_parent AS
            SELECT docmgr.dm_object_parent.*,dm_object.name,
            dm_object.object_type FROM docmgr.dm_object_parent
            LEFT JOIN docmgr.dm_object ON dm_object_parent.object_id=dm_object.id;

            CREATE VIEW docmgr.dm_view_colsearch AS
            SELECT dm_object.id, dm_object.name, dm_object.summary, dm_object.object_type, dm_object.create_date, dm_object.object_owner, 
            dm_object.status, dm_object.status_date, dm_object.status_owner, dm_object.version, dm_object.reindex, dm_object.hidden, 
            dm_object_parent.object_id, dm_object_parent.parent_id, dm_object_perm.account_id, dm_object_perm.group_id, 
            dm_object_perm.bitset, dm_object_perm.bitmask
            FROM docmgr.dm_object
            LEFT JOIN docmgr.dm_object_parent ON dm_object.id = dm_object_parent.object_id
            LEFT JOIN docmgr.dm_object_perm ON dm_object.id = dm_object_perm.object_id
            WHERE dm_object.object_type = 'collection' OR dm_object.object_type='search';
            ";
    $DB->query($sql);

    $DB->end();
    
    //now we have to make a permissions entry for all object owners that don't have permissions
    $sql = "SELECT id,object_owner FROM docmgr.dm_object WHERE id NOT IN 
            (SELECT object_id FROM docmgr.dm_object_perm WHERE object_id=dm_object.id AND account_id=dm_object.object_owner)";
    $list = $DB->fetch($sql);
    
    for ($i=0;$i<$list["count"];$i++)
    {
    
      $opt = null;
      $opt["object_id"] = $list[$i]["id"];
      $opt["account_id"] = $list[$i]["object_owner"];
      $opt["bitmask"] = "00000001";
      $DB->insert("docmgr.dm_object_perm",$opt);
    
    }
  
  }

}
