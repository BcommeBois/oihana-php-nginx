<?php

namespace oihana\nginx\helpers ;

/**
 * Splits an nginx configuration into its instructions, one per entry, in the order they are written.
 *
 * An instruction ends with `;`, opens a block with `{`, or closes one with `}`, and each entry
 * keeps that character: `root /var/www/x;`, `location / {`, `}`. Comments are dropped and every
 * run of blanks becomes one space, so two files that nginx reads the same way give the same list:
 * a brace on the line of its `location` or on the next one, an indentation of four spaces or five,
 * `x ;` or `x;`.
 *
 * Quotes are honoured the way nginx honours them: a `#`, a `;` or a brace inside `'…'` or `"…"`
 * belongs to the value, and so does a character escaped with `\`. A regular expression with a
 * `{2,4}` quantifier has to be quoted in nginx for that very reason.
 *
 * Meant for comparing two configurations instruction by instruction — a file in place and the
 * one a template would write, for instance — where the instructions are the question and the
 * layout is noise. It reads; it does not check that the configuration is valid.
 *
 * Scope: three things nginx's own reader does differently, which this one does not follow. For
 * nginx, a `#` inside a word (`a#b`) belongs to the word, `${name}` outside quotes is a variable,
 * and `\;` outside quotes is an escaped character; here they start a comment, open a block and
 * end an instruction. Quoting such a value makes both readers agree.
 *
 * @param string $text An nginx configuration, a whole file or a part of one.
 *
 * @return list<string> The instructions. Empty when the text holds only blanks and comments.
 *
 * @example
 * ```php
 * use function oihana\nginx\helpers\nginxStatements;
 *
 * nginxStatements( "location /\n{\n    root   /var/www/x ; # site\n}\n" ) ;
 * // [ 'location / {' , 'root /var/www/x;' , '}' ]
 *
 * nginxStatements( "location ~ \"^/a{2,4}$\" { return 404; }" ) ;
 * // [ 'location ~ "^/a{2,4}$" {' , 'return 404;' , '}' ]
 * ```
 *
 * @package oihana\nginx\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   0.1.0
 */
function nginxStatements( string $text ) :array
{
    $statements = [] ;
    $current    = '' ;
    $quote      = null ;
    $comment    = false ;
    $length     = strlen( $text ) ;

    $close = static function( string $piece ) use ( &$statements ) :void
    {
        $piece = trim( (string) preg_replace( '/\s+/' , ' ' , $piece ) ) ;

        if ( $piece !== '' )
        {
            $statements[] = $piece ;
        }
    } ;

    for ( $index = 0 ; $index < $length ; $index++ )
    {
        $char = $text[ $index ] ;

        // A comment runs to the end of its line. For nginx it can only start where a word does,
        // after a blank or a `;` or a brace, so the blank between the two words is already there.

        if ( $comment )
        {
            if ( $char === "\n" )
            {
                $comment = false ;
            }

            continue ;
        }

        if ( $quote !== null )
        {
            $current .= $char ;

            if ( $char === '\\' && $index + 1 < $length )
            {
                $current .= $text[ ++$index ] ;
            }
            elseif ( $char === $quote )
            {
                $quote = null ;
            }

            continue ;
        }

        if ( $char === '"' || $char === "'" )
        {
            $quote    = $char ;
            $current .= $char ;

            continue ;
        }

        if ( $char === '#' )
        {
            $comment = true ;

            continue ;
        }

        if ( $char === ';' || $char === '{' || $char === '}' )
        {
            if ( $char === '}' )
            {
                $close( $current ) ;
                $close( '}' ) ;
            }
            else
            {
                $close( $current . $char ) ;
            }

            $current = '' ;

            continue ;
        }

        $current .= $char ;
    }

    $close( $current ) ;

    // `x ;` and `x;`, `location / {` and `location /{` are one instruction to nginx: written
    // `x;` and `location / {`.

    foreach ( $statements as $index => $statement )
    {
        $statement = (string) preg_replace( '/\s+;$/' , ';' , $statement ) ;
        $statement = (string) preg_replace( '/\s*\{$/' , ' {' , $statement ) ;

        $statements[ $index ] = $statement === ' {' ? '{' : $statement ;
    }

    return $statements ;
}
