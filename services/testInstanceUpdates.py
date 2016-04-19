#!/usr/bin/env python

import requests
import time
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
FOAF = 'http://xmlns.com/foaf/0.1/'
XSD = 'http://www.w3.org/2001/XMLSchema#'

counter = 0
def gensym(name):
    global counter
    counter+=1 
    return name + str(counter+1)


# def new_instant_message():
#     name = gensym('Message-')
#     node = gensym('Node')
#     instance = 'instance'
#     return """
#         ['%(object)s', '%(type)s', '%(InstantMessage)s',instance],
#         ['%(object)s', '%(issued)s', literal(type('%(xsdDateTime)s','%(date)s')),instance],
#         ['%(object)s', '%(content)s', literal(lang('Some content here...', en)), instance],
#         ['%(object)s', '%(has_creator)s', '%(node)s', instance],
#         ['%(node)s', '%(type)s', '%(UserAccount)s', instance],
#         ['%(node)s','%(accountName)s', literal(lang('An account name',en)), instance],
#         ['%(object)s', '%(link)s', '%(href)s', instance]
#     """ % { "object" : SIOC+name, "node" : node, "type" : RDF+'type',
#              "content" : NS+'content',
#              "has_creator" : NS+'has_creator', 
#              "UserAccount" : NS+'UserAccount', "link" : NS+'link',
#              "href" : 'http://www.swig.org/2009-03-10.html#' + name,
#              "issued" : PURL+'issued',
#              "InstantMessage" : ST+'InstantMessage',
#              "date" : datetime.datetime.now().isoformat(),
#              "accountName" : FOAF+'accountName',
#              "xsdDateTime" : XSD + 'dateTime'}

def new_instant_message():
    name = gensym('Message-')
    node = gensym('Node')
    instance = 'instance'
    obj = SIOC+name
    typ = RDF+'type'
    content = NS+'content'
    has_creator = NS+'has_creator'
    userAccount = NS+'UserAccount'
    link = NS+'link'
    href = 'http://www.swig.org/2009-03-10.html#' + name
    issued = PURL+'issued'
    instantMessage = ST+'InstantMessage'
    date = datetime.datetime.now().isoformat()
    accountName = FOAF+'accountName'
    xsdDateTime = XSD + 'dateTime'
    
    return [[obj, typ, instantMessage,instance],
            [obj, issued, {'type' : xsdDateTime,
                           'data' : date}, instance], 
            [obj, content, {'lang' : 'en',
                            'data' : 'Some content here...'}, instance],
            [obj, has_creator, node, instance],
            [node, typ, userAccount, instance],
            [node, accountName, {'lang' : 'en',
                                  'data' : 'An account name'}, instance],
            [obj, link, href, instance]]

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
    start = time.time()
    r = requests.post(STARDOG, data=payload)
    end = time.time()
    print "Completed in time %s" % (start - end)
    
def test_dacura_inserts():
    data = nMessages(1)
    payload = {'pragma' : json.dumps({'tests' : 'all',
                                      'commit' : 'true'}),
               'update' : json.dumps({'inserts' : data,
                                      'updates' : [],
                                      'deletes' : []})}
    print json.dumps(data)
    start = time.time()
    r = requests.post(INSTANCE, data=payload)
    end = time.time()
    print "Completed in time %s" % (end - start)
    return r
