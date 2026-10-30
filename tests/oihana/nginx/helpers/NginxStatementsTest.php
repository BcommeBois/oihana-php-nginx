<?php

declare(strict_types=1);

namespace tests\oihana\nginx\helpers ;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function oihana\nginx\helpers\nginxStatements;

#[CoversFunction('oihana\nginx\helpers\nginxStatements')]
final class NginxStatementsTest extends TestCase
{
    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function textProvider() :array
    {
        return
        [
            'one instruction' =>
            [
                "root /var/www/x;\n" ,
                [ 'root /var/www/x;' ] ,
            ] ,
            'a block, brace on the next line' =>
            [
                "location /\n{\n    try_files \$uri =404;\n}\n" ,
                [ 'location / {' , 'try_files $uri =404;' , '}' ] ,
            ] ,
            'a block, brace on the same line' =>
            [
                "location / {\n    try_files \$uri =404;\n}\n" ,
                [ 'location / {' , 'try_files $uri =404;' , '}' ] ,
            ] ,
            'a brace with no blank before it' =>
            [
                "location /{ deny all; }" ,
                [ 'location / {' , 'deny all;' , '}' ] ,
            ] ,
            'a brace alone' =>
            [
                "{ deny all; }" ,
                [ '{' , 'deny all;' , '}' ] ,
            ] ,
            'indentation and inner blanks folded' =>
            [
                "     ssl_certificate      /etc/x.pem ;\n" ,
                [ 'ssl_certificate /etc/x.pem;' ] ,
            ] ,
            'tabs folded too' =>
            [
                "\tlisten\t443\tssl;\r\n" ,
                [ 'listen 443 ssl;' ] ,
            ] ,
            'an instruction over two lines' =>
            [
                "add_header X\n    \"y\";\n" ,
                [ 'add_header X "y";' ] ,
            ] ,
            'comments dropped, whole line and end of line' =>
            [
                "# a comment\nroot /x; # another\n#location / { deny all; }\n" ,
                [ 'root /x;' ] ,
            ] ,
            'a comment ending the text, with no line feed' =>
            [
                "root /x; # the end" ,
                [ 'root /x;' ] ,
            ] ,
            'a comment between two words is a blank' =>
            [
                "listen # the port\n443;\n" ,
                [ 'listen 443;' ] ,
            ] ,
            'a # inside double quotes is the value' =>
            [
                "add_header X \"a#b\";\n" ,
                [ 'add_header X "a#b";' ] ,
            ] ,
            'a # inside single quotes is the value' =>
            [
                "add_header X 'a#b';\n" ,
                [ "add_header X 'a#b';" ] ,
            ] ,
            'a brace and a semicolon inside quotes are the value' =>
            [
                "location ~ \"^/a{2,4};\" { return 404; }\n" ,
                [ 'location ~ "^/a{2,4};" {' , 'return 404;' , '}' ] ,
            ] ,
            'an escaped quote does not close the value' =>
            [
                "add_header X \"a\\\";b\";\n" ,
                [ 'add_header X "a\\";b";' ] ,
            ] ,
            'the other quote does not close the value' =>
            [
                "add_header X \"it's;\";\n" ,
                [ "add_header X \"it's;\";" ] ,
            ] ,
            'an unterminated quote keeps the rest' =>
            [
                "add_header X \"a; b\\" ,
                [ 'add_header X "a; b\\' ] ,
            ] ,
            'an if on one line, brace after the condition' =>
            [
                "if (\$host = 'x.example') {\n    return 301 https://www.x.example\$request_uri;\n}\n" ,
                [ "if (\$host = 'x.example') {" , 'return 301 https://www.x.example$request_uri;' , '}' ] ,
            ] ,
            'an instruction with no semicolon at the end' =>
            [
                "root /x" ,
                [ 'root /x' ] ,
            ] ,
            'nothing' =>
            [
                "\n   # only a comment\n" ,
                [] ,
            ] ,
            'empty' =>
            [
                '' ,
                [] ,
            ] ,
        ] ;
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('textProvider')]
    public function testTheInstructions( string $text , array $expected ) :void
    {
        $this->assertSame( $expected , nginxStatements( $text ) ) ;
    }

    /**
     * Two layouts that nginx reads the same way give the same list.
     */
    public function testTwoLayoutsOfOneVhostAreOneList() :void
    {
        $one = "server\n{\n    listen 443 ssl;\n    location ~ /\\.ht\n    {\n        deny all;\n    }\n}\n" ;
        $two = "server {\n     listen  443 ssl;\n     location ~ /\\.ht {\n         deny all;\n     }\n}\n" ;

        $this->assertSame( nginxStatements( $one ) , nginxStatements( $two ) ) ;
    }
}
