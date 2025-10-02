<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    // behind NPM/Reverse proxy, trust the proxy and forwarded headers
    protected $proxies = '*';

    // include proto header so Laravel knows the original scheme is https
    protected $headers = Request::HEADER_X_FORWARDED_FOR
                       | Request::HEADER_X_FORWARDED_HOST
                       | Request::HEADER_X_FORWARDED_PORT
                       | Request::HEADER_X_FORWARDED_PROTO
                       | Request::HEADER_X_FORWARDED_AWS_ELB;
}
