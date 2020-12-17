<?php
/**
 * YellowERP
 *
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2020 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types = 1);

namespace DBD;

use DBD\Common\DBDException as Exception;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

final class YellowERP extends OData
{
	protected $httpServicesUrl = null;
	/** @var int $maxRetries количество попыток HTTP запросов в случае, если неудачи */
	protected $maxRetries = 3;
	/** @var bool $reuseSessions флаг использования cookie-based сессий в 1С */
	protected $reuseSessions = false;
	/** @var string $servicesPath обращение к самописным сервисам в конфигурации по адресу Общие->HTTP-сервисы */
	protected $servicesPath = null;
	/** @var string $sessionFile место хранения файла с cookie. TODO: сделать возможность хранения в кэше, если он присутствует */
	protected      $sessionFile  = null;
	protected      $timeOutLimit = 30;
	private static $ibsession    = null;
	private static $retry        = 0;
	private static $sessionExist = false;

	/**
	 * Здесь мы должны открыть соединение в случае с базой, если у нас не стоит ондеманд.
	 * Но с curl все не так просто. У нас вообще не имеет смысла проверять лишний раз соединение и
	 * вызывать curl_init, потому что мы его вызовем тогда, когда нам надо, иначе мы просто так будем
	 * обращаться к ODATA сервису без какой-либо цели. Поэтому просто напросто возвращаем самих себя.
	 *
	 * @return $this
	 */
	public function connect(): DBD {

		return $this;
	}

	/**
	 * Выполнение запроса к сервису для получения данных как в случае с базой данных.
	 * Если мы обращаемся к сервисному ресурсу, то выполняем запрос и получаем JSON тело.
	 * В ином случае мы обращаемся к родителю и обрабатываем запросы через него
	 *
	 * @return array|OData|mixed|resource|string|null
	 * @throws Exception
	 * @throws InvalidArgumentException
	 * @throws ReflectionException
	 */
	public function execute() {
		if($this->servicesPath) {
			$this->result = null;

			$this->tryGetFromCache();

			// If not found in cache, then let's get via HTTP request
			if($this->result === null) {

				$this->setupRequest($this->httpServicesUrl . $this->servicesPath);
				$this->_connect();

				// Will return NULL in case of failure
				$this->result = json_decode($this->body, true);

				$this->storeResultToCache();
			}

			$this->servicesPath = null;

			return $this->result;
		}
		else {
			return parent::execute(func_get_args());
		}
	}

	/**
	 * 1С позволят заканчивать сессию. Следовательно мы тоже реализуем такую возможность
	 *
	 * @return $this
	 */
	public function finish() {
		if($this->resourceLink && self::$ibsession) {
			curl_setopt($this->resourceLink, CURLOPT_URL, $this->Config->getHost());
			curl_setopt($this->resourceLink, CURLOPT_COOKIE, "ibsession=" . self::$ibsession);
			curl_setopt($this->resourceLink, CURLOPT_HTTPHEADER, [ 'IBSession: finish' ]);
			curl_exec($this->resourceLink);
		}
		// Возможно мы не раз еще будем пользоваться файлом, поэтому чтобы его не создавать постоянно, просто запишем в него пустоту
		file_put_contents($this->sessionFile, null);
		self::$ibsession = null;

		return $this;
	}

	/**
	 * Возможность установки использования сессий для быстрого соединения
	 *
	 * @param bool   $reuseSessions
	 * @param int    $maxRetries
	 * @param string $sessionFile
	 *
	 * @return $this
	 */
	public function reuseSessions($reuseSessions = false, $maxRetries = 3, $sessionFile = null) {
		if(!isset($sessionFile)) {
			$sessionFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'YellowERP.ses';
		}
		$this->reuseSessions = $reuseSessions;
		$this->maxRetries = $maxRetries;
		$this->sessionFile = $sessionFile;

		return $this;
	}

	/*--------------------- reuseSessions="use" --------------------*/

	/**
	 * Кастомное решение для обращения к различным самописным сервисам, находящимся в Общие->HTTP-сервисы
	 * ```
	 * $odata->setHttpServicesUrl("https://ye.domain.com/hs/mySuperService/");
	 * $sth = $odata->service("report/Goods?name={$name}");
	 * $sth->cache("ODATA:Goods:{$name}", "3m");
	 * $sth->execute();
	 * $json = $sth->fetchrowset();
	 * ```
	 *
	 * @param $url
	 *
	 * @return $this
	 */
	public function service($url) {
		$this->dropVars();

		$this->servicesPath = $url;

		// We have to fake, otherwise DBD will issue exception on cache for non select query
		$this->query = "SELECT * FROM $url";

		return $this;
	}

	/**
	 * Установка сервисного URL для дальнейшего использования. Вызывается после создания инстанса
	 * ```
	 * $odata->setHttpServicesUrl("https://ye.domain.com/hs/mySuperService/");
	 * ```
	 *
	 * @param string $httpServicesUrl
	 *
	 * @return $this
	 */
	public function setHttpServicesUrl(string $httpServicesUrl) {
		$this->httpServicesUrl = $httpServicesUrl;

		return $this;
	}

	/**
	 * Переписанный метод, так как в 1С своеобразный подход к авторизаци и по своему созданный принцип сессионности для быстроты работы интерфейса
	 *
	 * @return $this|DBD|OData|YellowERP
	 * @throws Exception
	 * @inheritDoc
	 */
	protected function _connect(): void {

		if(!is_resource($this->resourceLink)) {
			$this->setupRequest($this->Config->getHost());
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
				$url = curl_getinfo($this->resourceLink, CURLINFO_EFFECTIVE_URL);
				throw new Exception("Too many connection retries. Can't initiate session. URL: '{$url}'");
			}

			if(self::$ibsession) {
				curl_setopt($this->resourceLink, CURLOPT_COOKIE, "ibsession=" . self::$ibsession);
			}
			else {
				curl_setopt($this->resourceLink, CURLOPT_COOKIE, null);
				curl_setopt($this->resourceLink, CURLOPT_HTTPHEADER, [ 'IBSession: start' ]);
			}
		}

		curl_setopt($this->resourceLink, CURLOPT_TIMEOUT, $this->timeOutLimit);

		$response = curl_exec($this->resourceLink);
		$header_size = curl_getinfo($this->resourceLink, CURLINFO_HEADER_SIZE);

		$this->header = substr($response, 0, $header_size);
		$this->body = substr($response, $header_size);
		$this->httpCode = curl_getinfo($this->resourceLink, CURLINFO_HTTP_CODE);
		//$url            = curl_getinfo($this->dbh, CURLINFO_EFFECTIVE_URL);

		if($this->reuseSessions && !self::$sessionExist) {

			//if ($this->httpcode  == 0) { throw new Exception("No connection to: '$url', {$this->body}"); }
			if($this->httpCode == 406) {
				throw new Exception("406 Not Acceptable. YellowERP can't initiate new session");
			}
			if($this->httpCode == 400 || $this->httpCode == 404 || $this->httpCode == 0) {
				file_put_contents($this->sessionFile, null);
				self::$ibsession = null;

				$this->_connect();
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

		if($this->httpCode >= 200 && $this->httpCode < 300) {
			if($this->reuseSessions && !self::$sessionExist) {
				curl_setopt($this->resourceLink, CURLOPT_COOKIE, "ibsession=" . self::$ibsession);
				@setcookie('IBSession', self::$ibsession, time() + 60 * 60 * 24, '/');
				self::$sessionExist = true;
			}
		}
		else {
			if(!$this->reuseSessions && $this->httpCode == 0 && self::$retry < $this->maxRetries) {
				self::$retry++;

				$this->_connect();
			}
			else {
				$this->parseError();
			}
		}
		self::$retry = 0;

	}
}