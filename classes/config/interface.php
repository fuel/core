<?php

namespace Fuel\Core;

interface Config_Interface
{
	function load($overwrite = false);
	function group();
}
