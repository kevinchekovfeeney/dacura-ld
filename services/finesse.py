#!/usr/bin/env python

import glob
import csv
import re
import datetime
import argparse
import numpy as np
import matplotlib.pyplot as plt
import time
import sys

FIELDS = ['NGA', 'Polity', 'Section', 'Subsection', 'Variable', 'Value From', 'Value To', 'Date From', 'Date To', 'Fact Type', 'Value Note', 'Date Note', 'Comment', 'Area', 'Key', 'Value', 'Name','Error','Date', 'File']

OLD_COMMENTS = ["Correctly parsed."]
OLD_ERRORS = ["Fact cannot be split correctly. Check coding delimiters.",
              "Enclosing quotation marks. Please report this error to scraper maintainer.", 
              "Daterange false positive. Please report this error to scraper maintainer.", 
              "This fact could not be parsed."]

NEW_COMMENTS = ["Date is disputed", 
                "Date is uncertain",
                "The fact is uncertain", 
                "The from date is disputed", 
                "The from date is disputed - The to date is disputed", 
                "The from date is uncertain", 
                "The to date is disputed",
                "The to date is uncertain"]
NEW_ERRORS = ["warning - Surrounding a simple value in disputed brackets",
              "warning - Surrounding a simple value in uncertain brackets",
              "warning - Using comma (,) instead of semi-colon (;) to separate list of disputed values",
              "warning - Using comma (,) instead of semi-colon (;) to separate list of uncertain values"]



def run_aggregate(args):
    with open('dated.tsv', 'wb') as tsvoutf:
        
        tsvout = csv.DictWriter(tsvoutf, delimiter='\t', fieldnames=FIELDS)
        tsvout.writeheader()
        
        for f in glob.glob(args['glob']):
            m = re.match(".*([0-9]{2})([0-9]{2})([0-9]{4})T[0-9]{6}Z",f)
            if m:
                year = m.group(3)
                month = m.group(2)
                day = m.group(1)
            else:        
                m = re.match(".*([0-9]{4})([0-9]{2})([0-9]{2})",f)
                if m:
                    year = m.group(1)
                    month = m.group(2)
                    day = m.group(3)
                else:
                    print "No such name: %s" % f

        
            if m:
                d = datetime.date(int(year),int(month),int(day))
                ts = time.mktime(d.timetuple())
                with open(f,'rb') as tsvinf:
                    tsvin = csv.DictReader(tsvinf, delimiter='\t')
                    
                    for row in tsvin:
                        out = dict(row)
                        out.update({'Date' : "%s" % ts, 'File' : f})
                        tsvout.writerow(out)

def run_sort(args):
    with open('dated.tsv', 'rb') as tsvinf:
        with open('date-sorted.tsv', 'wb') as tsvoutf:
            tsvin = csv.DictReader(tsvinf, delimiter='\t')
            fields = ['Current', 'Count', 'Date']
            tsvout = csv.DictWriter(tsvoutf, delimiter='\t', fieldnames=fields)
            tsvout.writeheader()
            
            rows = []
            for row in tsvin:
                rows.append(row)
                
            rows.sort(key=lambda x: x['File'])
            rows.sort(key=lambda x: x['Date'])
            
            for row in totals: 
                tsvout.writerow(row)


def run_plot(args):
    with open('dated.tsv', 'rb') as tsvinf:
        tsvin = csv.DictReader(tsvinf, delimiter='\t')

        counts = {}
        for row in tsvin:
            if row['File'] in counts:
                (current_count,date,f) = counts[row['File']]
                counts[row['File']] = (current_count + 1, date, f)
            else:
                counts[row['File']] = (1, float(row['Date']), row['File'])

                
        vals = counts.values()
        vals.sort(key=lambda x: x[1])
        
        # Truncate small values
        triples = [(x,y,f) for (x,y,f) in vals if x > 6200]
        size = [x for (x,y,f) in triples]
        dates = [ datetime.datetime.fromtimestamp(ts).strftime('%Y-%m-%d')
                  for (x,ts,f) in triples]
        files = [ f for (x,ts,f) in triples]

        N = len(size)
        ind = np.arange(N)  # the x locations for the groups
        width = 1       # the width of the bars

        
        fig, ax = plt.subplots()
        rects1 = ax.bar(ind, size, width, color='0.75')
        
        # add some text for labels, title and axes ticks
        ax.set_ylabel('Number of variables')
        ax.set_title('Number of variables by date')
        ax.set_xticks(ind+width)

        #ax.set_xticklabels( files, rotation='vertical')
        ax.set_xticklabels( dates, rotation='vertical')
        plt.subplots_adjust(bottom=0.15)
        #ax.legend((rects1[0],), ('Men',))

        def autolabel(rects):
             # attach some text labels
             for rect in rects:
                 height = rect.get_height()
                 ax.text(rect.get_x()+.5, height - 1000, '%d'%int(height),
                         #ha='center',
                         va='top',
                         rotation='vertical')

        autolabel(rects1)

        plt.show()


def run_change_plot(args):
    with open('dated.tsv', 'rb') as tsvinf:
        tsvin = csv.DictReader(tsvinf, delimiter='\t')

        counts = {}
        for row in tsvin:
            if row['File'] in counts:
                (current_count,date,f) = counts[row['File']]
                counts[row['File']] = (current_count + 1, date, f)
            else:
                counts[row['File']] = (1, float(row['Date']), row['File'])

                
        vals = counts.values()
        vals.sort(key=lambda x: x[1])

        triples = []
        minimum = 6200
        (x_0,y_0,f_0) = (None,None,None)
        for (x,y,f) in vals:
            if x > minimum: 
                if not x_0 or y_0 == y:
                    (x_0,y_0,f_0) = (x,y,f)
                else:
                    triples.append(((x - x_0) / (y - y_0), y, f))
                    (x_0,y_0,f_0) = (x,y,f)

        # Truncate small values
        #triples = [(x,y,f) for (x,y,f) in vals]
        size = [x for (x,y,f) in triples]
        dates = [ datetime.datetime.fromtimestamp(ts).strftime('%Y-%m-%d')
                  for (x,ts,f) in triples]
        files = [ f for (x,ts,f) in triples]

        N = len(size)
        ind = np.arange(N)  # the x locations for the groups
        width = 1       # the width of the bars

        
        fig, ax = plt.subplots()
        rects1 = ax.bar(ind, size, width, color='0.75')
        
        # add some text for labels, title and axes ticks
        ax.set_ylabel('Rate of change of variables (vars / sec)')
        ax.set_title('Rate of change of variables by date')
        ax.set_xticks(ind+width)

        #ax.set_xticklabels( files, rotation='vertical')
        ax.set_xticklabels( dates, rotation='vertical')
        plt.subplots_adjust(bottom=0.15)
        #ax.legend((rects1[0],), ('Men',))

        def autolabel(rects):
             # attach some text labels
             for rect in rects:
                 height = rect.get_height()
                 ax.text(rect.get_x()+.5, height - 1000, '%d'%int(height),
                         #ha='center',
                         va='top',
                         rotation='vertical')

        autolabel(rects1)

        plt.show()


        

def run_NGA_plot(args):
    with open('dated.tsv', 'rb') as tsvinf:
        tsvin = csv.DictReader(tsvinf, delimiter='\t')

        # print tsvin.next()
        # sys.exit(0)
        
        counts = {}
        for row in tsvin:
            if row['File']+row['NGA'] in counts:
                (current_count,date,p,f) = counts[row['File']+row['NGA']]
                counts[row['File']+row['NGA']] = (current_count + 1, date, p, f)
            else:
                counts[row['File']+row['NGA']] = (1, float(row['Date']), row['NGA'], row['File'])

                
        vals = counts.values()
        vals.sort(key=lambda x: x[1]) # sort by date
        vals.sort(key=lambda x: x[2]) # Sort by NGA
        
        # Truncate small values
        quads = [(x,y,p,f) for (x,y,p,f) in vals if x > 6200]
        size = [x for (x,y,p,f) in quads]
        labels = [ datetime.datetime.fromtimestamp(ts).strftime('%Y-%m-%d')
                  for (x,ts,NGA,f) in quads]
        files = [ f for (x,ts,NGA,f) in quads]

        N = len(size)
        ind = np.arange(N)  # the x locations for the groups
        width = 1       # the width of the bars

        
        fig, ax = plt.subplots()
        rects1 = ax.bar(ind, size, width, color='0.75')
        
        # add some text for labels, title and axes ticks
        ax.set_ylabel('Number of variables')
        ax.set_title('Number of variables by date and NGA')
        ax.set_xticks(ind+width)

        #ax.set_xticklabels( files, rotation='vertical')
        ax.set_xticklabels( labels, rotation='vertical')
        plt.subplots_adjust(bottom=0.15)
        #ax.legend((rects1[0],), ('Men',))

        def autolabel(rects):
             # attach some text labels
             count = 0
             for rect in rects:
                 height = rect.get_height()
                 ax.text(rect.get_x()+.5, height - 1000, '%s' % quads[count][2],
                         va='top',
                         rotation='vertical')
                 count += 1 


        autolabel(rects1)

        plt.show()


def run_NGA_totals(args):
    with open('dated.tsv', 'rb') as tsvinf:
        tsvin = csv.DictReader(tsvinf, delimiter='\t')

        # print tsvin.next()
        # sys.exit(0)
        
        counts = {}        
        for row in tsvin:
            if row['Date'] in counts and 'NGAs' in counts[row['Date']]:
                if row['NGA'] in counts[row['Date']]['NGAs']:
                    pass
                else:
                    counts[row['Date']]['NGAs'][row['NGA']] = True
            else:
                counts[row['Date']] = {'NGAs' : {row['NGA'] : True},
                                       'File' : row['File'],
                                       'Date' : float(row['Date'])}

        vals = [(len(d['NGAs']),d['File'],d['Date']) for d in counts.values()]
        vals.sort(key=lambda x: x[2]) # sort by date
        #vals.sort(key=lambda x: x[2]) # Sort by NGA
        Max = 0
        newvals = []
        for (n,f,d) in vals: 
            if n < Max:
                pass
            else:
                Max = n
                newvals.append((n,f,d))

        vals = newvals
        # Truncate small values
        size = [n for (n,f,d) in vals]
        labels = [ datetime.datetime.fromtimestamp(ts).strftime('%Y-%m-%d')
                  for (n,f,ts) in vals]
        files = [ f for (n,f,d) in vals]

        N = len(size)
        ind = np.arange(N)  # the x locations for the groups
        width = 1       # the width of the bars

        fig, ax = plt.subplots()
        rects1 = ax.bar(ind, size, width, color='0.75')
        
        # add some text for labels, title and axes ticks
        ax.set_ylabel('Number of NGAs')
        ax.set_title('Number of NGAs by date')
        ax.set_xticks(ind+width)

        #ax.set_xticklabels( files, rotation='vertical')
        ax.set_xticklabels( labels, rotation='vertical')
        plt.subplots_adjust(bottom=0.15)
        #ax.legend((rects1[0],), ('Men',))

        def autolabel(rects):
             # attach some text labels
             count = 0
             for rect in rects:
                 height = rect.get_height()
                 ax.text(rect.get_x()+.5, height - 1000, '%s' % vals[count][2],
                         va='top',
                         rotation='vertical')
                 count += 1 


        autolabel(rects1)

        plt.show()

def run_error_plot(args):
    with open('dated.tsv', 'rb') as tsvinf:
        tsvin = csv.DictReader(tsvinf, delimiter='\t')

        # print tsvin.next()
        # sys.exit(0)
        
        counts = {}
        for row in tsvin:
            if row['File'] in counts:
                if ((row['Comment'] in NEW_ERRORS or
                     row['Error'] in OLD_ERRORS) and
                    (not args['files'] or
                     row['File'] in args['files'])):
                    
                    (current_count,date,f) = counts[row['File']]
                    counts[row['File']] = (current_count + 1, date, f)
            else:
                if ((row['Comment'] in NEW_ERRORS or
                     row['Error'] in OLD_ERRORS) and
                    (not args['files'] or
                     row['File'] in args['files'])):
                    
                    counts[row['File']] = (1, float(row['Date']), row['File'])

                
        vals = counts.values()
        vals.sort(key=lambda x: x[1]) # sort by date

        triples = [(x,y,f) for (x,y,f) in vals]
        size = [x for (x,y,f) in triples]
        dates = [ datetime.datetime.fromtimestamp(ts).strftime('%Y-%m-%d')
                  for (x,ts,f) in triples]
        files = [ f for (x,ts,f) in triples]

        N = len(size)
        ind = np.arange(N)  # the x locations for the groups
        width = 1       # the width of the bars

        
        fig, ax = plt.subplots()
        rects1 = ax.bar(ind, size, width, color='0.75')
        
        # add some text for labels, title and axes ticks
        ax.set_ylabel('Number of errors')
        ax.set_title('Number of errors by date')
        ax.set_xticks(ind+width)

        #ax.set_xticklabels( files, rotation='vertical')
        ax.set_xticklabels( dates, rotation='vertical')
        plt.subplots_adjust(bottom=0.15)
        #ax.legend((rects1[0],), ('Men',))

        def autolabel(rects):
             # attach some text labels
             count = 0
             for rect in rects:
                 height = rect.get_height()
                 ax.text(rect.get_x()+.5, height - 1000, '%s' % triples[count][0],
                         va='top',
                         rotation='vertical')
                 count += 1 


        autolabel(rects1)

        plt.show()

def run_unique_plot(args):
    with open('dated.tsv', 'rb') as tsvinf:
        tsvin = csv.DictReader(tsvinf, delimiter='\t')

        # print tsvin.next()
        # sys.exit(0)
        
        counts = {}
        for row in tsvin:
            if row['Value From']:
                value = row['Value From']
            else:
                value = row['Value']
                
            if row['File'] in counts:
                if value in counts[row['File']]['values']:
                    pass
                else: 
                    d = dict(counts[row['File']])
                    d['values'][value] = True
                    counts[row['File']] = d
            else:
                counts[row['File']] = {'values' : {value : True},
                                       'File' : row['File'],
                                       'Date' : float(row['Date'])}

        vals = [(len(d['values']),d['Date'],d['File']) for d in counts.values() if len(d['values']) > 800]
        vals.sort(key=lambda x: x[1]) # sort by date

        triples = [(x,y,f) for (x,y,f) in vals]
        size = [x for (x,y,f) in triples]
        dates = [ datetime.datetime.fromtimestamp(ts).strftime('%Y-%m-%d')
                  for (x,ts,f) in triples]
        files = [ f for (x,ts,f) in triples]

        N = len(size)
        ind = np.arange(N)  # the x locations for the groups
        width = 1       # the width of the bars

        
        fig, ax = plt.subplots()
        rects1 = ax.bar(ind, size, width, color='0.75')
        
        # add some text for labels, title and axes ticks
        ax.set_ylabel('Number of unique variables')
        ax.set_title('Number of unique variables by date')
        ax.set_xticks(ind+width)

        #ax.set_xticklabels( files, rotation='vertical')
        ax.set_xticklabels( dates, rotation='vertical')
        plt.subplots_adjust(bottom=0.15)
        #ax.legend((rects1[0],), ('Men',))

        def autolabel(rects):
             # attach some text labels
             count = 0
             for rect in rects:
                 height = rect.get_height()
                 ax.text(rect.get_x()+.5, height - 1000, '%s' % triples[count][0],
                         va='top',
                         rotation='vertical')
                 count += 1 


        autolabel(rects1)

        plt.show()


def run_unique_change_plot(args):
    with open('dated.tsv', 'rb') as tsvinf:
        tsvin = csv.DictReader(tsvinf, delimiter='\t')

        # print tsvin.next()
        # sys.exit(0)
        
        counts = {}
        for row in tsvin:
            if row['Value From']:
                value = row['Value From']
            else:
                value = row['Value']
                
            if row['File'] in counts:
                if value in counts[row['File']]['values']:
                    pass
                else: 
                    d = dict(counts[row['File']])
                    d['values'][value] = True
                    counts[row['File']] = d
            else:
                counts[row['File']] = {'values' : {value : True},
                                       'File' : row['File'],
                                       'Date' : float(row['Date'])}

                
        vals = [(len(d['values']),d['Date'],d['File']) for d in counts.values() if len(d['values']) > 800]
        vals.sort(key=lambda x: x[1]) # sort by date

        triples = []
        minimum = 800
        (x_0,y_0,f_0) = (None,None,None)
        for (x,y,f) in vals:
            if x > minimum: 
                if not x_0 or y_0 == y:
                    (x_0,y_0,f_0) = (x,y,f)
                else:
                    triples.append(((x - x_0) / (y - y_0), y, f))
                    (x_0,y_0,f_0) = (x,y,f)
        
        
        #triples = [(x,y,f) for (x,y,f) in vals]
        size = [x for (x,y,f) in triples]
        dates = [ datetime.datetime.fromtimestamp(ts).strftime('%Y-%m-%d')
                  for (x,ts,f) in triples]
        files = [ f for (x,ts,f) in triples]

        N = len(size)
        ind = np.arange(N)  # the x locations for the groups
        width = 1       # the width of the bars

        
        fig, ax = plt.subplots()
        rects1 = ax.bar(ind, size, width, color='0.75')
        
        # add some text for labels, title and axes ticks
        ax.set_ylabel('Rate of change of unique variables (vars / second)')
        ax.set_title('Rate of unique variable change by date')
        ax.set_xticks(ind+width)

        #ax.set_xticklabels( files, rotation='vertical')
        ax.set_xticklabels( dates, rotation='vertical')
        plt.subplots_adjust(bottom=0.15)
        #ax.legend((rects1[0],), ('Men',))

        def autolabel(rects):
             # attach some text labels
             count = 0
             for rect in rects:
                 height = rect.get_height()
                 ax.text(rect.get_x()+.5, height - 1000, '%s' % triples[count][0],
                         va='top',
                         rotation='vertical')
                 count += 1 


        autolabel(rects1)

        plt.show()


        

def run_complex_plot(args):
    with open('dated.tsv', 'rb') as tsvinf:
        tsvin = csv.DictReader(tsvinf, delimiter='\t')

        counts = {}
        for row in tsvin:
            if (row['File'] in counts and
                row['Fact Type'] == 'complex'):
                
                (current_count,date,f) = counts[row['File']]
                counts[row['File']] = (current_count + 1, date, f)
            else:
                if ('Value Note' in row and
                    row['Fact Type'] == 'complex'):
                    
                    counts[row['File']] = (1, float(row['Date']), row['File'])

                
        vals = counts.values()
        vals.sort(key=lambda x: x[1])
        
        # Truncate small values
        triples = [(x,y,f) for (x,y,f) in vals if x > 1000]
        size = [x for (x,y,f) in triples]
        dates = [ datetime.datetime.fromtimestamp(ts).strftime('%Y-%m-%d')
                  for (x,ts,f) in triples]
        files = [ f for (x,ts,f) in triples]

        N = len(size)
        ind = np.arange(N)  # the x locations for the groups
        width = 1       # the width of the bars

        
        fig, ax = plt.subplots()
        rects1 = ax.bar(ind, size, width, color='0.75')
        
        # add some text for labels, title and axes ticks
        ax.set_ylabel('Number of complex variables')
        ax.set_title('Number of complex variables by date')
        ax.set_xticks(ind+width)

        #ax.set_xticklabels( files, rotation='vertical')
        ax.set_xticklabels( dates, rotation='vertical')
        plt.subplots_adjust(bottom=0.15)
        #ax.legend((rects1[0],), ('Men',))

        def autolabel(rects):
             # attach some text labels
             for rect in rects:
                 height = rect.get_height()
                 ax.text(rect.get_x()+.5, height - 1000, '%d'%int(height),
                         #ha='center',
                         va='top',
                         rotation='vertical')

        autolabel(rects1)

        plt.show()


def run_complex_change_plot(args):
    with open('dated.tsv', 'rb') as tsvinf:
        tsvin = csv.DictReader(tsvinf, delimiter='\t')

        counts = {}
        for row in tsvin:
            if (row['File'] in counts and
                row['Fact Type'] == 'complex'):
                
                (current_count,date,f) = counts[row['File']]
                counts[row['File']] = (current_count + 1, date, f)
            else:
                if ('Value Note' in row and
                    row['Fact Type'] == 'complex'):
                    
                    counts[row['File']] = (1, float(row['Date']), row['File'])

                
        vals = counts.values()
        vals.sort(key=lambda x: x[1])

        triples = []
        minimum = 1000
        (x_0,y_0,f_0) = (None,None,None)
        for (x,y,f) in vals:
            if x > minimum: 
                if not x_0 or y_0 == y:
                    (x_0,y_0,f_0) = (x,y,f)
                else:
                    triples.append(((x - x_0) / (y - y_0), y, f))
                    (x_0,y_0,f_0) = (x,y,f)
        
        # Truncate small values
        #triples = [(x,y,f) for (x,y,f) in vals if x > 1000]
        size = [x for (x,y,f) in triples]
        dates = [ datetime.datetime.fromtimestamp(ts).strftime('%Y-%m-%d')
                  for (x,ts,f) in triples]
        files = [ f for (x,ts,f) in triples]

        N = len(size)
        ind = np.arange(N)  # the x locations for the groups
        width = 1       # the width of the bars

        
        fig, ax = plt.subplots()
        rects1 = ax.bar(ind, size, width, color='0.75')
        
        # add some text for labels, title and axes ticks
        ax.set_ylabel('Rate of change of complex variables (vars / seconds)')
        ax.set_title('Rate of change of complex variables')
        ax.set_xticks(ind+width)

        #ax.set_xticklabels( files, rotation='vertical')
        ax.set_xticklabels( dates, rotation='vertical')
        plt.subplots_adjust(bottom=0.15)
        #ax.legend((rects1[0],), ('Men',))

        def autolabel(rects):
             # attach some text labels
             for rect in rects:
                 height = rect.get_height()
                 ax.text(rect.get_x()+.5, height - 1000, '%d'%int(height),
                         #ha='center',
                         va='top',
                         rotation='vertical')

        autolabel(rects1)

        plt.show()

def run_predefined(args):
    triples = [(56160, 1953, "2015-03-16"),
               (70928, 1775, "2015-05-20"),
               (72411, 1750, "2015-05-31"),
               (72252, 1578, "2015-06-09")]

    N = 4
    ind = np.arange(N)  # the x locations for the groups
    width = 1       # the width of the bars

    fig, ax = plt.subplots()
    totals = [ x / y for (x,y,d) in triples]
    rects1 = ax.bar(ind, totals, width, color='.80')

    errors = [y for (x,y,d) in triples]
    # rects2 = ax.bar(ind+width, errors, width, color='.65')

    labels = [d for (x,y,d) in triples]
    
    # add some text for labels, title and axes ticks
    ax.set_ylabel('Number of variables per error')
    ax.set_title('Number of variables per error by date')
    ax.set_xticks(ind+width)
    ax.set_xticklabels( labels , rotation='vertical')

    # ax.legend( (rects1[0], rects2[0]), ('Totals', 'Errors') )

    def autolabel(rects):
    # attach some text labels
        for rect in rects:
            height = rect.get_height()
            ax.text(rect.get_x()+.5, height-10, '%d'%int(height),
                    # ha='center',
                    va='top',
                    rotation='vertical')
            
    autolabel(rects1)
    #autolabel(rects2)

    plt.show()

    
    
        
if __name__ == '__main__':

    parser = argparse.ArgumentParser(description='Generate dictionary and document set.')
    parser.add_argument('--aggregate', '-a', help='Aggregate TSV files', action='store_true')
    parser.add_argument('--glob', '-g', help='Files for aggregation', default='./*.tsv')
    parser.add_argument('--files', '-f', help='Files to filter', nargs='*', default=None)
    parser.add_argument('--plot', '-p', help='Plot variables by date', action='store_true')
    parser.add_argument('--change-plot', '-P', help='Plot rate of change of variables', action='store_true')
    parser.add_argument('--NGA-plot', '-n', help='Plot NGA histograms', action='store_true')
    parser.add_argument('--NGA-totals', '-N', help='Plot histograms of NGA totals by date', action='store_true')
    parser.add_argument('--sort', '-s', help='Run sorting of files', action='store_true')
    parser.add_argument('--errors', '-e', help='Plot error totals by date', action='store_true')
    parser.add_argument('--unique', '-u', help='Plot unique variables by date', action='store_true')
    parser.add_argument('--complex', '-c', help='Plot complex variables by date', action='store_true')
    parser.add_argument('--complex-change', '-C', help='Plot complex varible change rate', action='store_true')
    parser.add_argument('--unique-change', '-U', help='Plot unique variable change rate', action='store_true')
    parser.add_argument('--pre-defined', '-d', help='Plot hardcoded values', action='store_true')
    
    args = vars(parser.parse_args())

    #print args
    #sys.exit(0)
    
    if 'help' in args: 
        parser.print_help()
        sys.exit(0)
        
    if 'aggregate' in args and args['aggregate']:
        run_aggregate(args)
        
    if 'sort' in args and args['sort']:
        run_sort(args)
        
    if 'plot' in args and args['plot']:
        run_plot(args)

    if 'change_plot' in args and args['change_plot']:
        run_change_plot(args)

    if 'NGA_plot' in args and args['NGA_plot']:
        run_NGA_plot(args)

    if 'NGA_plot' in args and args['NGA_totals']:
        run_NGA_totals(args)

    if 'errors' in args and args['errors']:
        run_error_plot(args)    

    if 'unique' in args and args['unique']:
        run_unique_plot(args)    

    if 'complex' in args and args['complex']:
        run_complex_plot(args)    

    if 'complex_change' in args and args['complex_change']:
        run_complex_change_plot(args)    
        
    if 'unique_change' in args and args['unique_change']:
        run_unique_change_plot(args)    


    if 'pre_defined' in args and args['pre_defined']:
        run_predefined(args)    
