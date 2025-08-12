<?php

namespace oihana\nginx\enums;

use oihana\reflections\traits\ConstantsTrait;

class RedirectDirection
{
    use ConstantsTrait ;

    public const string INBOUND  = 'inbound' ;
    public const string OUTBOUND = 'outbound' ;
}