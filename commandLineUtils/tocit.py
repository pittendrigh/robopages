#!/usr/bin/python

import sys
import argparse
import os
import re
import mimetypes
from pathlib import Path
rootPath = os.getcwd() + "/"
chapterUrlsDictionary = {}

parser = argparse.ArgumentParser(description='Create a recursive p2n file')
parser.add_argument( "--delFromPath", default= "")
args = parser.parse_args()
if not args.delFromPath:
  args.delFromPath = os.getcwd() + '/'

# ======= debugging functions
def dbgChapterNames():
  global chapterUrlsDictionary
  
  for chapterName in chapterUrlsDictionary.keys():
    print (chapterName)
  print ("end dbgChaperNames \n\n")
#============ end debugging functions

def processChapterUrlsDictionary(mode):
  global chapterUrlsDictionary

  fp = None
  if mode == 'write':
    fp = open('np2n', "w")

  for chapterName in chapterUrlsDictionary.keys():
    if mode == 'write' and not re.search('BOOKROOT',chapterName):
      fp.write(chapterName.rstrip('//') + "\n")
    for subUrl in chapterUrlsDictionary[chapterName].keys():
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


### needs same noPageDirs deal as recurse?
## same general logic?
# why recreate it?  Must be a way to factor the business lines
# and use both ways
def readExistingP2N(filepath):
    global args, chapterUrlsDictionary

    try:
        Path(filepath).touch()
        fp = open(filepath, "r")
    except:
        print("No fp to open on p2n file")

    Lines = fp.readlines()
    for thisLine in Lines:
        thisPath = thisLine.strip()
        doFile(thisPath)
 
    fp.close()

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

def getSubUrl(path, thisChapter):
  global args

  subUrl = ''
  path = path.replace(args.delFromPath,'')  
  subUrl = path.replace(thisChapter,'') 

  return(subUrl.strip())

#yyy
def doFile(path):
 

    ##filePath = path.replace("//","/").strip()
    subUrl = ''
    filePath = path  
    filePath = filePath.replace(args.delFromPath,"")
    fileType = tocMimer(path).strip()

    thisChapter = getChapterName(filePath).strip()
    print(thisChapter + " " + path + " " + fileType)
 
    if fileType == 'page' or fileType == 'dir':
      subUrl = getSubUrl(filePath,thisChapter).strip()
      subUrl = re.sub("^/","",subUrl)
      subUrl = re.sub("/$","",subUrl)
      print(thisChapter + " " + fileType + " " + path + " [" + subUrl + "]") 

      if thisChapter not in chapterUrlsDictionary.keys():
        chapterUrlsDictionary[thisChapter] = {}

      ## xxxx a Gallery has only images, so it contains  no *.htm file to 
      ## trigger subUrl save 
      noPageDirs = ["Gallery"]
      if os.path.basename(subUrl) in noPageDirs or (fileType == 'dir' and not re.search("BOOKROOT", thisChapter)): 
        chapterUrlsDictionary[thisChapter][subUrl] = subUrl

      if fileType == 'page' and subUrl not in chapterUrlsDictionary[thisChapter]:
        chapterUrlsDictionary[thisChapter][subUrl] = subUrl

       

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
        recurseDirs(newpath)


# an existing p2n may not exist
# chapterUrlsDictionary['BOOKROOT'] = {} 
readExistingP2N(rootPath + 'p2n')
recurseDirs(rootPath)

#dbgChapterNames()
processChapterUrlsDictionary('write')
