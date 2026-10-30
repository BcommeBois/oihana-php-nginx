<?php

declare(strict_types=1);

namespace tests\oihana\nginx\traits ;

use Stringable;

use oihana\commands\options\CommandOptions;
use oihana\nginx\traits\NginxTrait;

/**
 * Uses NginxTrait with the five methods that reach the machine replaced by recorders.
 *
 * `system()`, `makeFile()` and `deleteFile()` run nothing: they note what they were asked, and
 * answer with the next code of `$codes` (success when it is empty). `info()` and `warning()` note
 * the messages. So the trait can be tested without nginx, without sudo, and without writing
 * anything outside the directories a test gives it.
 */
final class RecordingNginx
{
    use NginxTrait ;

    /**
     * What the trait asked for, in order: `[ 'system' , command , args , sudo ]`,
     * `[ 'makeFile' , path , content , sudo ]`, `[ 'deleteFile' , path , sudo ]`.
     * @var list<array>
     */
    public array $calls = [] ;

    /**
     * The codes the next calls answer with, first in first out.
     * @var list<int>
     */
    public array $codes = [] ;

    /**
     * The messages, as `[ 'info' , message ]` or `[ 'warning' , message ]`.
     * @var list<array{string, string}>
     */
    public array $messages = [] ;

    public function deleteFile
    (
       ?string                    $filePath           ,
        null|array|CommandOptions $options    = null  ,
        bool                      $verbose    = false ,
        bool                      $assertable = false ,
        bool                      $sudo       = false ,
        bool                      $dryRun     = false ,
    )
    :int
    {
        $this->calls[] = [ 'deleteFile' , $filePath , $sudo ] ;

        return $this->nextCode() ;
    }

    public function info( string|Stringable $message , array $context = [] ) :void
    {
        $this->messages[] = [ 'info' , (string) $message ] ;
    }

    public function makeFile
    (
        ?string                   $filePath ,
        ?string                   $content  = '' ,
        null|array|CommandOptions $options  = null  ,
        bool                      $verbose  = false ,
        bool                      $sudo     = false ,
        bool                      $dryRun   = false ,
    )
    :int
    {
        $this->calls[] = [ 'makeFile' , $filePath , $content , $sudo ] ;

        return $this->nextCode() ;
    }

    public function system
    (
        null|array|string         $command ,
        null|array|string         $args     = null  ,
        null|array|CommandOptions $options  = null  ,
        bool                      $silent   = false ,
        bool                      $verbose  = false ,
       ?string                    $previous = null  ,
       ?string                    $post     = null  ,
        bool                      $sudo     = false ,
        bool                      $dryRun   = false ,
    )
    :int
    {
        $this->calls[] = [ 'system' , $command , $args , $sudo ] ;

        return $this->nextCode() ;
    }

    public function warning( string|Stringable $message , array $context = [] ) :void
    {
        $this->messages[] = [ 'warning' , (string) $message ] ;
    }

    private function nextCode() :int
    {
        return array_shift( $this->codes ) ?? 0 ;
    }
}
