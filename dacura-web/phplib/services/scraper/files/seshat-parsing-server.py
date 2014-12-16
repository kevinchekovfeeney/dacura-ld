# coding=utf-8
# parsing server for the seshat wiki scraper
# part of dacura
# Copyright (C) 2014 Dacura Team

# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

from bottle import route, request, run, get, response
from simpleparse.common import numbers, strings, comments
from simpleparse.parser import Parser
import json, random, logging

def splitCheck(fact):
	if ":" in fact or ";" in fact or "[" in fact or "{" in fact:
		return True
	return False

def parseValue(factval):
	success, resultTrees, nextCharacter = parser.parse(factval)
	return success, "This fact was parsed.", resultTrees

def parseCheck(fact):
	factArray = fact.split(u"\u2663")
	if len(factArray) != 2:
		nameValuePair = (fact, "")
		return True, "Fact cannot be split correctly. Check coding delimiters.", nameValuePair
	name = factArray[0]
	value = factArray[1]
	name = name.split(u"\u2660")[1].lstrip().rstrip()
	value = value.split(u"\u2665")[0].lstrip().rstrip()
	nameValuePair = name, value
	if splitCheck(value):
		if value[0] == "'" or value[0] == '"':
			return True, "Enclosing quotation marks. Please report this error to scraper maintainer.", nameValuePair
		parsedFact = value, parser.parse(value)
		nameValuePair = name, parsedFact
		if parsedFact[1][1] and parsedFact[1][2] == len(value):
			if "-[" in value or "]-" in value:
				return True, "Daterange false positive. Please report this error to scraper maintainer.", nameValuePair
			return False, "Correctly parsed.", nameValuePair
		return True, "This fact could not be parsed.", nameValuePair
	return False, "", nameValuePair

declaration = r'''
word 				:= 		[a-zA-Z0-9 ,.%/]+
SPACE				:=		" "
disagreement 		:= 		"{", (word, ";")+, word, "}"
uncertainty 		:=		SPACE*, '[', word, ("-"/";"), word, ']'
singleuncertainty	:=		SPACE*, '[', word, ']'
token				:=		uncertainty/disagreement/word
rangepart 			:= 		token, "-", token
piece				:=		rangepart/uncertainty/disagreement/word/singleuncertainty
fact 				:= 		(piece, ":", piece)/piece
factstatement 		:= 		((fact, ";")*, fact)/fact
facttest			:=		fact, "; ", fact
'''

parser = Parser(declaration, "factstatement")
returnValues = True
returnOnlyErrors = False
returnOnlyParsed = False
FORMAT = '%(asctime)-15s %(ip)s %(user)-8s %(message)s'
logging.basicConfig(filename='parserlog.txt', format=FORMAT, level=logging.DEBUG)

@route('/parser', method='POST')
def index():
	ip = request['REMOTE_ADDR']
	data = {"ip": ip, "user": ""}
	theText = request.body
	ngaList = []
	try:
		x = theText.getvalue()
	except AttributeError:
		x = theText.read()
	y = json.loads(x)
	data["user"] = y[0]["metadata"]["user"]
	if data["user"] == "x_scraper_user_id":
		data["user"] = "SCRAPER"
		logging.info("Scraper request", extra=data)
	else:
		logging.info("Checking " + y[0]["metadata"]["polity"], extra=data)
	for item in y:
		try:
			logging.info("Polity scraped: " + item["metadata"]["polity"], extra=data)
			nga = item["metadata"]["nga"]
			if nga not in ngaList:
				ngaList.append(nga)
			for each in item["data"]:
				try:
					unparsed = each["contents"]
					err, msg, val = parseCheck(unparsed)
					each["error"] = err
					each["errorMessage"] = msg
					if returnValues:
						each["contents"] = ""
						each["value"] = val
					if returnOnlyErrors and msg == [] and err == True:
						each = []
					if returnOnlyParsed and msg == "":
						each = []
				except TypeError:
					logging.debug("TypeError" + str(item), extra=data)
		except KeyError:
			logging.debug("KeyError" + str(item), extra=data)
	a = json.dumps(y)
	response.headers['Access-Control-Allow-Origin'] = '*'
	logging.info(str(len(y)) + " polities grabbed", extra=data)
	if len(ngaList) < 1 and ngaList[0] != "":
		logging.info(str(len(ngaList)) + " NGAs Grabbed: " + str(ngaList), extra=data)
	return a

run(host='localhost', port=1234)