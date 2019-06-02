<?php

/**
 * Copyright (C) 2019  NicheWork, LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace Hexmode\HTTPBasicAuth;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;

class Client {
	/** @param array $cred */
	protected $cred;
	/** @param bool $rety */
	protected $retry;
	/** @param string $realm */
	protected $realm;

	/**
	 * @param array $creds [
	 *    'hostname' => [
	 *       'username' => USER,
	 *       'password' => PASSWORD
	 *    ],
	 *    'realm' => [
	 *       'username' => USER,
	 *       'password' => PASSWORD
	 *    ],
	 *    ...
	 *  ]
	 */
	public function __construct( array $creds = [] ) {
		$this->cred = $creds;
	}

	public function getHandleStack( CurlHandler $handler ) {
		$stack = HandlerStack::create( $handler );
		$calls = [
			'RequestMapper', 'ResponseMapper', 'RetryDecider', 
			'BodyModifier', 'Logger', 'Redirector', 'Tapper',
			'ErrorHander', 'HistoryRecorder', 'CookieJar'
		];
		foreach ( $calls as $call ) {
			$caller = [ $this, "get$call" ];
			if ( is_callable( $caller ) ) {
				$stack->push( call_user_func( $caller ) );
			}
		}
		return $stack;
	}

	protected function parseAuthHeader( Response $response ) {
		$authHeader = $response->getHeader( "WWW-Authenticate" );
		if ( $authHeader ) {
			list ( $authType, $realmInfo ) = explode(
				" ", $authHeader[0], 2
			);
			if ( strtolower( $authType ) === "basic" ) {
				list ( $realmWord, $realm ) = array_map(
					function ( $str ) {
						return trim( $str, '\'" ' );
					},
					explode( "=", $realmInfo, 2 )
				);
				if ( strtolower( $realmWord ) === "realm" ) {
					$this->realm = $realm;
				}
				$this->retry = true;
			}
		}
	}

	public function getResponseMapper() {
		return Middleware::mapResponse(
			function ( Response $response ) {
				if ( $response->getStatusCode() === 401 ) {
					$this->parseAuthHeader( $response );
				}
				return $response;
			}
		);
	}

	public function getRetryDecider() {
		return Middleware::retry(
			function (
				$retries
			) {
				if ( $retries >= 1 ) {
					return false;
				}

				return $this->retry === true;
			}
		);
	}

	/**
	 * Determine if this object has credentials (valid or not) for the url.
	 *
	 * @param string $url
	 * @return bool
	 */
	public function hasCreds( string $url ) :bool {
		$key = $this->getKey( $url );
		return isset( $this->cred[$key] );
	}

	public function getKey( string $url ) :string {
		$parsed = parse_url( $url );
		return $parsed['host'] ?? null;
	}

	public function getUsername( string $url ): string {
		$key = $this->getKey( $url );
		return $this->cred[$key]['login'];
	}

	public function getPassword( string $url ): string {
		$key = $this->getKey( $url );
		return $this->cred[$key]['password'];
	}
}
