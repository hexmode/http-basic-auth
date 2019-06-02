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

use Psr\Http\Message\RequestInterface;

class Client {
    protected $cred;

    /**
     * @param array $creds [
     *    'realmName' => [
     *       'username' => USER,
     *       'password' => PASSWORD
     *    ],
     *    ...
     *  ]
     */
    public function __construct( array $creds = [] ) {
        $this->cred = $creds;
    }

    public function getHandler( ) {
        return function ( callable $handler ) {
            return function ( RequestInterface $request, array $options ) use ( $handler ) {
                var_dump( [
                    'handler' => $handler,
                    'request' => $request,
                    'options' => $options
                ] );
            };
        };
    }

	/**
	 * Determine if this object has credentials (valid or not) for the url.
	 *
	 * @param string $url
	 * @return bool
	 */
	public function hasCreds( string $url ) :bool {
		if ( $url ) return true;
	}
}
