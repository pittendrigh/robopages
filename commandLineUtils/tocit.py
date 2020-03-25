#!/usr/bin/python

import sys
import argparse
import os
import re
import mimetypes
from pathlib import Path

page = 1
rootPath = os.getcwd() + "/"
page2numDictionary = {}
chapterUrlsDictionary = {}
orderedPaths = []
orderedChapters = []
parser = argparse.ArgumentParser(description='Create a recursive p2n file')
parser.add_argument( "--delFromPath", default= "/var/www/html/Book/robopages/fragments/Flies/Library/Sandy-Pittendrigh/")
args = parser.parse_args()

## ======= debugging functions
##
def checkOrderedChapters():
    global orderedChapters
    for chapter in orderedChapters:
        print(chapter)

def checkOrderedPaths():
    global orderedPaths
    for path in orderedPaths:
        print(path)

def processchapterUrlsDictionary():
    global chapterUrlsDictionary, orderedPaths
    for pathUrl in orderedPaths:
          pathUrl = pathUrl.strip()
          subUrl = chapterUrlsDictionary[pathUrl]
          for subUrl in chapterUrlsDictionary[pathUrl]:
            displayLine = pathUrl + '/' + subUrl
            print(displayLine.replace('theRoot/', ''))

##============ end debugging functions

def processPage2numDictionary(mode):
    global page2numDictionary

    print("processPage2numDictionary(" + mode + ")")
    if mode == 'write':
        fp = open('p2n', 'w')
        fp.close()
        fp = open('p2n', 'a')
    print("len(page2numDictionary) " + str(len(page2numDictionary)))

    for pathKey in page2numDictionary.keys():
        if mode == 'print':
            print(pathKey.replace(args.delFromPath, ''))
        else:
            #print ("pw: ", pathKey.replace(args.delFromPath, ''))
            fp.write(pathKey.replace(args.delFromPath, '') + "\n")
    fp.close()

#### p2n file paths have to START at the folder where the book chapters reside
#### so the startDirs of the paths vary
#### /var/www/html/Book/robopages/fragments/Flies/Library/Sandy-Pittendrigh/ for instance
#### where the book is contained in Sandy-Pittendrigh and Sandy-Pittendrigh/Dryflies is a chapter
#### Top-level dirs in the book container dir are chapters in said book
#### Dryflies/Mayflies/pmds.htm (all book pages are *.htm fragments,
#### such as pmds.htm, which is *not* the case in the more general robopages context.)
####
def readExistingP2N(filepath):
    global chapterUrlsDictionary, orderedPaths, orderedChapters
    try:
        Path(filepath).touch()
        fp = open(filepath, "r")
    except:
        print("No fp to open on p2n file")
        exit

    cnt = 1
    Lines = fp.readlines()
    for line in Lines:
        thisDir = ''
        subUrl = ''
        line = line.strip()
        orderedPaths.append(line)
        #print("orderedPaths append: ", line)

        slashesCount = line.count('/')
        if slashesCount < 1:
            thisDir = 'theRoot'
            subUrl = line
        else:
            dirs = line.split('/')
            thisDir = dirs[0]
            subUrl = '/'.join(dirs[1:])
            # we have thisDir now
        if thisDir not in orderedChapters:
          #print("orderedChapters.append " + thisDir)
          orderedChapters.append(thisDir)
        try:
          chapterUrlsDictionary[thisDir].append(subUrl)
        except:
          chapterUrlsDictionary[thisDir] = []
          chapterUrlsDictionary[thisDir].append(subUrl)

        line = fp.readline()
        cnt += 1
    fp.close()

def getChapterName(thisPath):
    slashesCount = thisPath.count('/')
    if slashesCount < 1:
        thisDir = 'theRoot'
        subUrl = thisPath
    else:
        dirs = thisPath.split('/')
        thisDir = dirs[0]
        ##subUrl = '/'.join(dirs[1:])
    return (thisDir)

## orderedPaths is a list which is a copy of any existing p2n file
## From orderedPaths get a "thisDir" (perhaps rename as thisChapter)
## by peeling off the most left-side path directory
## Now print as is from orderedPaths until a new thisDir is encountered
## Now work with chapterUrlsDictionary to find new urls not already in old p2n.
## if so write them (append to this chapter) now.
## Now move onto the next thisDir
## ...will we ever need page2numDictionary? Ah useful for "already got this url"
## chapterUrlsDictionary has chapter dirNames as keys holding a list of subUrls
## For instance "Dryflies" might be a key with 'Mayflies/Ducktails/Ducktail-pmds.htm'
## as one of many subpaths
##
def writeNewP2N():
    global page2numDictionary, chapterUrlsDictionary, orderedPaths

    length = len(orderedPaths)
    print("len(orderedPaths) " + str(length))
    pageNum = 1
    if length > 0:
        f = open("np2n", "w")
        thisPath = orderedPaths[pageNum]
        thisDir = getChapterName(thisPath)
        lastDir = thisDir
        while thisDir == lastDir and pageNum < length:
            thisPath = orderedPaths[pageNum]
            thisDir = getChapterName(thisPath)
            if thisDir == lastDir:
                print(orderedPaths[pageNum])
            else:
                print("thisDir: " + thisDir)
                if chapterUrlsDictionary[thisDir] != None:
                  for subUrl in chapterUrlsDictionary[thisDir]:
                    if subUrl not in page2numDictionary:
                      ##write????????
                      print(subUrl)
            lastDir = thisDir
            pageNum += 1
        f.close()
    else:
        print ("call processPage2numDictionary")
        processPage2numDictionary('write')

## ##f.write(urlpath + "\n")
## not doing tocMimer here? Allow isdir and *.htm only
## for now we are relying on good input from afar
##
def doFile(filename):
    global page 
    
    if tocMimer(filename):
      print ("doFile: ", filename)
      page2numDictionary[filename] = page
      page = page + 1

def tocMimer(path):
    ret = False
    if os.path.isdir(path) == True:
        ret = True

    ## zips pdf and everything else must be wrapped
    ## in an *.htm fragment.  This is a book not a website
    ## ...for now anyway
    types = [".htm"]
    filename, suffix = os.path.splitext(path)
    if suffix in types:
        ret = True

    return (ret)

## recurseDirs makes no assignments
## but it does twice in its codes) call doFile(somePath)
## doFile increments the global page number and
## also inserts to the global page2numDictionary
## Paths start absolute but get stored from the chapter
## leve down. (path.replace(args.delFromPath,'')
##
def recurseDirs(path):
    if path[0] == '.':
        return ('')

    typeFs = []
    dirs = []
    for name in os.listdir(path):
        if name[0] == '.':
            continue
        checkThis = os.path.join(path, name)
        if os.path.isdir(checkThis):
            dirs.append(name)
            #print("dir: ", checkThis)
        else:
            typeFs.append(name)
            #print("typeF: ", checkThis)

    for file in typeFs:
        if file[0] == '.':
            continue
        joined = os.path.join(path, file).replace(args.delFromPath, '')
        skipRoboresources = re.search("roboresources", joined)
        if skipRoboresources:
            continue
        doFile(joined)

    for directory in dirs:
        if directory[0] == '.':
            continue
        newpath = os.path.join(path, directory).replace(args.delFromPath, '')
        skipRoboresources = re.search("roboresources", newpath)
        if skipRoboresources:
            continue
        doFile(os.path.join(path, directory))

        recurseDirs(newpath)

readExistingP2N(rootPath + '/p2n')
#checkOrderedPaths()
#checkOrderedChapters()

recurseDirs(rootPath)
#processPage2numDictionary("print") ## print mode means debugging
writeNewP2N()
