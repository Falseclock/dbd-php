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

namespace DBD\Base;

final class DBDConfig
{
    /** @var string $dsn */
    private $dsn;
    /** @var int $port */
    private $port;
    /** @var string $database */
    private $database;
    /** @var string $username */
    private $username;
    /** @var string $password */
    private $password;
    /** @var string $identity Connection Name */
    private $identity = "DBD-PHP";

    public function __construct($dsn, $port, $database, $username, $password, $identity = null) {
        $this->dsn = $dsn;
        $this->port = $port;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->identity = isset($identity) ? $identity : $this->identity;
    }

    /**
     * @return string
     */
    public function getIdentity() {
        return $this->identity;
    }

    /**
     * @param string $identity
     *
     * @return \DBD\Base\DBDConfig
     */
    public function setIdentity($identity) {
        $this->identity = $identity;

        return $this;
    }

    /**
     * @return string
     */
    public function getDsn() {
        return $this->dsn;
    }

    /**
     * @param string $dsn
     *
     * @return \DBD\Base\DBDConfig
     */
    public function setDsn($dsn) {
        $this->dsn = $dsn;

        return $this;
    }

    /**
     * @return int
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * @param int $port
     *
     * @return \DBD\Base\DBDConfig
     */
    public function setPort($port) {
        $this->port = $port;

        return $this;
    }

    /**
     * @return string
     */
    public function getDatabase() {
        return $this->database;
    }

    /**
     * @param string $database
     *
     * @return \DBD\Base\DBDConfig
     */
    public function setDatabase($database) {
        $this->database = $database;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return \DBD\Base\DBDConfig
     */
    public function setUsername($username) {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return \DBD\Base\DBDConfig
     */
    public function setPassword($password) {
        $this->password = $password;

        return $this;
    }
}