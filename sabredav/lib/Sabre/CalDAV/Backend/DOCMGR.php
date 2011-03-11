<?php

/**
 * DOCMGR CalDAV backend
 *
 * This backend is used to store calendar-data in a DOCMGR database, such as 
 * sqlite or MySQL
 * 
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Backend_DOCMGR extends Sabre_CalDAV_Backend_Abstract {

    /**
     * pdo 
     * 
     * @var DOCMGR
     */
    private $pdo;

    /**
     * List of CalDAV properties, and how they map to database fieldnames
     *
     * Add your own properties by simply adding on to this array
     * 
     * @var array
     */
    public $propertyMap = array(
        '{DAV:}displayname'                          => 'displayname',
        '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'description',
        '{urn:ietf:params:xml:ns:caldav}calendar-timezone'    => 'timezone',
        '{http://apple.com/ns/ical/}calendar-order'  => 'calendarorder',
        '{http://apple.com/ns/ical/}calendar-color'  => 'calendarcolor',
    );

    /**
     * Creates the backend 
     * 
     * @param DOCMGR $pdo 
     */
    public function __construct() {

    }

    /**
     * Returns a list of calendars for a principal
     *
     * @param string $userUri 
     * @return array 
     */
    public function getCalendarsForUser($principalUri) {

        $fields = array_values($this->propertyMap);
        $fields[] = 'id';
        $fields[] = 'uri';
        $fields[] = 'ctag';
        $fields[] = 'components';
        $fields[] = 'principaluri';

        global $DOCMGR;

        $opt = null;
        $opt["command"] = "calendar_ical_getcalendars";
        $opt["caldav"] = "t";
        $arr = $DOCMGR->call($opt);

        $len = count($arr["calendar"]);

        $calendars = array();

        for ($i=0;$i<$len;$i++)
        {

          $row = $arr["calendar"][$i];

          //rename some stuff
          $row["principaluri"] = $row["principal_uri"];
          $row["displayname"] = $row["display_name"];
          $row["calendarcolor"] = $row["color"];

          //remap some fields
          if (!$row["calendarorder"]) $row["calendarorder"] = $i+1;
          if (!$row["calendarcolor"]) $row["calendarcolor"] = "#2CA10BFF";

          $components = explode(',',$row['components']);

          $calendar = array(
            'id' => $row['id'],
            'uri' => $row['uri'],
            'principaluri' => $row['principaluri'],
            '{' . Sabre_CalDAV_Plugin::NS_CALENDARSERVER . '}getctag' => $row['ctag'],
            '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}supported-calendar-component-set' => new Sabre_CalDAV_Property_SupportedCalendarComponentSet($components),
          );

        foreach($this->propertyMap as $xmlName=>$dbName) {
          $calendar[$xmlName] = $row[$dbName];
        }

        $calendars[] = $calendar;

      }

      return $calendars;

    }


    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to reference
     * this calendar in other methods, such as updateCalendar
     *
     * @param string $principalUri
     * @param string $calendarUri
     * @param array $properties
     * @return mixed
     */
    public function createCalendar($principalUri,$calendarUri, array $properties) {

			global $DOCMGR;

			$fieldNames = array(
			            'principaluri',
			            'uri',
			            'ctag',
			            );
			$values = array(
			            ':principaluri' => $principalUri,
			':uri'=> $calendarUri,
			':ctag' => 1,
			);
			
			// Default value
			$fieldNames[] = 'components';
	
			$sccs = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';
			if (!isset($properties[$sccs])) {
			  $values[':components'] = 'VEVENT,VTODO';
			} else {
			  if (!($properties[$sccs] instanceof Sabre_CalDAV_Property_SupportedCalendarComponentSet)) {
			    throw new Sabre_DAV_Exception('The ' . $sccs . ' property must be of type: Sabre_CalDAV_Property_SupportedCalendarComponentSet');
	      }
	      $values[':components'] = implode(',',$properties[$sccs]->getValue());
			}
			
			foreach($this->propertyMap as $xmlName=>$dbName) {
			  if (isset($properties[$xmlName])) {
			
			    $myValue = $properties[$xmlName];
			    $values[':' . $dbName] = $properties[$xmlName];
			    $fieldNames[] = $dbName;
			  }
	
			}

			//dump into an array to pass to DOCMGR
      $num = count($fieldNames);
      $keys = array_keys($values); 

      $opt = array();
                                
      for ($i=0;$i<$num;$i++) $opt[$fieldNames[$i]] = $keys[$i];
        
      $opt = $this->reformatData($data);
      $opt["command"] = "calendar_ical_savecalendar";
      $opt["caldav"] = "t";
      $arr = $DOCMGR->call($opt);
        
      return $arr["calendar_id"];
        
    }

		/**
		 * Updates a calendars properties 
		 *
		 * The properties array uses the propertyName in clark-notation as key,
		 * and the array value for the property value. In the case a property
		 * should be deleted, the property value will be null.
		 *
		 * This method must be atomic. If one property cannot be changed, the
		 * entire operation must fail.
		 *
		 * If the operation was successful, true can be returned.
		 * If the operation failed, false can be returned.
		 *
		 * Deletion of a non-existant property is always succesful.
		 *
		 * Lastly, it is optional to return detailed information about any
		 * failures. In this case an array should be returned with the following
		 * structure:
		 *
		 * array(
		 * 403 => array(
		 *'{DAV:}displayname' => null,
		 * ),
		 * 424 => array(
		 *'{DAV:}owner' => null,
		 * )
		 * )
		 *
		 * In this example it was forbidden to update {DAV:}displayname. 
		 * (403 Forbidden), which in turn also caused {DAV:}owner to fail
		 * (424 Failed Dependency) because the request needs to be atomic.
		 *
		 * @param string $calendarId
		 * @param array $properties
		 * @return bool|array 
		 */
		public function updateCalendar($calendarId, array $properties) {
		
			$newValues = array();
			$result = array(
			        200 => array(), // Ok
			        403 => array(), // Forbidden
			        424 => array(), // Failed Dependency
			);
			
			$hasError = false;
			
			foreach($properties as $propertyName=>$propertyValue) {
			
				// We don't know about this property. 
				if (!isset($this->propertyMap[$propertyName])) {
  				$hasError = true;
  				$result[403][$propertyName] = null;
  				unset($properties[$propertyName]);
  				continue;
				}
				
				$fieldName = $this->propertyMap[$propertyName];
				$newValues[$fieldName] = $propertyValue;
			
			}
			
			// If there were any errors we need to fail the request
			if ($hasError) {
 
  			// Properties has the remaining properties
				foreach($properties as $propertyName=>$propertyValue) {
  				$result[424][$propertyName] = null;
				}
				
				// Removing unused statuscodes for cleanliness
				foreach($result as $status=>$properties) {
  				if (is_array($properties) && count($properties)===0) unset($result[$status]);
        }	
					
	  		return $result;
			
			}
		
			// Success
			
      // If the values array is empty, it means no supported
      // field are updated. The result should only contain 403 statuses
      if (count($newValues)===0) return $result;
    
      $opt = null;
            
      foreach($newValues as $fieldName=>$value) {
        $opt[$fieldName] = $value;
      }

      $opt = $this->reformatData($opt);
      $opt["command"] = "calendar_ical_savecalendar";
      $opt["calendar_id"] = $calendarId;
      $opt["caldav"] = "t";
      $arr = $DOCMGR->call($opt);
			
			return true; 
		
		}
	

    //reformats his fields to our database
    private function reformatCalData($data)
    {
    
      if ($data["principaluri"]) 
      {
        $data["principal_uri"] = $data["principaluri"];
        unset($data["principaluri"]);
      }        

      if ($data["displayname"]) 
      {
        $data["display_name"] = $data["displayname"];
        unset($data["displayname"]);
      }        

      if ($data["calendarcolor"]) 
      {
        $data["color"] = $data["calendarcolor"];
        unset($data["calendarcolor"]);
      }        

      return $data;
    
    }

	/**
	 * Delete a calendar and all it's objects 
	 * 
	 * @param string $calendarId 
	 * @return void
	 */
	public function deleteCalendar($calendarId) {
	
	  global $DOCMGR;
	      
	  $opt = null;
	  $opt["command"] = "calendar_ical_deletecalendar";
	  $opt["calendar_id"] = $calendarId;
	  $DOCMGR->call($opt);
                              	
	}
	
	/**
	 * Returns all calendar objects within a calendar object. 
	 * 
	 * @param string $calendarId 
	 * @return array 
	 */
	public function getCalendarObjects($calendarId) {
	
	  global $DOCMGR;

	  $opt = null;
	  $opt["command"] = "calendar_ical_getevents";
	  $opt["calendar_id"] = $calendarId;
	  $arr = $DOCMGR->call($opt);

	  $event = array();
	  $num = count($arr["event"]);

	  for ($i=0;$i<$num;$i++)
	  {

	    $row = $arr["event"][$i];
	    $row["calendardata"] = $row["data"];
	    $row["calendarid"] = $row["calendar_id"];
	    $row["lastmodified"] = $row["last_modified"];

	    $event[] = $row;
  
    }
  
    return $event;
                                                                                                                          	
	}
	
	/**
	 * Returns information from a single calendar object, based on it's object uri. 
	 * 
	 * @param string $calendarId 
	 * @param string $objectUri 
	 * @return array 
	 */
	public function getCalendarObject($calendarId,$objectUri) {
	
	  global $DOCMGR;

	  $opt = null;
	  $opt["command"] = "calendar_ical_getevents";
	  $opt["calendar_id"] = $calendarId;
	  $opt["uri"] = $objectUri;
	  $arr = $DOCMGR->call($opt);

	  if ($arr["event"])
	  {

	    $row = $arr["event"][0];
	    $row["calendardata"] = $row["data"];
	    $row["calendarid"] = $row["calendar_id"];
	    $row["lastmodified"] = $row["last_modified"];

	    return $row;
  
    } else return array();
                                                                                          	
	}
	
	/**
	* Creates a new calendar object. 
	* 
	* @param string $calendarId 
	* @param string $objectUri 
	* @param string $calendarData 
	* @return void
	*/
	public function createCalendarObject($calendarId,$objectUri,$calendarData) {
	
		global $DOCMGR;
		
		$opt = null;
		$opt["command"] = "calendar_ical_saveevent";
		$opt["calendar_id"] = $calendarId;
		$opt["uri"] = $objectUri;
		$opt["create"] = 1;
		$opt["data"] = $calendarData;
		$arr = $DOCMGR->call($opt);
		
	}
	
	/**
	* Updates an existing calendarobject, based on it's uri. 
	* 
	* @param string $calendarId 
	* @param string $objectUri 
	* @param string $calendarData 
	* @return void
	*/
	public function updateCalendarObject($calendarId,$objectUri,$calendarData) {
	
		global $DOCMGR;
		
		$opt = null;
		$opt["command"] = "calendar_ical_saveevent";
		$opt["calendar_id"] = $calendarId;
		$opt["update"] = 1;
		$opt["uri"] = $objectUri;
		$opt["data"] = $calendarData;
		$arr = $DOCMGR->call($opt);
	
	}
	
	/**
	* Deletes an existing calendar object. 
	* 
	* @param string $calendarId 
	* @param string $objectUri 
	* @return void
	*/
	public function deleteCalendarObject($calendarId,$objectUri) {
	
		global $DOCMGR;
		
		$opt = null;
		$opt["command"] = "calendar_ical_deleteevent";
		$opt["calendar_id"] = $calendarId;
		$opt["uri"] = $objectUri;
		$info = var_export($opt,true);
		
		$arr = $DOCMGR->call($opt);
	
	}
	
	
		
}
		