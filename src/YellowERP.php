<?php
/************************************************************************************
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

final class YellowERP extends OData
{
    protected      $httpServices  = null;
    protected      $maxRetries    = 3;
    protected      $reuseSessions = false;
    protected      $servicesURL   = null;
    protected      $sessionFile   = null;
    protected      $timeOutLimit  = 30;
    private static $ibsession     = null;
    private static $retry         = 0;
    private static $sessionExist  = false;

    public function connect() {
        if(!is_resource($this->dbh)) {
            $this->setupCurl($this->dsn);
        }

        if($this->reuseSessions && !self::$sessionExist) {
            self::$retry++;
            if(!file_exists($this->sessionFile)) {
                touch($this->sessionFile);
            }
            $IBSession = file_get_contents($this->sessionFile);

            if($IBSession) {
                self::$ibsession = $IBSession;
            }

            if(self::$retry > $this->maxRetries) {
                $url = curl_getinfo($this->dbh, CURLINFO_EFFECTIVE_URL);
                throw new Exception("Too many connection retiries. Can't initiate session. URL: '{$url}'");
            }

            if(self::$ibsession) {
                curl_setopt($this->dbh, CURLOPT_COOKIE, "ibsession=" . self::$ibsession);
            }
            else {
                curl_setopt($this->dbh, CURLOPT_COOKIE, null);
                curl_setopt($this->dbh, CURLOPT_HTTPHEADER, [ 'IBSession: start' ]);
            }
        }

        curl_setopt($this->dbh, CURLOPT_TIMEOUT, $this->timeOutLimit);

        $response    = curl_exec($this->dbh);
        $header_size = curl_getinfo($this->dbh, CURLINFO_HEADER_SIZE);

        $this->header   = substr($response, 0, $header_size);
        $this->body     = substr($response, $header_size);
        $this->httpcode = curl_getinfo($this->dbh, CURLINFO_HTTP_CODE);
        //$url            = curl_getinfo($this->dbh, CURLINFO_EFFECTIVE_URL);

        if($this->reuseSessions && !self::$sessionExist) {

            //if ($this->httpcode  == 0) { throw new Exception("No connection to: '$url', {$this->body}"); }
            if($this->httpcode == 406) {
                throw new Exception("406 Not Acceptable. YellowERP can't initiate new session");
            }
            if($this->httpcode == 400 || $this->httpcode == 404 || $this->httpcode == 0) {
                file_put_contents($this->sessionFile, null);
                self::$ibsession = null;

                return $this->connect();
            }

            preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $this->header, $matches);

            $cookies = [];
            foreach($matches[1] as $item) {
                parse_str($item, $cookie);
                $cookies = array_merge($cookies, $cookie);
            }
            if($cookies['ibsession']) {
                self::$ibsession = $cookies['ibsession'];
                file_put_contents($this->sessionFile, $cookies['ibsession']);
            }
            self::$retry = 0;
        }

        if($this->httpcode >= 200 && $this->httpcode < 300) {
            if($this->reuseSessions && !self::$sessionExist) {
                curl_setopt($this->dbh, CURLOPT_COOKIE, "ibsession=" . self::$ibsession);
                @setcookie('IBSession', self::$ibsession, time() + 60 * 60 * 24, '/');
                self::$sessionExist = true;
            }
        }
        else {
            if(!$this->reuseSessions && $this->httpcode == 0 && self::$retry < $this->maxRetries) {
                self::$retry++;

                return $this->connect();
            }
            else {
                $this->parseError();
            }
        }
        self::$retry = 0;

        return $this;
    }

    public function execute() {
        if($this->servicesURL) {
            $this->result = null;

            $this->tryGetFromCache();

            // If not found in cache, then let's get via HTTP request
            if($this->result === null) {

                $this->setupCurl($this->httpServices . $this->servicesURL);
                $this->connect();

                // Will return NULL in case of failure
                $this->result = json_decode($this->body, true);

                $this->storeResultToCache();
            }

            $this->servicesURL = null;

            return $this->result;
        }
        else {
            return parent::execute(func_get_args());
        }
    }

    public function finish() {
        if($this->dbh && self::$ibsession) {
            curl_setopt($this->dbh, CURLOPT_URL, $this->dsn);
            curl_setopt($this->dbh, CURLOPT_COOKIE, "ibsession=" . self::$ibsession);
            curl_setopt($this->dbh, CURLOPT_HTTPHEADER, [ 'IBSession: finish' ]);
            curl_exec($this->dbh);
        }
        file_put_contents($this->sessionFile, null);
        self::$ibsession = null;

        return $this;
    }

    /*--------------------- reuseSessions="use" --------------------*/

    public function httpServices($httpServices) {
        $this->httpServices = $httpServices;

        return $this;
    }

    /*--------------------- reuseSessions="use" --------------------*/

    public function reuseSessions($use = false, $maxRetries = 3, $file = 'YellowERP.ses') {
        $this->reuseSessions = $use;
        $this->maxRetries    = $maxRetries;
        $this->sessionFile   = $file;

        return $this;
    }

    /*------------------- Set's HTTP service URL -------------------*/

    public function service($url) {
        $this->dropVars();

        $this->servicesURL = $url;

        // We have to fake, otherwise DBD will issue exception on cache for non select query
        $this->query = "SELECT * FROM $url";

        return $this;
    }
}