#!/usr/bin/env python

import glob
import csv
import re
import datetime
import numpy as np
import matplotlib.pyplot as plt


def run_aggregate(args): 
    for f in glob.glob(args['glob']):
        noHeader = True
        with open('dated.tsv', 'ab') as tsvoutf:
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
                with open(f,'rb') as tsvinf:
                    tsvin = csv.DictReader(tsvinf, delimiter='\t')
                    
                    if noHeader:
                        header = tsvin.fieldnames + ["Date", "File"]
                        tsvout = csv.DictWriter(tsvoutf, delimiter='\t', fieldnames=header)
                        tsvout.writeheader()
                        noHeader = False
                    
                    for row in tsvin:
                        out = dict(row)
                        out.update({'Date' : d.isoformat(), 'File' : f})
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
    with open('date-sorted.tsv', 'rb') as tsvinf:
        tsvin = csv.DictReader(tsvinf, delimiter='\t')
        
        totals = []
        last = None
        count = 0
        for row in tsvin:
            current = row['File']
            if current == last:
                count += 1
            else:
                last = current
                totals.append({'Current' : current, 'Count': count, 'Date' : row['Date']})
                count = 1
        # final element
        totals.append({'Current' : current, 'Count': count, 'Date' : row['Date']})

        counts = [row['Count'] for row in totals]
        print counts
        n_bins = len(totals)
        
        colors = ['red', 'tan', 'lime']
        n, bins, patches = plt.hist(counts, n_bins, normed=1, histtype='bar', color=colors, label=colors)
        plt.plot(bins)
        plt.show()


if __name__ == '__main__':

    parser = argparse.ArgumentParser(description='Generate dictionary and document set.')
    parser.add_argument('--aggregate', '-a', help='Aggregate TSV files', default='./*.tsv')
    parser.add_argument('--plot', '-p', help='Plot histograms', action='store_true')
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
