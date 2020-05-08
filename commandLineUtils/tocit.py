#!/usr/bin/python

import sys
import argparse
import os
import re
import mimetypes
from pathlib import Path

rootPath = os.getcwd() + "/"
chapterUrlsDictionary = {}

## still a mess
## the idea is to preserve ordering from any existing p2n file
## but then to find any new file additions under the book root
## and to append them to the right chapter location
## where a chapter is any first child directory of the book root
## Should be easy. Havn't made it work yet.

##need to zap orderedPaths
parser = argparse.ArgumentParser(description='Create a recursive p2n file')
parser.add_argument( "--delFromPath", default= "/var/www/html/Github/robopages/fragments/Flies/")
args = parser.parse_args()

## ======= debugging functions

def dbgChapterNames():
  global chapterUrlsDictionary
  
  for chapterName in chapterUrlsDictionary.keys():
    print (chapterName)
  print ("end dbgChaperNames \n")

def dbgChapterUrlsDictionary(mode):
  global chapterUrlsDictionary

  fp = None
  if mode == 'write':
    fp = open('np2n', "w")

  for chapterName in chapterUrlsDictionary.keys():
    #print("chn " + chapterName)
    for subUrl in chapterUrlsDictionary[chapterName].keys():
      line = chapterName + "/" + subUrl 
      line = re.sub("TOC","",line)
      line = line.replace("//","/")
      line = re.sub("^/","",line)
      print (line)
      if mode == 'write':
        fp.write(line + "\n") 

  if mode == 'write':
    fp.close()

##============ end debugging functions


def readExistingP2N(filepath):
    global args, chapterUrlsDictionary
    try:
        Path(filepath).touch()
        fp = open(filepath, "r")
    except:
        print("No fp to open on p2n file")
        exit

    Lines = fp.readlines()
    for thisPath in Lines:
        #thisPath = thisPath.replace("\n",'')
        thisPath = thisPath.strip()
        thisChapter = getChapterName(thisPath).strip()
        subUrl = thisPath.replace(args.delFromPath, '').strip().replace(thisChapter + '/','').strip().replace("//","/")
        subUrl = re.sub("^/", "",subUrl)
          
        #print ("thisPath: " + thisPath)
        #print("thisChapter: " + thisChapter) 
        #print("subUrl: " + subUrl + "\n") 
        try:
            chapterUrlsDictionary[thisChapter][subUrl] = subUrl
        except:
            chapterUrlsDictionary[thisChapter] = {} 
            chapterUrlsDictionary[thisChapter][subUrl] = subUrl
         
    fp.close()

def getChapterName(thisPath):
  global args

  chapterName = 'xyz'
  ## following should be unnecessary
  thisPath = thisPath.replace(args.delFromPath, '')

  if thisPath.count('/') < 1:
    chapterName = 'TOC'
  else:
    dirs = thisPath.split('/')   
    chapterName = dirs[0]
  
  return (chapterName.strip())

def getSubUrl(path, thisChapter):
  global args

  subUrl = ''
  path = path.replace(args.delFromPath,'')  
  subUrl = path.replace(thisChapter,'') 

  return(subUrl.strip())

def doFile(filename):
   
    if tocMimer(filename):
      thisChapter = getChapterName(filename)
      subUrl = getSubUrl(filename,thisChapter)

      if thisChapter not in chapterUrlsDictionary.keys():
         chapterUrlsDictionary[thisChapter] = {}

      #if subUrl not in chapterUrlsDictionary[thisChapter]:
      chapterUrlsDictionary[thisChapter][subUrl] = subUrl
      #print ("doFile " + thisChapter + ": " +  subUrl)

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

def recurseDirs(path):

    if path[0] == '.':
        return ('')

    typeFs = []
    dirs = []
    chapterUrlsDictionary['TOC'] = {} 

    #print("recurseDirs(" + path + ")")
    for name in os.listdir(path):
        if name[0] == '.':
            continue
        inspectThis = os.path.join(path, name)
        if os.path.isdir(inspectThis):
            dirs.append(name)
        else:
            typeFs.append(name)


    for directory in dirs:
        if directory[0] == '.':
            continue
        newpath = os.path.join(path, directory)
        skipRoboresources = re.search("roboresources", newpath)
        if skipRoboresources:
            continue
        recurseDirs(newpath)

    for file in typeFs:
        if file[0] == '.':
            continue
        joined = os.path.join(path, file).replace(args.delFromPath, '')
        skipRoboresources = re.search("roboresources", file)
        if skipRoboresources:
            continue
        doFile(joined)



## an existing p2n may not exist
readExistingP2N(rootPath + 'p2n')
recurseDirs(rootPath)

#dbgChapterNames()
dbgChapterUrlsDictionary('write')
