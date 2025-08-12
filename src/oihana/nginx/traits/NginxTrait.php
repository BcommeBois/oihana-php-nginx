<?php

namespace oihana\nginx\traits;

use oihana\commands\enums\BrewCommands;
use oihana\commands\enums\ExitCode;
use oihana\commands\enums\SystemCTLCommands;
use oihana\commands\traits\CommandTrait;
use oihana\commands\traits\ConsoleLoggerTrait;
use oihana\commands\traits\FileTrait;

use oihana\files\exceptions\DirectoryException;
use oihana\files\exceptions\FileException;

use oihana\nginx\options\NginxOption;
use oihana\nginx\options\NginxOptions;

use function oihana\files\assertDirectory;
use function oihana\files\isMac;
use function oihana\files\path\joinPaths;

/**
 * Provides functionality to supports the nginx command.
 */
trait NginxTrait
{
    use ConsoleLoggerTrait,
        CommandTrait ,
        FileTrait ;

    /**
     * The 'nginx' constant.
     */
    public const string NGINX = 'nginx' ;

    /**
     * Holds the default options for the nginx command.
     * @var NginxOptions|null
     */
    public ?NginxOptions $nginxOptions  = null ;

    /**
     * Initializes the internal core install options using an array of configuration values.
     *
     * This method supports structured initialization using keys like `core.install`, or directly
     * from a flat array of parameters.
     *
     * Example input:
     * ```php
     * [
     *   'core' =>
     *   [
     *     'install' =>
     *     [
     *       'url'            => 'https://example.com' ,
     *       'title'          => 'My Site' ,
     *       'admin_user'     => 'admin' ,
     *       'admin_password' => 'secret' ,
     *       'admin_email'    => 'admin@example.com'
     *     ]
     *   ]
     * ]
     * ```
     *
     * @param array $init Optional associative array for initializing install parameters.
     * @return static Returns the current instance for method chaining.
     */
    public function initializeNginxOptions( array $init = [] ) :static
    {
        $this->nginxOptions = new NginxOptions( $init[ static::NGINX ] ?? $init ) ;
        return $this ;
    }

    /**
     * Creates a new Nginx config for a specific domain (and subdomain - optional).
     * @param string      $fileName The name of the config file.
     * @param string|null $content The content of the nginx config file.
     * @param array|NginxOptions|null $options Optional install options. Can be a flat array, a NginxOptions instance, or null to use internal defaults.
     * @param bool $verbose If true, outputs command execution feedback.
     * @param bool $silent If true, suppresses command output entirely.
     * @return int
     *
     * @throws DirectoryException Throws if the nginx folder not exist.
     */
    public function nginxConfigCreate
    (
        string                  $fileName ,
        string|null             $content   = null ,
        array|NginxOptions|null $options   = null ,
        bool                    $verbose   = false ,
        bool                    $silent    = false ,
    ):int
    {
        $paths = $this->resolveNginxFilePaths( $fileName, $options ) ;

        $fileSitesAvailable = $paths[ static::FILE_SITES_AVAILABLE ] ;
        $fileSitesEnabled   = $paths[ static::FILE_SITES_ENABLED   ] ;

        $status = $this->makeFile
        (
            filePath : $fileSitesAvailable ,
            content  : $content ,
            verbose  : $verbose ,
            sudo     : true
        ) ;

        if( $verbose )
        {
            if( $status == ExitCode::SUCCESS )
            {
                $this->info( sprintf( '[✓] The nginx config file "%s" is created.' , $fileSitesAvailable ) ) ;
            }
            else
            {
                $this->warning( sprintf( '[!] Failed to create the nginx config file : %s' , $fileSitesAvailable ) ) ;
            }
        }

        if( !is_link( $fileSitesEnabled ) )
        {
            $status = $this->system
            (
                command : [ 'ln -s' , $fileSitesAvailable , $fileSitesEnabled ] ,
                silent  : $silent ,
                verbose : $verbose ,
                sudo    : true
            ) ;
        }

        if( $verbose )
        {
            if( $status == ExitCode::SUCCESS )
            {
                $this->info( sprintf( '[✓] The nginx config symlink "%s" is created.' , $fileSitesEnabled ) ) ;
            }
            else
            {
                $this->warning( sprintf( '[!] Failed to create the nginx config symlink : %s' , $fileSitesEnabled ) ) ;
            }
        }

        return $status  ;
    }

    /**
     * Checks if the Nginx configuration file exists (and optionally its symlink).
     *
     * @param string                  $fileName The name of the nginx config file (ex: example.com.www).
     * @param array|NginxOptions|null $options  Optional Nginx options to resolve paths.
     * @param bool $checkSymlink      Whether to also check if the symlink in sites-enabled exists.
     *
     * @return bool True if the file (and optionally the symlink) exists.
     *
     * @throws DirectoryException
     */
    public function nginxConfigExists( string $fileName, array|NginxOptions|null $options = null, bool $checkSymlink = false): bool
    {
        $paths = $this->resolveNginxFilePaths($fileName, $options);

        $fileSitesAvailable = $paths[ static::FILE_SITES_AVAILABLE ] ;
        $fileSitesEnabled   = $paths[ static::FILE_SITES_ENABLED   ] ;

        if ( !is_file( $fileSitesAvailable ) )
        {
            return false;
        }

        if ( $checkSymlink && !is_link($fileSitesEnabled ) )
        {
            return false;
        }

        return true;
    }

    /**
     * Delete an Nginx config for a specific domain (and subdomain - optional).
     * @param string $fileName The name of the config file.
     * @param array|NginxOptions|null $options Optional install options. Can be a flat array, a NginxOptions instance, or null to use internal defaults.
     * @param bool $verbose If true, outputs command execution feedback.
     * @return int
     *
     * @throws DirectoryException Throws if the nginx folder not exist.
     * @throws FileException
     */
    public function nginxConfigDelete
    (
        string                  $fileName ,
        array|NginxOptions|null $options   = null ,
        bool                    $verbose   = false
    ):int
    {
        $paths = $this->resolveNginxFilePaths( $fileName, $options ) ;

        $fileSitesAvailable = $paths[ static::FILE_SITES_AVAILABLE ] ;
        $fileSitesEnabled   = $paths[ static::FILE_SITES_ENABLED   ] ;

        $status = $this->deleteFile
        (
            filePath : $fileSitesAvailable ,
            verbose  : $verbose ,
            sudo     : true
        ) ;

        if( $verbose )
        {
            if( $status == ExitCode::SUCCESS )
            {
                $this->info( sprintf( '[✓] The nginx config file "%s" is removed.' , $fileSitesAvailable ) ) ;
            }
            else
            {
                $this->warning( sprintf( '[!] Failed to remove the nginx config file : %s' , $fileSitesAvailable ) ) ;
            }
        }

        if( is_link( $fileSitesEnabled ) )
        {
            $status = $this->deleteFile
            (
                filePath : $fileSitesEnabled ,
                verbose  : $verbose ,
                sudo     : true
            ) ;
        }

        if( $verbose )
        {
            if( $status == ExitCode::SUCCESS )
            {
                $this->info( sprintf( '[✓] The nginx config symlink "%s" is removed.' , $fileSitesEnabled ) ) ;
            }
            else
            {
                $this->warning( sprintf( '[!] Failed to remove the nginx config symlink : %s' , $fileSitesEnabled ) ) ;
            }
        }

        return $status  ;
    }

    /**
     * Executes the `nginx -t` command.
     *
     * Test the configuration file: nginx checks the configuration for correct syntax,
     * and then tries to open files referred in the configuration.
     *
     * @param array|NginxOptions|null $options Optional install options. Can be a flat array, a NginxOptions instance, or null to use internal defaults.
     * @param bool                    $verbose If true, outputs command execution feedback.
     * @param bool                    $silent  If true, suppresses command output entirely.
     *
     * @return int Returns the exit status of the system command (0 for success).
     */
    public function nginxTest
    (
        array|NginxOptions|null $options = null ,
        bool                    $verbose = false ,
        bool                    $silent  = false ,
    )
    :int
    {
        $options = NginxOptions::resolve( $this->nginxOptions , [ NginxOption::TEST => true ] , $options ) ;

        if( $verbose )
        {
            $this->info( '[?] Test the Nginx configuration ' ) ;
        }

        return $this->system
        (
            command : static::NGINX ,
            args    : (string) $options ,
            silent  : $silent  ,
            verbose : $verbose ,
            sudo    : true     ,
        ) ;
    }

    /**
     * Reload the nginx configuration, start the new worker process with a new configuration,
     * gracefully shut down old worker processes.
     * @param array|NginxOptions|null $options
     * @param bool $verbose
     * @param bool $silent
     * @return int
     */
    public function nginxReload
    (
        array|NginxOptions|null $options = null ,
        bool                    $verbose = false ,
        bool                    $silent  = false ,
    )
    :int
    {
        $options = NginxOptions::resolve( $this->nginxOptions , $options ) ;
        return $this->system
        (
            command :
            [
                isMac() ? BrewCommands::BREW_SERVICES_RESTART : SystemCTLCommands::SYSTEM_CTL_RELOAD ,
                static::NGINX
            ],
            silent  : $silent ,
            verbose : $verbose ,
            sudo    : $options->sudo
        ) ;
    }

    /**
     * Restart the nginx configuration, start the new worker process with a new configuration,
     * gracefully shut down old worker processes.
     * @param array|NginxOptions|null $options
     * @param bool $verbose
     * @param bool $silent
     * @return int
     */
    public function nginxRestart
    (
        array|NginxOptions|null $options = null ,
        bool                    $verbose = false ,
        bool                    $silent  = false ,
    )
    :int
    {
        $options = NginxOptions::resolve( $this->nginxOptions , $options ) ;
        return $this->system
        (
            command :
            [
                isMac() ? BrewCommands::BREW_SERVICES_RESTART : SystemCTLCommands::SYSTEM_CTL_RESTART ,
                static::NGINX
            ] ,
            silent  : $silent ,
            verbose : $verbose ,
            sudo    : $options->sudo
        ) ;
    }

    /**
     * Start the nginx configuration, start the new worker process with a new configuration.
     * @param array|NginxOptions|null $options
     * @param bool $verbose
     * @param bool $silent
     * @return int
     */
    public function nginxStart
    (
        array|NginxOptions|null $options = null ,
        bool                    $verbose = false ,
        bool                    $silent  = false ,
    )
    :int
    {
        $options = NginxOptions::resolve( $this->nginxOptions , $options ) ;
        return $this->system
        (
            command :
            [
                isMac() ? BrewCommands::BREW_SERVICES_START : SystemCTLCommands::SYSTEM_CTL_START ,
                static::NGINX
            ],
            silent  : $silent ,
            verbose : $verbose ,
            sudo    : $options->sudo
        ) ;
    }

    /**
     * Stops the nginx configuration, stops the worker process with a new configuration.
     * @param array|NginxOptions|null $options
     * @param bool $verbose
     * @param bool $silent
     * @return int
     */
    public function nginxStop
    (
        array|NginxOptions|null $options = null ,
        bool                    $verbose = false ,
        bool                    $silent  = false ,
    )
    :int
    {
        $options = NginxOptions::resolve( $this->nginxOptions , $options ) ;
        return $this->system
        (
            command :
            [
                isMac() ? BrewCommands::BREW_SERVICES_STOP : SystemCTLCommands::SYSTEM_CTL_STOP ,
                static::NGINX
            ] ,
            silent  : $silent ,
            verbose : $verbose ,
            sudo    : $options->sudo
        ) ;
    }

    /**
     * Helper to resolve the Nginx file paths (sites-available and sites-enabled) with a specific config file name.
     *
     * @param string                  $fileName The name of the config file.
     * @param array|NginxOptions|null $options  The options definition to initialize the file paths.
     *
     * @return array{
     *    dirSitesAvailable  :string ,
     *    dirSitesEnabled   :string ,
     *    fileSitesAvailable :string ,
     *    fileSitesEnabled  :string ,
     *    nginxOptions      :NginxOptions
     * }
     *
     * @throws DirectoryException
     */
    protected function resolveNginxFilePaths( string $fileName, array|NginxOptions|null $options = null ): array
    {
        $nginxOptions = NginxOptions::resolve( $this->nginxOptions, options: $options ) ;

        $dirSitesAvailable = $nginxOptions->getSiteAvailableDirectory();

        assertDirectory( $dirSitesAvailable );

        $dirSitesEnabled = $nginxOptions->getSiteEnabledDirectory();

        assertDirectory( $dirSitesEnabled ) ;

        $fileSitesAvailable = joinPaths( $dirSitesAvailable , $fileName ) ;
        $fileSitesEnabled   = joinPaths( $dirSitesEnabled   , $fileName ) ;

        return
        [
            static::DIR_SITES_AVAILABLE  => $dirSitesAvailable  ,
            static::DIR_SITES_ENABLED    => $dirSitesEnabled    ,
            static::FILE_SITES_AVAILABLE => $fileSitesAvailable ,
            static::FILE_SITES_ENABLED   => $fileSitesEnabled   ,
            static::NGINX_OPTIONS        => $nginxOptions       ,
        ];
    }
    
    protected const string DIR_SITES_AVAILABLE  = 'dirSitesAvailable'  ;
    protected const string DIR_SITES_ENABLED    = 'dirSitesEnabled'    ;
    protected const string FILE_SITES_AVAILABLE = 'fileSitesAvailable' ;
    protected const string FILE_SITES_ENABLED   = 'fileSitesEnabled'   ;
    protected const string NGINX_OPTIONS        = 'nginxOptions'   ;
}