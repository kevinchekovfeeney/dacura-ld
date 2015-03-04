
:- module(schemaRules,[checkDB/3, demoDB/0, demoDB/1, demoDB/2, demoDB/3, populateDB/1]).

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

allUniqueClasses :- forall(class(X), uniqueClass(X)).

duplicateClasses(L) :- setof(Y,notUniqueClass(Y), L).

% subclasses 

subClass(X) :- rdf(X, rdfs:subClassOf, _, schema).

subClassOf(X,Y) :- rdf(X, rdfs:subClassOf, Y, schema).

subClassOfClass(X) :- subClassOf(X,Y), class(Y).

notSubClassOfClass(X) :- subClassOf(X,Y), \+ class(Y).

allSubClassesHaveClass :- forall(subClass(X), subClassOfClass(X)).

orphanSubClasses(L) :- setof(Y,notSubClassOfClass(Y),L).

% subclass cycles
classCycleHelp(C,S) :- get_assoc(C,S,true).
classCycleHelp(C,S) :- class(C), subClassOf(K, C), put_assoc(C, S, true, S2), classCycleHelp(K,S2).

classCycle(C) :- empty_assoc(S), classCycleHelp(C,S). 

noClassCycles :- class(C), forall( classCycle(C), false). 

% properties.

property(X) :- rdf(X, rdf:type, owl:'ObjectProperty', schema).

uniqueProperty(Y) :- property(Y), bagof(X, property(X), L), count(Y,L,1).

notUniqueProperty(Y) :- property(Y), bagof(X, property(X), L), \+ count(Y,L,1).

allUniqueProperties :- forall(property(X), uniqueProperty(X)).

duplicateProperties(L) :- setof(Y,notUniqueProperty(Y), L).

% subProperties.

subProperty(X) :- rdf(X, rdfs:subPropertyOf, _, schema).

subPropertyOf(X,Y) :- rdf(X, rdfs:subPropertyOf, Y, schema).

subPropertyOfProperty(X) :- subPropertyOf(X,Y), class(Y).

notSubPropertyOfProperty(X) :- subPropertyOf(X,Y), \+ class(Y).

allSubPropertyesHaveProperty :- forall(subProperty(X), subPropertyOfProperty(X,_)).

orphanSubPropertyes(L) :- setof(Y,notSubPropertyOfProperty(Y),L).

% subProperty cycles 

propertyCycleHelp(P,S) :- get_assoc(P,S,true).
propertyCycleHelp(P,S) :- property(P), subPropertyOf(Q, P), put_assoc(P, S, true, S2), propertyCycleHelp(Q,S2).

propertyCycle(P) :- empty_assoc(S), propertyCycleHelp(P,S). 

noPropertyCycles :- property(P), forall( propertyCycle(P), false). 

% range / domain

range(P,X) :- rdf(P, rdfs:range, X, schema).

domain(P,X) :- rdf(P, rdfs:domain, X, schema). 

uniqueRange(P,X) :- range(P,X), bagof(Y, class(Y), L), count(P,L,1).

notUniqueRange(P,X) :- range(P,X), bagof(Y, class(Y), L), \+ count(P,L,1).

allUniqueRange :- forall(range(P,X), uniqueRange(P,X)).

duplicateRange(L) :- setof(Y,notUniqueRange(Y,_), L).

%%%%%%%%%%%%%%%%%%%%%%%
%% Instance constraints

instanceClass(X, Y) :- rdf(X, rdf:type, Y, instance).

instance(X) :- instanceClass(X,_).

instanceHasClass(X) :- instanceClass(X, Y), class(Y).

orphanInstance(X) :- instanceClass(X,Y), \+ class(Y).

noOrphans :- forall(instance(X), instanceHasClass(X)).

orphanInstances(L) :- setof(Y,orphanInstance(Y), L).

instanceDomainExists(X) :- domain(X,P), property(P).
instanceRangeExists(X) :- range(X,P), property(P).

noInstanceDomain(X) :- domain(X,P), \+ property(P).
noInstanceRange(X) :- range(X,P), \+ property(P).

allDomainedInstances :- forall(domain(X,_), instanceDomainExists(X)).
allRangedInstances :- forall(range(X,_), instanceRangeExists(X)).

orphanDomains(L) :-setof(Y, notInstanceDomain(Y), L). 
orphanRanges(L) :-setof(Y, notInstanceRange(Y), L). 

instanceProperty(X,P) :- instance(X), rdf(X, P, _), \+ P=rdf:type.

instanceHasPropertyClass(X) :- instanceProperty(X,P), property(P).

noInstancePropertyClass(X) :- instanceProperty(X,P), \+ property(P).

allPropertiedInstances :- forall(instance(X), instanceHasPropertyClass(X)).

orphanProperties(L) :- setof(Y, noInstancePropertyClass(Y), L). 

%%%%%%%%%%%%%%%%%%%%%%%%%
%%% Blank nodes

blankNodes(X) :- rdf(X,_,_), rdf_is_bnode(X).
blankNodes(Y) :- rdf(_,Y,_), rdf_is_bnode(Y).
blankNodes(Z) :- rdf(_,_,Z), rdf_is_bnode(Z).

noBlankNodes :- forall( blankNodes(_), false).

%%%%%%%%%%%%%%%%%%%%%%%%%%
%%% Labels 

classHasLabel(X,Y) :- class(X), rdf(X, rdfs:label, Y, schema).
classHasNoLabel(X) :- class(X), \+ rdf(X, rdfs:label, _, schema).

classHasOneLabel(X) :- classHasLabel(X,Label), bagof(label(Y,Label2), classHasLabel(Y,Label2), L), count(label(X,Label),L,1).
allClassesHaveOneLabel(X) :-forall(class(X), classHasOneLabel(X)). 

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

generate(L) :- empty_assoc(S), classRoot(C), generateClosed(C, L, S). 

generateN(0,[]).
generateN(N,L) :- N > 0, M is N-1, generate(L1), generateN(M,L2),append(L1,L2,L).

:- use_module(library(apply)).
addToDB(rdf(X,Y,Z)) :- rdf_assert(X,Y,Z,instance). 
:- rdf_meta populateDB. 

%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%%% Carefully Corrupting the DB.

% N specifies number of times to decend the class hierarchy rather than 
% the number of classes or triples.  This is convenient as consistency 
% is a global property which can't easily be maintained without total traversal. 
% If you have a big schema, make N small. 
populateDB(N) :- generateN(N,L), maplist(addToDB, L).

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

demoDB :-  % abolish(rdf/3), abolish(rdf/4), 
	rdf_load('plants.rdf', [graph(instance)]), rdf_load('plant-onto.rdf', [graph(schema)]).

demoDB(Schema) :- rdf_load(Schema, [graph(schema)]). 

demoDB(Instance,Schema) :- rdf_load(Instance, [graph(instance)]), 
			   rdf_load(Schema, [graph(schema)]).

demoDB(Instance,Schema,Options) :- rdf_load(Instance, [graph(instance)|Options]), 
				   rdf_load(Schema, [graph(schema)|Options]).

%%%%%%%%%%%%%%%%%%%%%%%%%%
%%% DB Checker

stringStream(Handle, Stream) :-
    new_memory_file(Handle),
    open_memory_file(Handle, write, Stream).

streamString(Handle, String) :-
    open_memory_file(Handle, read, R, [free_on_close(true)]),
    read_string(R, _, String).

checkDB(Schema,Instance,Output) :-
    % clear out the triple store.
    % load rdf
    rdf_load(Schema, [graph(schema)]),
    rdf_load(Instance, [graph(instance)]),
    %% Setup output string stream 
    stringStream(Handle,Stream),
    write(Stream, '***** Starting check of DB *****'),
    nl(Stream),	 
    (\+ allUniqueClasses ->
	 nl(Stream),	 
	 write(Stream, 'Dulicate classes: '), 
	 nl(Stream),	 
	 duplicateClasses(C),
	 write(Stream, C), 
	 nl(Stream)
     ; true)
    ,
    (\+ allUniqueProperties ->
	 nl(Stream),	 
	 write(Stream, 'Dulicate properties: '), 
	 nl(Stream),	 
	 duplicateProperties(P),
	 write(Stream, P), 
	 nl(Stream)
     ; true)
    ,
    (\+ allSubClassesHaveClass->
	 nl(Stream),	 
	 write(Stream, 'Orphaned subclasses: '), 
	 nl(Stream),
	 orphanSubClasses(S),
	 write(Stream, S), 
	 nl(Stream)
     ; true)
    ,
    (\+ noOrphans ->
	 nl(Stream),	 
	 write(Stream, 'Orphaned instances: '), 
	 nl(Stream),
	 orphanInstances(O),
	 write(Stream, O),
	 nl(Stream)
     ; true )
    , 
    (\+ allRangedInstances ->
	 nl(Stream),	 
	 write(Stream, 'Missing Range for instances: '), 
	 nl(Stream),
	 noInstanceRange(R),
	 write(Stream, R),
	 nl(Stream)
     ; true )
    , 
    (\+ allDomainedInstances ->
	 nl(Stream),	 
	 write(Stream, 'Missing Domain for instances: '), 
	 nl(Stream),
	 noInstanceDomain(D),
	 write(Stream, D),
	 nl(Stream)
     ; true)     
    ,
    (\+ allPropertiedInstances ->
	 nl(Stream),	 
	 write(Stream, 'Missing class for properties: '), 
	 nl(Stream),
	 orphanProperties(IP),
	 write(Stream, IP),
	 nl(Stream)
     ; true)
    ,
    (\+ noBlankNodes ->
	 nl(Stream),	 
	 write(Stream, 'Blank Nodes found: '), 
	 nl(Stream),
	 blankNodes(BN),
	 write(Stream, BN),
	 nl(Stream)
     ; true)     
    ,
    (\+ allUniqueRange ->
	 nl(Stream),	 
	 write(Stream, 'Property with non-unique range found: '), 
	 nl(Stream),
	 duplicateRange(DR),
	 write(Stream, DR),
	 nl(Stream)
     ; true)     
    ,
    nl(Stream),
    write(Stream, 'Finished checking DB!'),
    close(Stream), 
    streamString(Handle, Output),
    true.
