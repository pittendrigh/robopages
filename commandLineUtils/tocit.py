#!/usr/bin/python

import sys
import argparse
import os
import re
import mimetypes
from pathlib import Path

rootPath = os.getcwd() + "/"
chapterUrlsDictionary = {}

# edit default defFromPath as needed 

# Buggy first take. Not prime time yet 
# The idea is to preserve ordering from any existing p2n file
# but then to find any NEW file additions under the book root
# and then to append them to (approximately) the right chapter location.
#
# A chapter is any first child directory of the book root
# Chapters may have subdirectories for organizational conveniente.
# Sub-directories of chapters are not chapters.
#
# Each chapter and each chapter subdirectory (if exists)
# Any index.htm in any chapter should be the first path in 
# that chapter group (first link later on, in php codes)
# So a warning: index.htm always comes first not yet implemented. 
#
# delFromPath can't be a simple cwd function because of robopages quirks
# at the top of the fragments/ hierarchy -- that needs nandling too
# delFromPath needs work
#
# debugging? Start by erasing any existing p2n
# output (for now) is np2n.  Look at it.  copy it as p2n if you like what you see
#
parser = argparse.ArgumentParser(description='Create a recursive p2n file')
parser.add_argument( "--delFromPath", default= "/var/www/html/photography/robopages/fragments/")
args = parser.parse_args()

# ======= debugging functions

def ddbgChapterNames():
  global chapterUrlsDictionary
  
  for chapterName in chapterUrlsDictionary.keys():
    print (chapterName)
  print ("end dbgChaperNames \n")

#============ end debugging functions

#======begin output functions
def processChapterUrlsDictionary(mode):
  global chapterUrlsDictionary

  fp = None
  if mode == 'write':
    fp = open('np2n', "w")

  for chapterName in chapterUrlsDictionary.keys():
    if mode == 'write' and not re.search('TOC',chapterName):
      fp.write(chapterName.rstrip('//') + "\n")
    for subUrl in chapterUrlsDictionary[chapterName].keys():
      line = chapterName + subUrl 

      # if not printing any TOC line why do we have them?
      # debug this at some point
      if not re.search('TOC',line):
        print (line)
      if mode == 'write':
          fp.write(line + "\n") 

  if mode == 'write':
    fp.close()
#======end output functions


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
        subUrl = re.sub("/$", "",subUrl)
          
        try:
            chapterUrlsDictionary[thisChapter][subUrl] = subUrl
        except:
            chapterUrlsDictionary[thisChapter] = {} 
            chapterUrlsDictionary[thisChapter][subUrl] = subUrl
         
    fp.close()

def getChapterName(thisPath):
  global args

  chapterName = 'xyz'
  # following should be unnecessary
  thisPath = thisPath.replace(args.delFromPath, '')

  if thisPath.count('/') < 1:
    chapterName = 'TOC/'
  else:
    dirs = thisPath.split('/')   
    chapterName = dirs[0] + '/'
  
  return (chapterName.strip())

def getSubUrl(path, thisChapter):
  global args

  subUrl = ''
  path = path.replace(args.delFromPath,'')  
  subUrl = path.replace(thisChapter,'') 

  return(subUrl.strip())

def doFile(filePath):
  
    #filePath = re.sub("TOC","",filePath)
    filePath = filePath.replace("//","/").strip()
    filePath = re.sub("^/","",filePath)
    if tocMimer(filePath):
      thisChapter = getChapterName(filePath).strip()
      subUrl = getSubUrl(filePath,thisChapter).strip()
      subUrl = re.sub("^/","",subUrl)

      if thisChapter not in chapterUrlsDictionary.keys():
         chapterUrlsDictionary[thisChapter] = {}

      if subUrl not in chapterUrlsDictionary[thisChapter]:
        chapterUrlsDictionary[thisChapter][subUrl] = subUrl
       

def tocMimer(path):
    ret = False
    if os.path.isdir(path) == True:
        ret = True

    # zips pdf and everything else must be wrapped
    # in an *.htm fragment. 
    # Books have chapters, meaningless chapter subdirectories 
    # and leaf level files ending in *.htm
    types = [".htm"]
    filePath, suffix = os.path.splitext(path)
    if suffix in types:
        ret = True

    return (ret)

def recurseDirs(path):

    if path[0] == '.':
        return ('')

    typeFs = []
    dirs = []

    for name in os.listdir(path):
        if name[0] == '.':
            continue
        inspectThis = os.path.join(path, name)
        if os.path.isdir(inspectThis):
            dirs.append(name)
        else:
            typeFs.append(name)

    for file in typeFs:
        if file[0] == '.':
            continue
        joined = os.path.join(path, file).replace(args.delFromPath, '')
        skipRoboresources = re.search("roboresources", file)
        if skipRoboresources:
            continue
        doFile(joined)

    for directory in dirs:
        if directory[0] == '.':
            continue
        newpath = os.path.join(path, directory)
        skipRoboresources = re.search("roboresources", newpath)
        if skipRoboresources:
            continue
        #doFile(newpath)
        recurseDirs(newpath)


# an existing p2n may not exist
# chapterUrlsDictionary['TOC'] = {} 
readExistingP2N(rootPath + 'p2n')
recurseDirs(rootPath)

#dbgChapterNames()
processChapterUrlsDictionary('write')
