#!/usr/bin/env python

import requests

SERVER = 'http://parvus:3020/'
INSTANCE = SERVER + '/dacura/instance/'
SCHEMA = SERVER + '/dacura/schema/'

STARDOG = 'http://parvus:5822/sioc'

ST = 'http://rdfs.org/sioc/types#'
SIOC = 'http://rdfs.org/sioc/#'
RDF = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#'
NS = 'http://rdfs.org/sioc/ns#'
PURL = 'http://purl.org/dc/terms/'
    sioc:TalkAtT11-00-51     a st:InstantMessage;
         <http://purl.org/dc/terms/issued> "2009-03-10T11:00:51Z";
         :content "logger-sioc-rdfa has joined #swig";
         :has_creator  [
             a :UserAccount;
             <http://xmlns.com/foaf/0.1/accountName> "logger-sioc-rdfa" ];
         :link <http://www.swig.org/2009-03-10.html#T11-00-51> .
FOAF = 'http://xmlns.com/foaf/0.1/accountName'

counter = 0
def gensym(name):
    global counter
    counter+=1 
    return name + str(counter+1)


def new_instant_message():
    name = gensym('Message-')
    node = gensym('Node')
    return
    [[SIOC+name, RDF+'type', ST+'InstantMessage',instance],
     [SIOC+name, PURL+'issued', datetime.datetime.now().isoformat()+"xsd:dateTime", instance],
     [SIOC+name, NS+'content', "Some content here...", instance],
     [SIOC+name, NS+'has_creator', node, instance],
     [node, RDF+'type', NS+'UserAccount', instance],
     [node,FOAF+'accountName', "An account name", instance],
     [SIOC+name, NS+'link', 'http://www.swig.org/2009-03-10.html#' + name, instance]]

def SPARQLise(triples):
    Query = """
    INSERT DATA
    { GRAPH sioc {"""
    for triple in tripes:
        [x,y,z,g] = triple
        Query += " %s %s %s ." % (x,y,z)
    return Query+ "}}"


def package(inserts, deletes):
    return {"tests" : "all",
            "inserts" : inserts,
            "deletes" : deletes}

#test_instance_json={"tests" : "all",
#                    "inserts" : [[]]
#                    "deletes" : [[]]
#                    }


def test_stardog_inserts():
    r = requests.post(STARDOG)
        
def test_dacura_inserts():
    r = requests.post(INSTANCE)
    
