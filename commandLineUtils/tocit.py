#!/usr/bin/python

import argparse
import os
import re
from pathlib import Path

## A p2n file is a sequential list of partial URLs
## used by robopage's server-side ebook-like bookNav layout
##
## chapters2UrlsDict is a dictionary of dictionaries.
## perhaps it should be a dictoriary of list
##

rootPath = os.getcwd() + "/"
chapters2UrlsDict = {}

parser = argparse.ArgumentParser(description='Create a recursive p2n file')
parser.add_argument( "--delFromPath", default= "")
args = parser.parse_args()
if not args.delFromPath:
  args.delFromPath = os.getcwd() + '/'

# ======= Notes 
#  The idea here is to preserve the ordering of any already existing
#  p2n file and then to insert any new files found, roughly in the
#  right place--at the end of the current book Chapter. 
#  The idea relies on the python3 promise to perserve
#  initial dictionary input ordering. 
# ======= 

# ======= debugging functions
def dbgChapterNames():
  global chapters2UrlsDict
  
  for chapterName in chapters2UrlsDict.keys():
    print (chapterName)
  print ("end dbgChaperNames \n\n")
#============ end debugging functions

## prints to terminal or writes to file as per incoming mode argument
def p2nFileSave(mode):
  global chapters2UrlsDict

  fp = None
  if mode == 'write':
    fp = open('np2n', "w")

  for chapterName in chapters2UrlsDict.keys():
    if mode == 'write' and not re.search('BOOKROOT',chapterName):
      fp.write(chapterName.rstrip('//') + "\n")
    for subUrl in chapters2UrlsDict[chapterName].keys():
      line = os.path.join(chapterName + "/" + subUrl)
      line = line.replace('BOOKROOT','');

      line = line.replace("BOOKROOT","")
      if line.startswith('/'):
        line = line[1:]
      if line.endswith('/'):
        line = line[:-1]

      if mode == 'write':
            fp.write(line + "\n") 

  if mode == 'write':
    fp.close()
#======end output functions

## if a p2n file exists try to preserve its ordering
## but skip over any specified paths that do not exist anymore
def readExistingP2nFile(filepath):
    global args, chapters2UrlsDict

    try:
        Path(filepath).touch()
        fp = open(filepath, "r")
    except:
        print("No fp to open on p2n file")

    Lines = fp.readlines()
    for thisLine in Lines:
        thisPath = thisLine.strip()
        statCheckFile(thisPath)
 
    fp.close()

## examine a path and return its top level dir, 
## which is assumed to be a book chapter name
def getChapterName(thisPath):
  global args

  thisPath = thisPath.replace(args.delFromPath,'')
  chapterName = 'xyz'
  # following should be unnecessary
  thisPath = thisPath.replace(args.delFromPath, '')

  if thisPath.count('/') < 1:
    chapterName = 'BOOKROOT'
  else:
    dirs = thisPath.split('/')   
    #chapterName = dirs[0] + '/'
    chapterName = dirs[0] 
  
  return (chapterName.strip())

## examine a path.  Assume left-most dir is a chapter (thisChapter)
## and strip it off, in order to get the subUrl that inserts
## into the chapters2UrlsDict for thisChapter
def getSubUrl(path, thisChapter):
  global args

  subUrl = ''
  path = path.replace(args.delFromPath,'')  
  subUrl = path.replace(thisChapter,'') 

  return(subUrl.strip())

## used by readExistingP2nFile 
## check first to see if the specified path still exists
## skip if not.  Else call doFile, which
## is the same insert into chapters2UrlsDict routine
## checkForFileChanges uses
def statCheckFile(path):
  statPath = os.path.join(args.delFromPath + path)
  if Path(statPath).exists():
    #print ("statCheckFile: " + path)
    doFile(path)
  else:
    print (statPath + " does not exist")

# inserts into chapters2UrlsDict, if tocMimer approves
def doFile(path):

    ##filePath = path.replace("//","/").strip()
    subUrl = ''
    filePath = path  
    filePath = filePath.replace(args.delFromPath,"")
    fileType = tocMimer(path).strip()

    thisChapter = getChapterName(filePath).strip()
 
    if fileType == 'page' or fileType == 'dir':
      subUrl = getSubUrl(filePath,thisChapter).strip()
      subUrl = re.sub("^/","",subUrl)
      subUrl = re.sub("/$","",subUrl)
      #print(thisChapter + " " + fileType + " " + path + " [" + subUrl + "]") 

      if thisChapter not in chapters2UrlsDict.keys():
        chapters2UrlsDict[thisChapter] = {}

      ## a Gallery has only images, so it contains  no *.htm file to 
      ## trigger subUrl into chapters2UrlsDict insert 
      noPageDirs = ["Gallery"]

      if os.path.basename(subUrl) in noPageDirs or (fileType == 'dir' and not re.search("BOOKROOT", thisChapter)): 
        chapters2UrlsDict[thisChapter][subUrl] = subUrl

      if fileType == 'page' and subUrl not in chapters2UrlsDict[thisChapter]:
        chapters2UrlsDict[thisChapter][subUrl] = subUrl

## return unknown dir or page for use by doFile(path)
def tocMimer(path):
    ret = 'unknown' 
    types = [".htm"]
    filePath, suffix = os.path.splitext(path)

    found = re.search("Wiggler",os.path.basename(path))
    if os.path.isdir(path):
        ret = 'dir' 
    elif suffix in types:
        ret = 'page' 

    return (ret)

## chapters2UrlsDict may or may not already be populated
## this routine examines the file system and adds any valid
## new *.htm files (book pages) not already in the dictionary
## This needs a way to add arbitraty new Galleries yet.
## right now Gallery has to be hand edited into an existing p2n file
def checkForFileChanges(path):

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
        joined = os.path.join(path, file).replace(args.delFromPath, '')
        skipRoboresources = re.search("roboresources", file)
        if skipRoboresources:
            continue
        doFile(joined)

    for directory in dirs:
        if directory[0] == '.':
            continue
        directory = re.sub("/$","",directory)
        
        newpath = os.path.join(path, directory)
        skipRoboresources = re.search("roboresources", newpath)
        if skipRoboresources:
            continue
        doFile(newpath)
        checkForFileChanges(newpath)

readExistingP2nFile(rootPath + 'p2n')
checkForFileChanges(rootPath)
p2nFileSave('write')
