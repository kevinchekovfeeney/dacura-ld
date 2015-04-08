
:- use_module(schemaRules). 
:- use_module(library(semweb/rdf_db)).
:- use_module(library(statistics)). 

% set stack size. 
:- set_prolog_stack(global, limit(400 000 000 000)).
:- set_prolog_stack(trail,  limit(80 000 000 000)).
:- set_prolog_stack(local,  limit(8 000 000 000)).

:- demoDB('data/SeshatGlobalHistoryDatabank0.1.00.ttl').

:- populateDB(1000), rdf_statistics(triples(Count)), write('Count: '), write(Count), nl.

:- write('1000 populations'), nl, time(checkDB(_)).

:- populateDB(10000), rdf_statistics(triples(Count)), write('Count: '), write(Count), nl.

:- write('10000 populations'), nl, time(checkDB(_)).

:- populateDB(10000), rdf_statistics(triples(Count)), write('Count: '), write(Count), nl.

:- write('10000 populations'), nl, time(checkDB(_)).

:- populateDB(10000), rdf_statistics(triples(Count)), write('Count: '), write(Count), nl.

:- write('10000 populations'), nl, time(checkDB(_)).

:- populateDB(10000), rdf_statistics(triples(Count)), write('Count: '), write(Count), nl.

:- write('100000 populations'), nl, time(checkDB(_)).

:- populateDB(100000), rdf_statistics(triples(Count)), write('Count: '), write(Count), nl.

:- write('100000 populations'), nl, time(checkDB(_)).

:- populateDB(50000), rdf_statistics(triples(Count)), write('Count: '), write(Count), nl.

:- write('50000 populations'), nl, time(checkDB(_)).

:- halt.
