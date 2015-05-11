#!/usr/bin/env python

import requests
import timeit
import datetime
import json

SERVER = 'http://parvus:3020'
INSTANCE = SERVER + '/dacura/instance'
SCHEMA = SERVER + '/dacura/schema'

STARDOG = 'http://parvus:5822/sioc'

ST = 'http://rdfs.org/sioc/types#'
SIOC = 'http://rdfs.org/sioc/#'
RDF = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#'
NS = 'http://rdfs.org/sioc/ns#'
PURL = 'http://purl.org/dc/terms/'
FOAF = 'http://xmlns.com/foaf/0.1/accountName'

counter = 0
def gensym(name):
    global counter
    counter+=1 
    return name + str(counter+1)


def new_instant_message():
    name = gensym('Message-')
    node = gensym('Node')
    instance = 'instance'
    return [
        [SIOC+name, RDF+'type', ST+'InstantMessage',instance],
        [SIOC+name, PURL+'issued', '"'+datetime.datetime.now().isoformat()+'"^xsd:dateTime',
         instance],
        [SIOC+name, NS+'content', '"Some content here..."', instance],
        [SIOC+name, NS+'has_creator', node, instance],
        [node, RDF+'type', NS+'UserAccount', instance],
        [node,FOAF+'accountName', '"An account name"', instance],
        [SIOC+name, NS+'link', 'http://www.swig.org/2009-03-10.html#' + name, instance]
    ]

def nMessages(n):
    triples = []
    for i in range(0, n):
        triples += new_instant_message()
    return triples
    
def SPARQLise(triples):
    Query = """
    INSERT DATA
    { GRAPH sioc {"""
    for triple in triples:
        [x,y,z,g] = triple
        Query += " %s %s %s ." % (x,y,z)
    return Query+ "}}"

def test_stardog_inserts():
    data = SPARQLise(nMessages(1000))
    start = timeit.timeit()
    r = requests.post(STARDOG, data=payload)
    end = timeit.end()
    print "Completed in time %s" % (start - end)
    
def test_dacura_inserts():
    data = nMessages(1000)
    payload = {'pragma' : json.dumps({'tests' : 'all'}),
               'update' : json.dumps({'inserts' : data,
                                      'updates' : [],
                                      'deletes' : []})}
    start = timeit.timeit()
    r = requests.post(INSTANCE, data=payload)
    end = timeit.timeit()
    print "Completed in time %s" % (start - end)
    return r
