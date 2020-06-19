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

      # if not printing any BOOKROOT line why do we have them?
      # debug this at some point
      #if not re.search('BOOKROOT',line):
      line = line.replace("BOOKROOT","")
      if line.startswith('/'):
        line = line[1:]
      if line.endswith('/'):
        line = line[:-1]

      #print (line)
      if mode == 'write':
            fp.write(line + "\n") 

  if mode == 'write':
    fp.close()
#======end output functions


def readExistingP2N(filepath):
    global args, chapterUrlsDictionary

    #print ("readExisting")
    try:
        Path(filepath).touch()
        fp = open(filepath, "r")
    except:
        print("No fp to open on p2n file")

    Lines = fp.readlines()
    for thisPath in Lines:
        thisPath = thisPath.strip()
        print("read thisPath " + thisPath)
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

def doDir(dirPath):
#  found = re.search('Gallery', dirPath)
#  if found:
#    print()
#    print ("dddddddddoDir " + dirPath)

  #print("dirPath: " + dirPath)
  #if dirPath.count('/') < 1 and dirPath not in chapterUrlsDictionary.keys():
  #  print("dirPath: " + dirPath)
  if os.path.basename(dirPath) not in chapterUrlsDictionary.keys():
      chapterUrlsDictionary[os.path.basename(dirPath)] = {}
  doFile(dirPath)

def doFile(filePath):
  
    #filePath = re.sub("BOOKROOT","",filePath)
    filePath = filePath.replace("//","/").strip()
    filePath = re.sub("^/","",filePath)
    fileType = tocMimer(filePath)
    #if fileType in ['dir','page']:
    if fileType == 'page':
      thisChapter = getChapterName(filePath).strip()
      #print("chapter: " + thisChapter)
      subUrl = getSubUrl(filePath,thisChapter).strip()
      subUrl = re.sub("^/","",subUrl)
      subUrl = re.sub("/$","",subUrl)
      #if fileType == 'dir':
      #  print ("doFile subUrl: " + subUrl)

      if thisChapter not in chapterUrlsDictionary.keys():
         chapterUrlsDictionary[thisChapter] = {}

      if subUrl not in chapterUrlsDictionary[thisChapter]:
        chapterUrlsDictionary[thisChapter][subUrl] = subUrl
       

def tocMimer(path):
    ret = 'unknown' 
    #print (os.getcwd() + "/" + path)
    #if os.path.isdir(os.getcwd() + "/" + path) == True:
    #    ret = 'dir' 

    types = [".htm"]
    filePath, suffix = os.path.splitext(path)
    if suffix in types:
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
        directory = re.sub("/$","",directory)
        
        newpath = os.path.join(path, directory)
        skipRoboresources = re.search("roboresources", newpath)
        if skipRoboresources:
            continue
        doDir(newpath)
        recurseDirs(newpath)


# an existing p2n may not exist
# chapterUrlsDictionary['BOOKROOT'] = {} 
#readExistingP2N(rootPath + 'p2n')
recurseDirs(rootPath)

#dbgChapterNames()
#dbgChapterNames()
processChapterUrlsDictionary('write')
