<?php
/************************************************************************************************************
	ssh_client.php
***********************************************************************************************************/

class SSH_CLIENT {

	protected $sshconn;
	protected $scpconn;
	protected $serverconn;
	protected $errorMessage;
	protected $outfile;
	protected $errfile;
	protected $tmpdir;
	 
	function __construct($serverName) {

		//make a temp directory for the user
		$this->tmpdir = TMP_DIR."/".USER_LOGIN;
		recurMkDir($this->tmpdir);

		//setup our output files
		$this->outfile = $this->tmpdir."/sshout";
		$this->errfile = $this->tmpdir."/ssherr";
		
		$xml = file_get_contents("config/server.xml");
		$arr = xml2array($xml);

		//setup our connection strings
		foreach ($arr["server"] AS $server) {

			if ($server["name"]==$serverName) {

				$this->sshconn = "ssh -i \"".$server["keyfile"]."\" ";
				$this->scpconn = "scp -i \"".$server["keyfile"]."\" ";
				$this->serverconn = $server["login"]."@".$server["name"];
				break;
							
			}
			
		}	

		if (!$this->sshconn) return false;
 
	}

	function run($command) {
	
		$cmd = $this->sshconn." ".$this->serverconn." \"".$command."\" 1>".$this->outfile." 2>".$this->errfile;
		`$cmd`;

		//look for errors
		$str = file_get_contents($this->errfile);
		if (strlen($str)>0) $this->throwError($str);

		//return stdout
		return file_get_contents($this->outfile);
			
	}

	function throwError($msg) {
		$this->errorMessage = $msg;
	}
	
	function getError() {
		return $this->errorMessage;
	}

	function copytoserver($src,$dst) {
	
		//add server prefix to file destination
		$dst = $this->serverconn.":".$dst;
	
		$cmd = $this->scpconn." \"".$src."\" \"".$dst."\" 1>".$this->outfile." 2>".$this->errfile;
		`$cmd`;

		//look for errors
		$str = file_get_contents($this->errfile);
		if (strlen($str)>0) $this->throwError($str);
			
	}

	function copytoclient($src,$dst,$noerror=null) {
	
		//add server prefix to file destination
		@unlink($dst);

		$src = $this->serverconn.":".$src;
	
		$cmd = $this->scpconn." \"".$src."\" \"".$dst."\" 1>".$this->outfile." 2>".$this->errfile;
		`$cmd`;

		//look for errors
		if (!$noerror) {
			$str = file_get_contents($this->errfile);
			if (strlen($str)>0) $this->throwError($str);
		}
					
	}

	function getFile($src,$noerror=null) {

		//where to put file from server
		$dst = $this->tmpdir."/getfile.dat";
		
		//copy the file to a local directory and snag it's contents
		$this->copytoclient($src,$dst,$noerror);	

		return @file_get_contents($dst);
	
	}
	
	function writeFile($content,$dst) {

		//write content to our source file	
		$src = $this->tmpdir."/writefile.dat";
		file_put_contents($src,$content);

		//copy the file to the server
		$this->copytoserver($src,$dst);	
	
	}

	function deleteFile($file,$noerror=null) {

		$command = "rm \"".$file."\"";	
		$cmd = $this->sshconn." ".$this->serverconn." \"".$command."\" 1>".$this->outfile." 2>".$this->errfile;
		`$cmd`;

		//look for errors
		$str = file_get_contents($this->errfile);
		if (strlen($str)>0 && !$noerror) $this->throwError($str);
			
	}

}
