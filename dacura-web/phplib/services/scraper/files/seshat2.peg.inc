<?php

require_once '/autoloader.php';
use hafriedlander\Peg\Parser;

/**
 * This parser parses Seshat data
 * NOTE: The disagreefactstatement definition does not work.
 * @author Odhran Gavin
 */
class seshat2Parsing extends Parser\Basic {

/*!* seshat

fact: (value:factfragment SPACE ";" SPACE)* value:factfragment 

factfragment: value:datedfact | value:reversedatefact | value:undatedfact

reversedatefact: value:datevalue SPACE ":" SPACE value:undatedfact

datedfact: value:undatedfact SPACE ":" SPACE value:datevalue 

datevalue: value:daterange | value:disagreedate | value:singledate

daterange: value:singledate SPACE "-" SPACE value:singledate

singledate: value:uncertaindate | value:disagreedate | value:simpledate

simpledaterange: value:simpledate SPACE "-" SPACE value:simpledate

disagreedatefragment: value:simpledaterange | value:simpledate

disagreedate: "{" SPACE (value:disagreedatefragment SPACE ";" SPACE)+ value:disagreedatefragment SPACE "}"

uncertaindate:  "[" SPACE (value:simpledate SPACE ";" SPACE)+ value:simpledate SPACE "]"

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


year: /[0-9]{/

yearsuffix: "bce" | "ce" | "bc" | "BCE" | "CE" | "BC" | "Bce" | "Ce" | "Bc"

*/

}