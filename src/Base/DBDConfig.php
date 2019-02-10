<?php
/**
 * <description should be written here>
 *
 * @package      DBD\Base
 * @copyright    Copyright © Real Time Engineering, LLP - All Rights Reserved
 * @license      Proprietary and confidential
 * Unauthorized copying or using of this file, via any medium is strictly prohibited.
 * Content can not be copied and/or distributed without the express permission of Real Time Engineering, LLP
 *
 * @author       Written by Nurlan Mukhanov <102@mp.kz>, Февраль 2018
 */

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