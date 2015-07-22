#!/usr/bin/env python

import requests
import time
import datetime
import json

SERVER = 'http://parvus:3020'

if __name__ == '__main__':

    parser = argparse.ArgumentParser(description='Controller for Dacura Quality Service API.')
    parser.add_argument('--server', help='Server name and port', default=SERVER)
    parser.add_argument('--instance', help='Instance to be checked', default='instance')
    parser.add_argument('--schema', help='Schema to check instance against', default='schema')
    # parser.add_argument('--check-schema', help='Check correctness of schema', action='store_true')
    # parser.add_argument('--check-instance', help='Check correctness of instance with respect to the schema', action='store_true')

    args = vars(parser.parse_args())

    server = args['server']
    validate = server + '/dacura/validate' 

    payload = {'pragma' : json.dumps({'tests' : 'all',
                                      'schema' : args['schema'],
                                      'instance' : args['instance']})}

    r = requests.post(validate, data=payload)
    print r.text
