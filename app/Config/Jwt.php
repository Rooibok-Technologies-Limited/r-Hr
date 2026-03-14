<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Jwt extends BaseConfig
{
    public string $secret    = '';  // loaded from system_setting('jwt_secret')
    public string $algorithm = 'HS256';
    public int    $ttl       = 86400;  // 24 hours
}
