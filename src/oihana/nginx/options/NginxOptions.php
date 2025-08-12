<?php

namespace oihana\nginx\options;

use oihana\nginx\enums\NginxPath;
use ReflectionException;

use oihana\options\Options;
use oihana\enums\Char;
use oihana\nginx\enums\NginxSignal;

use function oihana\files\path\joinPaths;

/**
 * The options of the nginx command.
 */
class NginxOptions extends Options
{
    /**
     * The nginx website configuration.
     * @var string|null
     */
    public ?string $conf = null ;

    /**
     * Use an alternative configuration file instead of a default file
     * @var string|null
     */
    public ?string $config = null ;

    /**
     * The nginx directory where we can find the nginx configuration files.
     * @var string|null
     */
    public ?string $dir = null ;

    /**
     * Indicates if the nginx commands are enabled. Default true.
     * @var bool|null
     */
    public ?bool $enabled = true ;

    /**
     * Use an alternative error log file to store the log instead of a default file (1.19.5).
     * The special value stderr selects the standard error file.
     * @var string|null
     */
    public ?string $error = null ;

    /**
     * Set a global configuration directives, for example:
     * ```bash
     * nginx -g "pid /var/run/nginx.pid; worker_processes `sysctl -n hw.ncpu`;"
     * ```
     * @var string|null
     */
    public ?string $global = null ;

    /**
     * Print help for command-line parameters.
     * @var bool|null
     */
    public ?bool $help = null ;

    /**
     * The initial configuration definition to initialize the nginx website settings.
     * @var string|null
     */
    public ?string $init = null ;

    /**
     * The nginx directory where we can find the nginx logs.
     * @var string|null
     */
    public ?string $logs = null ;

    /**
     * Set nginx path prefix, i.e. a directory that will keep server files (default value is /usr/local/nginx).
     * @var bool|null
     */
    public ?bool $prefix = null ;

    /**
     * Suppress non-error messages during configuration testing.
     * @var bool|null
     */
    public ?bool $quiet = null ;

    /**
     * send a signal to the master process.
     * The argument signal can be one of:
     * - stop — shut down quickly
     * - quit — shut down gracefully
     * - reload — reload configuration, start the new worker process with a new configuration, gracefully shut down old worker processes.
     * - reopen — reopen log files
     * @var bool|null
     * @see NginxSignal
     */
    public ?bool $signal = null ;

    /**
     * Indicates if the command use sudo.
     * @var bool|null
     */
    public ?bool $sudo = true ;

    /**
     * Test the configuration file: nginx checks the configuration for correct syntax,
     * and then tries to open files referred in the configuration.
     * @var bool|null
     */
    public ?bool $test = null ;

    /**
     * Same as the -t option (test), but additionally dump configuration files to standard output (1.9.2).
     * @var bool|null
     */
    public ?bool $testFull = null ;

    /**
     * Print the nginx version.
     * @var bool|null
     */
    public ?bool $version = null ;

    /**
     * Print the nginx version, the compiler version, and the configure parameters.
     * @var bool|null
     */
    public ?bool $versionFull = null ;

    /**
     * Returns the path to the "sites-available" directory inside the nginx config directory.
     * For example, if $dir is `/etc/nginx`, this will return `/etc/nginx/sites-available`.
     * @return string
     */
    public function getSiteAvailableDirectory() :string
    {
        return joinPaths( $this->dir ?? Char::EMPTY , NginxPath::SITES_AVAILABLE ) ;
    }

    /**
     * Returns the path to the "sites-enabled" directory inside the nginx config directory.
     * This is where enabled (active) site configuration symlinks are stored.
     * For example, if $dir is `/etc/nginx`, this will return `/etc/nginx/sites-enabled`.
     * @return string
     */
    public function getSiteEnabledDirectory() :string
    {
        return joinPaths( $this->dir ?? Char::EMPTY , NginxPath::SITES_ENABLED ) ;
    }

    /**
     * Returns the string expression of the object.
     * @return string
     * @throws ReflectionException
     */
    public function __toString() : string
    {
        return $this->getOptions
        (
            clazz    : NginxOption::class ,
            prefix   : Char::HYPHEN ,
            excludes : // Extras options
            [
                NginxOption::CONF    ,
                NginxOption::DIR     ,
                NginxOption::ENABLED ,
                NginxOption::INIT    ,
                NginxOption::LOGS    ,
                NginxOption::SUDO    ,
            ] ,
        ) ;
    }
}