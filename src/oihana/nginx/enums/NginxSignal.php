<?php

namespace oihana\nginx\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The enumeration of all signal arguments of the nginx command.
 * Example:
 * ```
 * nginx -s stop
 * ```
 */
class NginxSignal
{
    use ConstantsTrait ;

    /**
     * Shut down gracefully.
     */
    public const string QUIT = 'quit'   ;

    /**
     * Reload configuration, start the new worker process with a new configuration,
     * gracefully shut down old worker processes.
     */
    public const string RELOAD = 'reload' ;

    /**
     * Reopen log files.
     */
    public const string REOPEN  = 'reopen' ;

    /**
     * Shut down quickly.
     */
    public const string STOP = 'stop'   ;
}