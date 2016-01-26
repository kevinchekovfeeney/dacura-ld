<?php

/*
 * Scraper Page parser
 *
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors:
 * Licence: GPL v2
 */


require_once 'autoloader.php';
use hafriedlander\Peg\Parser;

/**
 * This parser parses Seshat data
 * Needs to be run from the command line...
 * >php cli.php seshat.peg.inc seshat.parser.php
 * @author Chekov
 */
class seshatParsing extends Parser\Basic {

/*!* seshat

fact: (value:datedfact SPACE ";" SPACE)* (value:datedfact | value:undatedfact)

datedfact: (value:undatedfact SPACE ":" SPACE value:datevalue) | (value:datevalue SPACE ":" SPACE value:undatedfact) 

datevalue: value:daterange | value:disagreedate | value:singledate

daterange: value:singledate SPACE "-" SPACE value:singledate

singledate: value:uncertaindate | value:disagreedate | value:simpledate

simpledaterange: value:simpledate SPACE "-" SPACE value:simpledate

disagreedatefragment: value:simpledaterange | value:simpledate

disagreedate: "{" SPACE (value:disagreedatefragment SPACE ";" SPACE)+ value:disagreedatefragment SPACE "}"

uncertaindate:  "[" SPACE ((value:simpledaterange | value:simpledate) SPACE ";" SPACE)+ (value:simpledaterange | value:simpledate) SPACE "]" 

simpledate: value:year SPACE ( value:yearsuffix )?

undatedfact: value:uncertainlist | value:uncertainrange | value:disagreelist |  value:string 

uncertainlist: "[" SPACE  (value:string SPACE ";" SPACE)+ value:string SPACE "]"

uncertainrange: "[" SPACE  (value:nodashstring SPACE "-" SPACE)+ value:nodashstring SPACE "]"

disagreelistfragment: value:uncertainrange | value:string

disagreelist: "{" SPACE  (value:disagreelistfragment SPACE ";" SPACE)+ value:disagreelistfragment SPACE "}"

string: word (SPACE word)*

nodashstring: nodashword (SPACE nodashword)*

SPACE: " "*

nodashword: /[^-\]\[\{\};: ]+/

word: /[^\]\[\{\};: ]+/


year: /[0-9]{1,5}/

yearsuffix: "bce" | "ce" | "bc" | "BCE" | "CE" | "BC" | "Bce" | "Ce" | "Bc"

*/

}