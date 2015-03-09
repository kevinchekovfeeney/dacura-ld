
:- use_module(schemaRules). 
:- use_module(library(semweb/rdf_db)).
:- use_module(library(statistics)). 

:- demoDB('SeshatGlobalHistoryDatabank0.1.00.ttl').

:- write('Initial'), nl, time(checkDB(_)).

:- populateDB(10), rdf_statistics(triples(Count)), write('Count: '), write(Count), nl.

:- write('10 populations'), nl, time(checkDB(_)).

:- populateDB(100), rdf_statistics(triples(Count)), write('Count: '), write(Count), nl.

:- write('100 populations'), nl, time(checkDB(_)).

:- populateDB(1000), rdf_statistics(triples(Count)), write('Count: '), write(Count), nl.

:- write('1000 populations'), nl, time(checkDB(_)).

:- populateDB(10000), rdf_statistics(triples(Count)), write('Count: '), write(Count), nl.

:- write('10000 populations'), nl, time(checkDB(_)).

:- populateDB(100000), rdf_statistics(triples(Count)), write('Count: '), write(Count), nl.

:- write('100000 populations'), nl, time(checkDB(_)).

:- populateDB(1000000), rdf_statistics(triples(Count)), write('Count: '), write(Count), nl.

:- write('1000000 populations'), nl, time(checkDB(_)).

:- populateDB(1000000), rdf_statistics(triples(Count)), write('Count: '), write(Count), nl.

:- write('1000000 populations'), nl, time(checkDB(_)).

:- halt.
