
:- module(schemaRules,[demoDB/0, demoDB/1, demoDB/2, demoDB/3, 
		       populateDB/1, 
		       checkDB/1, 
		       loadAndCheckDB/3, 
		       tests/0]).

:- use_module(library(semweb/rdf_db)).
:- use_module(library(semweb/turtle)). 

%% setup namespaces related to rdf, rdfs and owl 
%%%
%% ALL of these are predefined!!!! 
%:- rdf_register_ns(rdf, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#').
%:- rdf_register_ns(rdfs, 'http://www.w3.org/2000/01/rdf-schema#').
%:- rdf_register_ns(owl, 'http://www.w3.org/2002/07/owl#').

%%% Utils 

count(_,[], 0).
count(A,[B|L],C) :- count(A,L,K), (A=B -> C is K+1 ; C=K).

%%%%%%%%%%%%%%%%%%%%
% Schema constraints

%classes

class(X) :- rdf(X, rdf:type, owl:'Class', schema). 

uniqueClass(Y) :- class(Y), bagof(X, class(X), L), count(Y,L,1).

notUniqueClass(Y) :- class(Y), bagof(X, class(X), L), \+ count(Y,L,1).

duplicateClasses(L) :- setof(Y,notUniqueClass(Y), L).

% subclasses 

subClass(X,Y) :- rdf(X, rdfs:subClassOf, Y, schema).

subClassOf(X,Y) :- rdf(X, rdfs:subClassOf, Y, schema).
subClassOf(X,Z) :- rdf(X, rdfs:subClassOf, Y, schema), subClassOf(Y,Z).

subClassOfClass(X,Y) :- subClassOf(X,Y), class(Y).

notSubClassOfClass(X,Y) :- subClassOf(X,Y), \+ class(Y).

orphanSubClasses(L) :- setof(notSubClassOfClass(X,Y), notSubClassOfClass(X,Y),L).

% subclass cycles
classCycleHelp(C,S,[]) :- get_assoc(C,S,true), !.
classCycleHelp(C,S,[K|P]) :- class(C), subClass(K, C), put_assoc(C, S, true, S2), classCycleHelp(K,S2,P).

classCycle(C,P) :- empty_assoc(S), classCycleHelp(C,S,P). 

classCycles(L) :- setof(classCycle(CC,P), classCycle(CC,P), L).

% properties.
:- rdf_meta property(r).
property(rdfs:label).
property(rdfs:comment).
property(P) :- rdf(P, rdf:type, owl:'ObjectProperty', schema).
property(P) :- rdf(P, rdf:type, owl:'DataProperty', schema).

uniqueProperty(P) :- property(P), bagof(P2, property(P2), L), count(P,L,1).

notUniqueProperty(P) :- property(P), bagof(P2, property(P2), L), \+ count(P,L,1).

duplicateProperties(L) :- setof(P,notUniqueProperty(P), L).

% subProperties.

subProperty(X,Y) :- rdf(X, rdfs:subPropertyOf, Y, schema).

subPropertyOf(X,Y) :- rdf(X, rdfs:subPropertyOf, Y, schema).
subPropertyOf(X,Z) :- rdf(X, rdfs:subPropertyOf, Y, schema), subPropertyOf(Y, Z). 

subPropertyOfProperty(X,Y) :- subPropertyOf(X,Y), property(Y).

notSubPropertyOfProperty(X,Y) :- subPropertyOf(X,Y), \+ property(Y).

orphanSubProperties(L) :- setof(notSubPropertyOfProperty(X,Y),notSubPropertyOfProperty(X,Y),L).

% subProperty cycles 

propertyCycleHelp(P,S,[]) :- get_assoc(P,S,true), !.
propertyCycleHelp(P,S,[Q|T]) :- property(P), subProperty(Q, P), put_assoc(P, S, true, S2), propertyCycleHelp(Q,S2,T).

propertyCycle(P,PC) :- empty_assoc(S), propertyCycleHelp(P,S,PC). 

propertyCycles(L) :- setof(propertyCycle(P,PC), propertyCycle(P,PC),L).

% data types.  / list all primitive types we will be using here.

:- rdf_meta type(r).
type(X) :- class(X). 
type(X) :- rdf_match_label(prefix, 'http://www.w3.org/2001/XMLSchema#', X). 

% range / domain

range(P,R) :- rdf(P, rdfs:range, R, schema).

domain(P,D) :- rdf(P, rdfs:domain, D, schema). 

validRange(P,R) :- range(P,R), type(R).
validDomain(P,D) :- domain(P,D), type(D).

uniqueValidRange(P,R) :- range(P,R), findall(R2, validRange(P,R2), L), length(L,1).

uniqueValidDomain(P,D) :- domain(P,D), findall(D2, validRange(P,D2), L), length(L,1).

notUniqueValidRange(P,R) :- range(P,R), findall(R2, validRange(P,R2), L), \+ length(L,1).

notUniqueValidDomain(P,D) :- domain(P,D), findall(D2, validDomain(P,D2), L), \+ length(L,1).

% does this do too much? 
invalidRange(L) :- setof(range(Y, R), notUniqueValidRange(Y,R), L).

invalidDomain(L) :- setof(domain(Y, D), notUniqueValidDomain(Y,D), L).


%%%%%%%%%%%%%%%%%%%%%%%
%% Instance constraints

instanceClass(X, Y) :- rdf(X, rdf:type, Y, instance).

instance(X) :- instanceClass(X,_).

instanceHasClass(X,C) :- instanceClass(X, C), class(C).

orphanInstance(X,C) :- instanceClass(X,C), \+ class(C).

noOrphans :- \+ orphanInstance(_,_).

orphanInstances(L) :- setof(orphanInstance(X,C),orphanInstance(X,C), L).

instanceProperty(X,P) :- instance(X), rdf(X, P, _), \+ P=rdf:type.

instanceHasPropertyClass(X,P) :- instanceProperty(X,P), property(P).

noInstancePropertyClass(X,P) :- instanceProperty(X,P), \+ property(P).

orphanProperties(L) :- setof(noInstancePropertyClass(X,Y), noInstancePropertyClass(X,Y), L). 

%%%%%%%%%%%%%%%%%%%%%%%%%
%%% Blank nodes

blankNode(X) :- rdf(X,_,_), rdf_is_bnode(X).
blankNode(Y) :- rdf(_,Y,_), rdf_is_bnode(Y).
blankNode(Z) :- rdf(_,_,Z), rdf_is_bnode(Z).

blankNodes(L) :- setof(X, blankNode(X), L). 

%%%%%%%%%%%%%%%%%%%%%%%%%%
%%% Labels 

classHasLabel(X,Y) :- class(X), rdf(X, rdfs:label, Y, schema).
classHasNoLabel(X) :- class(X), \+ rdf(X, rdfs:label, _, schema).

classHasOneLabel(X) :- classHasLabel(X,Label), bagof(label(Y,Label2), classHasLabel(Y,Label2), L), count(label(X,Label),L,1).

duplicateLabelClasses(X) :- classHasLabel(X,Label), bagof(label(Y,Label2), classHasLabel(Y,Label2), L), \+ count(label(X,Label),L,1).


%%%%%%%%%%%%%%%%%%%%%%%%%%
%%% Instance Data Generator

:- use_module(library(assoc)). 

classRoot(X) :- class(X), \+ subClassOf(X,_).

classPropertyClass(C,P,Z) :- domain(P,C), range(P,Z), class(C), property(P), class(Z).
classPropertyClass(C,P,Z) :- subClassOf(C, K), classPropertyClass(K, P, Z). 

% this is bullshit...  prefixes should work under unification 
%classPropertyLiteral(C,P) :- domain(P,C), range(P,rdf:'PlainLiteral'), class(C), property(P). 
classPropertyLiteral(C,P) :- domain(P,C), range(P,'http://www.w3.org/1999/02/22-rdf-syntax-ns#PlainLiteral'), class(C), property(P). 

generateLinks(_,X,[rdf(X, 'http://www.w3.org/2000/01/rdf-schema#label', 'Rubbish')],_).
generateLinks(C,X,[Triple],_) :- 
    classPropertyLiteral(C,P), 
    Triple = rdf(X,P,literal(lang(en, 'some arbitrary literal'))).
generateLinks(C,X,[ rdf(X, P, Y) | O],S) :- 
    classPropertyClass(C,P,K), 
    (get_assoc(K, S, Y) ->  O=[]  % remove cycles by reusing instances of encountered classes.
     ; atom_concat(K, '-instance', A), 
       gensym(A, Y), 
       put_assoc(K, S, Y, S2),
       bagof(R, generateLinks(K, Y, R, S2), L), 
       flatten(L, O)
    ).
generateLinks(C,X,[ rdf(X, P, Y) | O],S) :- 
    classPropertyClass(C,P,Super),
    subClassOf(K, Super), 
    (get_assoc(K, S, Y) ->  O=[]  % remove cycles by reusing instances of encountered classes.
     ; atom_concat(K, '-instance', A), 
       gensym(A, Y), 
       put_assoc(K, S, Y, S2),
       bagof(R, generateLinks(K, Y, R, S2), L), 
       flatten(L, O)
    ).

generateClosed(C,[rdf(X,'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',C)|O],S) :- 
    class(C),
    atom_concat(C, '-instance', A),
    gensym(A, X), 
    put_assoc(C, S, X, S2),
    bagof(R, generateLinks(C, X, R, S2), L), 
    flatten(L,O).
generateClosed(C,[rdf(X,'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',Sub)|O],S) :- 
    subClassOf(Sub,C),
    atom_concat(Sub, '-instance', A),
    gensym(A, X), 
    put_assoc(Sub, S, X, S2),
    bagof(R, generateLinks(Sub, X, R, S2), L), 
    flatten(L,O).

generate(L) :- empty_assoc(S), classRoot(C), generateClosed(C, L, S). 

generateN(0,[]).
generateN(N,L) :- N > 0, M is N-1, generate(L1), generateN(M,L2),append(L1,L2,L).

:- use_module(library(apply)).
addToDB(rdf(X,Y,Z)) :- rdf_assert(X,Y,Z,instance). 
:- rdf_meta populateDB. 

% N specifies number of times to decend the class hierarchy rather than 
% the number of classes or triples.  This is convenient as consistency 
% is a global property which can't easily be maintained without total traversal. 
% If you have a big schema, make N small. 
populateDB(N) :- generateN(N,L), maplist(addToDB, L), !.


%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%%% Carefully Corrupting the DB.

corrupt_class :- 
    class(X), rdf_assert(X, rdf:type, owl:'Class', schema). % create duplicates

corrupt_instance :- 
    gensym('rubbish', X),
    class(Y),
    rdf_assert(X, rdf:type, Y), 
    property(P), 
    gensym('rubbish_target', Z),
    rdf_assert(X, P, Z).

% Corrupt the database.  This should excercise the reasoner. 
corruptDB(0).
corruptDB(N) :- 
    M is N-1, 
    corrupt_class,
    corrupt_instance, 
    corruptDB(M).

%%%%%%%%%%%%%%%%%%%%%%%%%
%%% Testing

%% Schema and Instance

demoDB :-  
    rdf_retractall(_, _, _, _), 
    rdf_load('testData/plants.rdf', [graph(instance)]), 
    rdf_load('testData/plant-onto.rdf', [graph(schema)]).

demoDB(Schema) :- 
    rdf_retractall(_, _, _, _), 
    rdf_load(Schema, [graph(schema)]). 

demoDB(Schema,Instance) :- 
    rdf_retractall(_, _, _, _), 
    rdf_load(Schema, [graph(schema)]), 
    rdf_load(Instance, [graph(instance)]).

demoDB(Schema,Instance,Options) :- 
    rdf_retractall(_, _, _, _), 
    rdf_load(Schema, [graph(schema)|Options]),     
    rdf_load(Instance, [graph(instance)|Options]). 


%%%%%%%%%%%%%%%%%%%%%%%%%%
%%% DB Checker

test(classCycles).
test(propertyCycles). 
test(duplicateClasses).
test(duplicateProperties).
test(orphanSubClasses).
test(orphanSubProperties). 
test(orphanInstances).
test(orphanProperties). 
test(blankNodes).
test(invalidRange). 
test(invalidDomain). 

% this package prefixing is a ridiculous hack to deal with metapredicate handling.
testMessage(schemaRules:classCycles, 'Cycles in class hierarchy: ') :- !.
testMessage(schemaRules:propertyCycles, 'Cycles in property hierarchy: ') :- !. 
testMessage(schemaRules:duplicateClasses, 'Duplicate classes: ') :- !.
testMessage(schemaRules:duplicateProperties, 'Duplicate properties: ') :- !.
testMessage(schemaRules:orphanSubClasses, 'Orphaned subclasses: ') :- !.
testMessage(schemaRules:orphanSubProperties, 'Orphaned subproperties: ') :- !.
testMessage(schemaRules:orphanInstances, 'Orphaned instances: ') :- !.
testMessage(schemaRules:orphanProperties, 'Missing class for properties: ') :- !. 
testMessage(schemaRules:blankNodes, 'Blank Nodes found: ') :- !.
testMessage(schemaRules:invalidRange, 'Property with non-unique or invalid range found: ') :- !. 
testMessage(schemaRules:invalidDomain, 'Property with non-unique or invalid domain found: ') :- !. 
testMessage(_,'Unknown test').

:- meta_predicate validate(1,?).
validate(Test, Stream) :- 
    call(Test, C),
    nl(Stream),	
    testMessage(Test, Message),
    write(Test),nl,
    write(Stream, Message),
    nl(Stream),	 
    write(Stream, C), 
    nl(Stream), 
    fail.

% validate will always fail, we want to iterate over choice points in T
% to accumulate all I/O side effects.
runValidate(Stream) :-
    test(Test), 
    validate(Test,Stream). 
runValidate(_).

:- meta_predicate runTest(1,?).
runTest(Test) :- 
    atom_concat('testData/', Test, TestBegin), 
    atom_concat(TestBegin, '.ttl', TestFile), 
    demoDB(TestFile, 'testData/instance.ttl'), 
    call(Test, _), !, fail.
runTest(Test) :-
    atom_concat('Failed test ', Test, Fail),
    write(Fail), nl, fail.

% runTest will always fail, we want to iterate over choice points in T
% to accumulate all I/O side effects.
tests :- 
    test(Test),
    runTest(Test).
tests.

stringStream(Handle, Stream) :-
    new_memory_file(Handle),
    open_memory_file(Handle, write, Stream).

streamString(Handle, String) :-
    open_memory_file(Handle, read, R, [free_on_close(true)]),
    read_string(R, _, String).

loadAndCheckDB(Schema,Instance,Output) :-
    % clear out the triple store.
    % load rdf
    rdf_load(Schema, [graph(schema)]),
    rdf_load(Instance, [graph(instance)]),
    checkDB(Output).

checkDB(Output) :-
    %% Setup output string stream 
    stringStream(Handle,Stream),
    write(Stream, '***** Starting check of DB *****'),
    nl(Stream),	 
    runValidate(Stream),
    nl(Stream),
    write(Stream, 'Finished checking DB!'),
    close(Stream), 
    streamString(Handle, Output).
