#Microsoft SQL Server Driver

* Version: 1.0.0

## Description

This is a driver for connectiong Microsoft SQL Server (include Azure SQL Database).

## parameter of "db.conf"

+	type :
	'sqlsrv'

+	hotname :
	SQL Server or SQL Database hostname with prefix 'tcp:'

+	port:
	usually '1433'

+	database:
	database name

+	username:
	login user name, usually username@host

+	password:
	login password

+	charset:
	keep blank for UTF-8

+	table_prefix:
	strings of table prefix


### Example "db.conf" ###
	<?php
	return array(
		'default' => array(
        	        'type'     => 'sqlsrv',
			'connection'  => array(
	                        'hostname'   => 'tcp:testdb.database.windows.net',
        	                'port'             => 1433,
                	        'database'    => 'database name',
				'username'   => 'username@testdb',
				'password'   => 'Password',
			),
                	'charset'        => '',
	                'table_prefix' => 'pref_',
		),
	);

