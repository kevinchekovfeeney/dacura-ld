
:- use_module(library(http/thread_httpd)).
:- use_module(library(http/http_dispatch)).
:- use_module(library(http/html_write)).
:- use_module(library(http/http_parameters)).

:- use_module(weiRules).

write_schema(S,PS) :- 
    tmp_file('schema.rdf',PS),
    open(PS, write, Stream), 
    write(Stream, S),
    close(Stream).

write_instance(S,IS) :- 
    tmp_file('instance.rdf',IS),
    open(IS, write, Stream), 
    write(Stream, S),
    close(Stream).

my_server(Port) :-
    http_server(http_dispatch, [port(Port)]).

:- http_handler(/, rdf_file_page, []).

rdf_file_page(Request) :-
    http_parameters(Request,[],[form_data(Data)]),
    dispatch(Data).

dispatch([]) :- 
    phrase(rdf_page_grammar(''),
	   TokenizedHtml,
	   []),
    %nl,write(Data),nl,
    format('Content-type: text/html~n~n'),
    print_html(TokenizedHtml).
dispatch([schema=S,instance=I]) :- 
    format('Content-type: text/plain~n~n'),
    write_schema(S,PS), 
    write_instance(I,PI), 
    checkDB(PS,PI,Output), 
    format(Output). 

rdf_page_grammar(String) --> 
    html([html([head([title('RDF Schema Checker')]),
    		body([h1('RDF Schema Checker'),
		      h2(String),
    		      form([action('http://localhost:8000/'), 
			    enctype('multipart/form-data'), method('POST')], 
			   dl([dt('Schema RDF File'), 
			       dd(input([name=schema, type=file, value=''])), 
			       dt('Instance RDF File'), 
			       dd(input([name=instance, type=file, value=''])), 
			       dt(''), 
			       dd(input([type=submit, value='Submit']))
			  ]))])])]).

%:- server(8000)
