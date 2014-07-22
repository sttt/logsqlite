<?php defined('SYSPATH') or die('No direct script access.');

Kohana::$log->attach(new Log_SQLite3(APPPATH.'logs'));