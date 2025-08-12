<?php

namespace oihana\nginx\options;

use oihana\options\Option;

/**
 * The enumeration of the nginx command options.
 */
class NginxOption extends Option
{
    // ---------- Default options

    public const string CONFIG       = 'config'      ;
    public const string ERROR        = 'error'       ;
    public const string GLOBAL       = 'global'      ;
    public const string HELP         = 'help'        ;
    public const string PREFIX       = 'prefix'      ;
    public const string QUIET        = 'quiet'       ;
    public const string SIGNAL       = 'signal'      ;
    public const string TEST         = 'test'        ;
    public const string TEST_FULL    = 'testFull'    ;
    public const string VERSION      = 'version'     ;
    public const string VERSION_FULL = 'versionFull' ;

    // ---------- Extras options

    public const string CONF    = 'conf'    ;
    public const string DIR     = 'dir'     ;
    public const string ENABLED = 'enabled' ;
    public const string INIT    = 'init'    ;
    public const string LOGS    = 'logs'    ;
    public const string SUDO    = 'sudo'    ;

    // ----------

    /**
     * Returns the command line option expression from a specific option.
     * @param string $option
     * @return string
     */
    public static function getCommandOption( string $option ):string
    {
        return match( $option )
        {
            NginxOption::CONFIG       => 'c'  ,
            NginxOption::ERROR        => 'e'  ,
            NginxOption::GLOBAL       => 'g'  ,
            NginxOption::HELP         => 'h'  ,
            NginxOption::PREFIX       => 'p'  ,
            NginxOption::QUIET        => 'q'  ,
            NginxOption::SIGNAL       => 's'  ,
            NginxOption::TEST         => 't'  ,
            NginxOption::TEST_FULL    => 'T' ,
            NginxOption::VERSION      => 'v'  ,
            NginxOption::VERSION_FULL => 'V'  ,
        };
    }
}