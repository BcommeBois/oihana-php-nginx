<?php

declare(strict_types=1);

namespace tests\oihana\nginx\traits ;

use oihana\commands\enums\BrewCommands;
use oihana\commands\enums\SystemCTLCommands;
use oihana\files\exceptions\DirectoryException;
use oihana\nginx\options\NginxOptions;
use oihana\nginx\traits\NginxTrait;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use Random\RandomException;
use function oihana\core\env\isMac;

/**
 * NginxTrait, through {@see RecordingNginx}: nothing is run, the commands are read.
 */
#[CoversTrait(NginxTrait::class)]
final class NginxTraitTest extends TestCase
{
    private RecordingNginx $nginx ;

    private string $root = '' ;

    /**
     * @throws RandomException
     */
    protected function setUp() :void
    {
        $this->root = sys_get_temp_dir() . '/oihana-nginx-trait-' . bin2hex( random_bytes( 6 ) ) ;

        mkdir( $this->root . '/sites-available' , 0755 , true ) ;
        mkdir( $this->root . '/sites-enabled'   , 0755 , true ) ;

        $this->nginx = new RecordingNginx()->initializeNginxOptions( [ 'nginx' => [ 'dir' => $this->root ] ] ) ;
    }

    protected function tearDown() :void
    {
        exec( sprintf( 'rm -rf %s' , escapeshellarg( $this->root ) ) ) ;
    }

    // --------------------------------------------------------------------------- initializeNginxOptions

    public function testTheOptionsAreReadUnderTheNginxKey() :void
    {
        $nginx = new RecordingNginx()->initializeNginxOptions( [ 'nginx' => [ 'dir' => '/etc/nginx' , 'sudo' => false ] , 'other' => [] ] ) ;

        $this->assertInstanceOf( NginxOptions::class , $nginx->nginxOptions ) ;
        $this->assertSame( '/etc/nginx' , $nginx->nginxOptions->dir ) ;
        $this->assertFalse( $nginx->nginxOptions->sudo ) ;
    }

    public function testTheOptionsAreReadFlatToo() :void
    {
        $nginx = new RecordingNginx()->initializeNginxOptions( [ 'dir' => '/etc/nginx' ] ) ;

        $this->assertSame( '/etc/nginx' , $nginx->nginxOptions->dir ) ;
    }

    public function testWithoutOptionsTheDefaultsApply() :void
    {
        $nginx = new RecordingNginx()->initializeNginxOptions() ;

        $this->assertNull( $nginx->nginxOptions->dir ) ;
        $this->assertTrue( $nginx->nginxOptions->sudo ) ;
    }

    // --------------------------------------------------------------------------- nginxConfigCreate

    /**
     * @throws DirectoryException
     */
    public function testCreateWritesTheFileThenLinksIt() :void
    {
        $status = $this->nginx->nginxConfigCreate( 'example.com' , 'server {}' ) ;

        $this->assertSame( 0 , $status ) ;
        $this->assertSame
        (
            [
                [ 'makeFile' , $this->available( 'example.com' ) , 'server {}' , true ] ,
                [ 'system' , [ 'ln -s' , $this->available( 'example.com' ) , $this->enabled( 'example.com' ) ] , null , true ] ,
            ] ,
            $this->nginx->calls
        ) ;
        $this->assertSame( [] , $this->nginx->messages , 'Not verbose: nothing to say.' ) ;
    }

    /**
     * @throws DirectoryException
     */
    public function testCreateLeavesALinkAlreadyThere() :void
    {
        touch( $this->available( 'example.com' ) ) ;
        symlink( $this->available( 'example.com' ) , $this->enabled( 'example.com' ) ) ;

        $this->nginx->nginxConfigCreate( 'example.com' , 'server {}' ) ;

        $this->assertSame( [ [ 'makeFile' , $this->available( 'example.com' ) , 'server {}' , true ] ] , $this->nginx->calls ) ;
    }

    /**
     * @throws DirectoryException
     */
    public function testCreateSaysWhatItDid() :void
    {
        $this->nginx->nginxConfigCreate( 'example.com' , 'server {}' , verbose : true ) ;

        $this->assertSame
        (
            [
                [ 'info' , sprintf( '[✓] The nginx config file "%s" is created.' , $this->available( 'example.com' ) ) ] ,
                [ 'info' , sprintf( '[✓] The nginx config symlink "%s" is created.' , $this->enabled( 'example.com' ) ) ] ,
            ] ,
            $this->nginx->messages
        ) ;
    }

    /**
     * @throws DirectoryException
     */
    public function testCreateSaysWhatFailed() :void
    {
        $this->nginx->codes = [ 1 , 2 ] ;

        $status = $this->nginx->nginxConfigCreate( 'example.com' , 'server {}' , verbose : true ) ;

        $this->assertSame( 2 , $status , 'The link\'s code is the one returned.' ) ;
        $this->assertSame
        (
            [
                [ 'warning' , sprintf( '[!] Failed to create the nginx config file : %s' , $this->available( 'example.com' ) ) ] ,
                [ 'warning' , sprintf( '[!] Failed to create the nginx config symlink : %s' , $this->enabled( 'example.com' ) ) ] ,
            ] ,
            $this->nginx->messages
        ) ;
    }

    /**
     * @throws DirectoryException
     */
    public function testCreateUsesTheOptionsGiven() :void
    {
        $other = $this->root . '/other' ;

        mkdir( $other . '/sites-available' , 0755 , true ) ;
        mkdir( $other . '/sites-enabled'   , 0755 , true ) ;

        $this->nginx->nginxConfigCreate( 'example.com' , 'x' , [ 'dir' => $other ] ) ;

        $this->assertSame( $other . '/sites-available/example.com' , $this->nginx->calls[ 0 ][ 1 ] ) ;
    }

    public function testCreateRefusesAMissingSitesAvailable() :void
    {
        rmdir( $this->root . '/sites-available' ) ;

        $this->expectException( DirectoryException::class ) ;

        $this->nginx->nginxConfigCreate( 'example.com' , 'server {}' ) ;
    }

    public function testCreateRefusesAMissingSitesEnabled() :void
    {
        rmdir( $this->root . '/sites-enabled' ) ;

        try
        {
            $this->nginx->nginxConfigCreate( 'example.com' , 'server {}' ) ;
        }
        catch ( DirectoryException )
        {
            $this->assertSame( [] , $this->nginx->calls , 'Nothing is written before both directories are known.' ) ;

            return ;
        }

        $this->fail( 'A missing sites-enabled directory was accepted.' ) ;
    }

    // --------------------------------------------------------------------------- nginxConfigExists

    /**
     * @throws DirectoryException
     */
    public function testExistsIsFalseWithoutTheFile() :void
    {
        $this->assertFalse( $this->nginx->nginxConfigExists( 'example.com' ) ) ;
    }

    /**
     * @throws DirectoryException
     */
    public function testExistsIsTrueWithTheFile() :void
    {
        touch( $this->available( 'example.com' ) ) ;

        $this->assertTrue( $this->nginx->nginxConfigExists( 'example.com' ) ) ;
        $this->assertFalse( $this->nginx->nginxConfigExists( 'example.com' , checkSymlink : true ) , 'The link was asked for, and is not there.' ) ;
    }

    /**
     * @throws DirectoryException
     */
    public function testExistsChecksTheLinkWhenAsked() :void
    {
        touch( $this->available( 'example.com' ) ) ;
        symlink( $this->available( 'example.com' ) , $this->enabled( 'example.com' ) ) ;

        $this->assertTrue( $this->nginx->nginxConfigExists( 'example.com' , checkSymlink : true ) ) ;
    }

    /**
     * @throws DirectoryException
     */
    public function testALinkAloneIsNoConfiguration() :void
    {
        symlink( $this->available( 'example.com' ) , $this->enabled( 'example.com' ) ) ;

        $this->assertFalse( $this->nginx->nginxConfigExists( 'example.com' , checkSymlink : true ) ) ;
    }

    // --------------------------------------------------------------------------- nginxConfigDelete

    /**
     * @throws DirectoryException
     */
    public function testDeleteRemovesTheFileThenTheLink() :void
    {
        touch( $this->available( 'example.com' ) ) ;
        symlink( $this->available( 'example.com' ) , $this->enabled( 'example.com' ) ) ;

        $status = $this->nginx->nginxConfigDelete( 'example.com' ) ;

        $this->assertSame( 0 , $status ) ;
        $this->assertSame
        (
            [
                [ 'deleteFile' , $this->available( 'example.com' ) , true ] ,
                [ 'deleteFile' , $this->enabled( 'example.com' ) , true ] ,
            ] ,
            $this->nginx->calls
        ) ;
    }

    /**
     * @throws DirectoryException
     */
    public function testDeleteLeavesAMissingLinkAlone() :void
    {
        touch( $this->available( 'example.com' ) ) ;

        $this->nginx->nginxConfigDelete( 'example.com' ) ;

        $this->assertSame( [ [ 'deleteFile' , $this->available( 'example.com' ) , true ] ] , $this->nginx->calls ) ;
    }

    /**
     * @throws DirectoryException
     */
    public function testDeleteSaysWhatItDid() :void
    {
        symlink( $this->available( 'example.com' ) , $this->enabled( 'example.com' ) ) ;

        $this->nginx->nginxConfigDelete( 'example.com' , verbose : true ) ;

        $this->assertSame
        (
            [
                [ 'info' , sprintf( '[✓] The nginx config file "%s" is removed.' , $this->available( 'example.com' ) ) ] ,
                [ 'info' , sprintf( '[✓] The nginx config symlink "%s" is removed.' , $this->enabled( 'example.com' ) ) ] ,
            ] ,
            $this->nginx->messages
        ) ;
    }

    /**
     * @throws DirectoryException
     */
    public function testDeleteSaysWhatFailed() :void
    {
        symlink( $this->available( 'example.com' ) , $this->enabled( 'example.com' ) ) ;

        $this->nginx->codes = [ 1 , 3 ] ;

        $status = $this->nginx->nginxConfigDelete( 'example.com' , verbose : true ) ;

        $this->assertSame( 3 , $status , 'The link\'s code is the one returned.' ) ;
        $this->assertSame
        (
            [
                [ 'warning' , sprintf( '[!] Failed to remove the nginx config file : %s' , $this->available( 'example.com' ) ) ] ,
                [ 'warning' , sprintf( '[!] Failed to remove the nginx config symlink : %s' , $this->enabled( 'example.com' ) ) ] ,
            ] ,
            $this->nginx->messages
        ) ;
    }

    public function testDeleteRefusesAMissingDirectory() :void
    {
        rmdir( $this->root . '/sites-enabled' ) ;

        $this->expectException( DirectoryException::class ) ;

        $this->nginx->nginxConfigDelete( 'example.com' ) ;
    }

    // --------------------------------------------------------------------------- nginxTest

    public function testTestRunsNginxDashTWithSudo() :void
    {
        $status = $this->nginx->nginxTest() ;

        $this->assertSame( 0 , $status ) ;
        $this->assertSame( [ [ 'system' , 'nginx' , '-t' , true ] ] , $this->nginx->calls ) ;
        $this->assertSame( [] , $this->nginx->messages ) ;
    }

    public function testTestAddsTheOptionsGiven() :void
    {
        $this->nginx->nginxTest( [ 'config' => '/etc/nginx/nginx.conf' , 'quiet' => true ] ) ;

        $this->assertSame( [ [ 'system' , 'nginx' , '-c "/etc/nginx/nginx.conf" -q -t' , true ] ] , $this->nginx->calls ) ;
    }

    /**
     * nginx itself needs root to read its certificates, whatever the options say.
     */
    public function testTestRunsWithSudoEvenWhenTheOptionsSayNot() :void
    {
        $this->nginx->nginxTest( [ 'sudo' => false ] ) ;

        $this->assertTrue( $this->nginx->calls[ 0 ][ 3 ] ) ;
    }

    public function testTestSaysWhatItDoes() :void
    {
        $this->nginx->codes = [ 1 ] ;

        $this->assertSame( 1 , $this->nginx->nginxTest( verbose : true ) ) ;
        $this->assertSame( [ [ 'info' , '[?] Test the Nginx configuration ' ] ] , $this->nginx->messages ) ;
    }

    // --------------------------------------------------------------------------- the service

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function serviceProvider() :array
    {
        return
        [
            'reload'  => [ 'nginxReload'  , BrewCommands::BREW_SERVICES_RESTART , SystemCTLCommands::SYSTEM_CTL_RELOAD  ] ,
            'restart' => [ 'nginxRestart' , BrewCommands::BREW_SERVICES_RESTART , SystemCTLCommands::SYSTEM_CTL_RESTART ] ,
            'start'   => [ 'nginxStart'   , BrewCommands::BREW_SERVICES_START   , SystemCTLCommands::SYSTEM_CTL_START   ] ,
            'stop'    => [ 'nginxStop'    , BrewCommands::BREW_SERVICES_STOP    , SystemCTLCommands::SYSTEM_CTL_STOP    ] ,
        ] ;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function serviceMethodProvider() :array
    {
        return array_map( static fn( array $case ) :array => [ $case[ 0 ] ] , self::serviceProvider() ) ;
    }

    /**
     * brew on macOS, systemctl elsewhere. Only the branch of the machine running the tests is
     * exercised.
     */
    #[DataProvider('serviceProvider')]
    public function testTheServiceCommandOfThisSystem( string $method , string $brew , string $systemctl ) :void
    {
        $this->nginx->codes = [ 5 ] ;

        $status = $this->nginx->$method() ;

        $this->assertSame( 5 , $status , 'The command\'s code is the one returned.' ) ;
        $this->assertSame( [ [ 'system' , [ isMac() ? $brew : $systemctl , 'nginx' ] , null , true ] ] , $this->nginx->calls ) ;
    }

    /**
     * Unlike `nginx -t`, the service commands follow the `sudo` option.
     */
    #[DataProvider('serviceMethodProvider')]
    public function testTheServiceCommandFollowsTheSudoOption( string $method ) :void
    {
        $this->nginx->$method( [ 'sudo' => false ] ) ;

        $this->assertFalse( $this->nginx->calls[ 0 ][ 3 ] ) ;
    }

    #[DataProvider('serviceMethodProvider')]
    public function testTheServiceCommandFollowsTheInitialOptions( string $method ) :void
    {
        $nginx = new RecordingNginx()->initializeNginxOptions( [ 'nginx' => [ 'sudo' => false ] ] ) ;

        $nginx->$method() ;

        $this->assertFalse( $nginx->calls[ 0 ][ 3 ] ) ;
    }

    // --------------------------------------------------------------------------- helpers

    private function available( string $name ) :string
    {
        return $this->root . '/sites-available/' . $name ;
    }

    private function enabled( string $name ) :string
    {
        return $this->root . '/sites-enabled/' . $name ;
    }
}
