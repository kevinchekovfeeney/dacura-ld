<?php

require_once '/autoloader.php';
use hafriedlander\Peg\Parser;

/**
 * This parser parses Seshat data
 * NOTE: The disagreefactstatement definition does not work.
 * @author Odhran Gavin
 */
class seshatParsing extends Parser\Basic {

/*!* seshat

factcontainer: value:factstatement | value:disagreefactstatement | value:uncertainfactstatement

uncertainfactstatement: "[" ((value:fact ";" SPACE)* value:fact) "]"

disagreefactstatement: "{" ((value:fact ";" SPACE)+ value:fact) "}"

factstatement: ((value:fact ";" SPACE)* value:fact)

fact: value:basefact | value:uncertainbasefact

uncertainbasefact: "[" (value:keyvalue | value:singleton) "]"

basefact: value:keyvalue | value:singleton

keyvalue: value:statement SPACE ":" SPACE value:statement

singleton: value:statement

statement: (value:disagreement|value:uncertainty|value:range|value:string SPACE)+

disagreement: "{" (value:statement ";" SPACE)+ value:statement "}"

uncertainty: "[" value:string SPACE (";" SPACE value:string)* "]"

range: value:string SPACE "-" SPACE value:string

string: word (SPACE word)*

SPACE: " "*

word: (wordpart ((","|".") wordpart)+)|wordpart

wordpart: /[a-zA-Z0-9()<>]+/

*/

}