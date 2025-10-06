<?php

/**
 * Vvveb
 *
 * Copyright (C) 2022  Ziadin Givan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

 return [
 	'auth' => [
 		'mode' => 'token', //token = admin added token, http = basic auth using admin user and password
 	],
 	'session' => [
 		'driver' => 'request',//stateless, handle session externally if needed. Change to 'php' for session management if php session cookie can be sent with the request.
 	],
 	'email' => [
 		'driver' => 'mail',
 	],
 	'cache' => [
 		'driver'  => 'file',
 		'servers' => [
 		],
 		'options' => [
 		],
 	],
 	'key'     => 'NRtV7fInOn2RaX4piJ0aT1t8G2MkFClJ',
 	'cronkey' => 'SkLEcmLlETUC3dzoOEOU0r7jZwKi8SzO',
 ];
