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
        rects1 = ax.bar(ind, size, width, color='r')
        
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


def run_polity_plot(args):
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
        vals.sort(key=lambda x: x[2]) # Sort by polity
        
        # Truncate small values
        quads = [(x,y,p,f) for (x,y,p,f) in vals if x > 6200]
        size = [x for (x,y,p,f) in quads]
        labels = [ datetime.datetime.fromtimestamp(ts).strftime('%Y-%m-%d')
                  for (x,ts,polity,f) in quads]
        files = [ f for (x,ts,polity,f) in quads]

        N = len(size)
        ind = np.arange(N)  # the x locations for the groups
        width = 1       # the width of the bars

        
        fig, ax = plt.subplots()
        rects1 = ax.bar(ind, size, width, color='r')
        
        # add some text for labels, title and axes ticks
        ax.set_ylabel('Number of variables')
        ax.set_title('Number of variables by date and polity')
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


def run_polity_plot(args):
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
        vals.sort(key=lambda x: x[2]) # Sort by polity
        
        # Truncate small values
        quads = [(x,y,p,f) for (x,y,p,f) in vals if x > 6200]
        size = [x for (x,y,p,f) in quads]
        labels = [ datetime.datetime.fromtimestamp(ts).strftime('%Y-%m-%d')
                  for (x,ts,polity,f) in quads]
        files = [ f for (x,ts,polity,f) in quads]

        N = len(size)
        ind = np.arange(N)  # the x locations for the groups
        width = 1       # the width of the bars

        
        fig, ax = plt.subplots()
        rects1 = ax.bar(ind, size, width, color='r')
        
        # add some text for labels, title and axes ticks
        ax.set_ylabel('Number of variables')
        ax.set_title('Number of variables by date and polity')
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




if __name__ == '__main__':

    parser = argparse.ArgumentParser(description='Generate dictionary and document set.')
    parser.add_argument('--aggregate', '-a', help='Aggregate TSV files', action='store_true')
    parser.add_argument('--glob', '-g', help='Files for aggregation', default='./*.tsv')
    parser.add_argument('--plot', '-p', help='Plot histograms', action='store_true')
    parser.add_argument('--polity-plot', '-t', help='Plot polity histograms', action='store_true')
    parser.add_argument('--polity-totals', '-o', help='Plot histograms of polity totals by date', action='store_true')
    parser.add_argument('--sort', '-s', help='Run sorting of files', action='store_true')
    args = vars(parser.parse_args())

    if 'help' in args: 
        parser.print_help()
        sys.exit(0)
        
    if 'aggregate' in args and args['aggregate']:
        run_aggregate(args)
        
    if 'sort' in args and args['sort']:
        run_sort(args)
        
    if 'plot' in args and args['plot']:
        run_plot(args)

    if 'polity_plot' in args and args['polity_plot']:
        run_polity_plot(args)

    if 'polity_plot' in args and args['polity_totals']:
        run_polity_totals(args)
