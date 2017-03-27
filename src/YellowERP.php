<?php
/*************************************************************************************
 *   MIT License                                                                     *
 *                                                                                   *
 *   Copyright (C) 2009-2017 by Nurlan Mukhanov <nurike@gmail.com>                   *
 *                                                                                   *
 *   Permission is hereby granted, free of charge, to any person obtaining a copy    *
 *   of this software and associated documentation files (the "Software"), to deal   *
 *   in the Software without restriction, including without limitation the rights    *
 *   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell       *
 *   copies of the Software, and to permit persons to whom the Software is           *
 *   furnished to do so, subject to the following conditions:                        *
 *                                                                                   *
 *   The above copyright notice and this permission notice shall be included in all  *
 *   copies or substantial portions of the Software.                                 *
 *                                                                                   *
 *   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR      *
 *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,        *
 *   FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE     *
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER          *
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,   *
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE   *
 *   SOFTWARE.                                                                       *
 ************************************************************************************/
 
 namespace DBD;
 use Exception;
 
 final class YellowERP extends OData {

	private static $retry = 0;
	private static $ibsession = null;
	
	protected $reuseSessions = false;
	protected $maxRetries = 3;
	protected $httpServices = null;
	protected $servicesURL = null;
	
	//FIXME: initiate session establishment with execute
	// to avoid unnecessary url request
	public function connect() {
		if ($this->myDebug) {echo("YellowERP connector\n");}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->dsn);
		curl_setopt($ch, CURLOPT_USERAGENT, 'DBD\YellowERP driver');
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		if ($this->username && $this->password) {
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, $this->username.":".$this->password);
		}
		curl_setopt($ch, CURLOPT_POST, false);
		
		if ($this->reuseSessions) {
			self::$retry++;
			if (!file_exists('YellowERP.ses') ) {
				touch('YellowERP.ses');
			}
			$IBSession = file_get_contents('YellowERP.ses');
			
			if ($IBSession && self::$retry == 1) {
				self::$ibsession = $IBSession;
			}
			
			if (self::$retry > $this->maxRetries) {
				throw new Exception("Too many connection retiries. Can't initiate session");
			}
			
			if (self::$ibsession) {
				curl_setopt($ch, CURLOPT_COOKIE, "ibsession=".self::$ibsession);
				if ($this->myDebug) {echo("Reusing session: ".self::$ibsession."\n");}
			} else {
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('IBSession: start'));
				if ($this->myDebug) {echo("Starting session\n");}
			}
		}
		
		$response  = curl_exec($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if ($this->reuseSessions) {
			if ($httpcode == 0) { throw new Exception("No connection"); }
			if ($httpcode == 406) { throw new Exception("406 Not Acceptable. ERP can't initiate new session"); }
			if ($httpcode == 400 || $httpcode == 404) {self::$ibsession = null; return $this->connect(); } 
			
			preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header, $matches);
			
			$cookies = array();
			foreach($matches[1] as $item) {
				parse_str($item, $cookie);
				$cookies = array_merge($cookies, $cookie);
			}
			if ($cookies['ibsession']) {
				self::$ibsession = $cookies['ibsession'];
				if ($this->myDebug) {echo("Writing to YellowERP.ses: ".$cookies['ibsession']."\n");}
				file_put_contents('YellowERP.ses',$cookies['ibsession']);
			}
			self::$retry = 0;
		}
		
		if ($httpcode>=200 && $httpcode<300) {
			@setcookie('IBSession', self::$ibsession, time() + 60*60*24, '/');
			$this->dbh = $ch;
			return $this;
		} else {
			trigger_error($body, E_USER_ERROR);
		}
	}
	
	public function execute(){
		if ($this->servicesURL) {
			$this->result = null;
			
			$this->tryGetFromCache();
			
			// If not found in cache, then let's get via HTTP request
			if ($this->result === null) {

				if ($this->myDebug) {echo("HTTP request\n");}
				$this->result = $this->queryOData($this->servicesURL, $this->httpServices, null);
				
				$this->storeResultToache();
			}
			
			if ( $this->result === null)  {
				new ErrorHandler ("error happen"); //FIXME:
			}
			$this->servicesURL = null;
			
			return $this->result;
		} else {
			return parent::execute(func_get_args());
		}
	}
	
	public function finish()
	{
		if ($this->dbh && self::$ibsession) {
			curl_setopt($this->dbh, CURLOPT_URL, $this->dsn);
			curl_setopt($this->dbh, CURLOPT_COOKIE, "ibsession=".self::$ibsession);
			curl_setopt($this->dbh, CURLOPT_HTTPHEADER, array('IBSession: finish'));
			curl_exec($this->dbh);
		}
		file_put_contents('YellowERP.ses',null);
		self::$ibsession = null;
		return $this;
	}
	
	public function reuseSessions($use = true, $maxRetries = 3) {
		$this->reuseSessions = true;
		$this->maxRetries = $maxRetries;
		
		return $this;
	}
	
	public function service($url) {

		if (!$this->isConnected()) {
			$this->connect();
		}
		
		$this->dropVars();
		
		$this->servicesURL = $url;

		$this->query = "SELECT"; // We have to fake, otherwise DBD will issue exception on cache for non select query
		
		return $this;
	}
	
	public function httpServices($httpServices)
	{
		$this->httpServices = $httpServices;
		return $this;
	}
 }