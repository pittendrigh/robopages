#!/usr/bin/python

#### buggy development code as of 3/14/2020

import sys
import argparse
import os
import re
import mimetypes

page = 1
rootPath = os.getcwd() + "/"
page2numDictionary = {}
dirPath2ListDictionary = {}
orderedPaths = []
parser = argparse.ArgumentParser(description='Create a recursive p2n file')
parser.add_argument("--prepath", default="")
args = parser.parse_args()

def processdirPath2ListDictionary():
    #for pathUrl, myList in dirPath2ListDictionary.items():
    #for pathUrl in dirPath2ListDictionary.keys():
    for pathUrl in orderedPaths:
        pathUrl = pathUrl.strip()
        subUrl = dirPath2ListDictionary[pathUrl] 
        for subUrl in dirPath2ListDictionary[pathUrl]:
          displayLine = pathUrl + '/' + subUrl
          print(displayLine.replace('theRoot/','') )

def readExistingP2NFile(filepath):
    try:
        fp = open(filepath)
    except:
        print("No fp open on p2n file")
        exit
    line = fp.readline()
    cnt = 1
    while line:
        thisDir = ''
        subUrl = ''
        line = line.strip()
        #print("line: ", line)
        slashesCount = line.count('/')
        if slashesCount < 1:
            thisDir = 'theRoot'
            subUrl = line
        else        :
            dirs = line.split('/')
            thisDir = dirs[0]
            subUrl = '/'.join(dirs[1:]) 

        if not thisDir in dirPath2ListDictionary:
          orderedPaths.append(thisDir)

        try:
             dirPath2ListDictionary[thisDir].append(subUrl)
        except:
             dirPath2ListDictionary[thisDir] = []
             dirPath2ListDictionary[thisDir].append(subUrl)


        #print("making: dirPath2ListDictionary[" + thisDir + "] = " +  subUrl)

        line = fp.readline()
        cnt += 1
    fp.close()


def writeNewP2N():
    pageNum = 1
    f = open("p2n", "w")
    for key in page2numDictionary:
        prepath = args.prepath
        urlpath = prepath + key.replace(rootPath, '')

        #are there an .git like files here
        # perhaps we want to short circuit this sooner, in doDir or doFile
        skipRoboresources = re.search("^\.", urlpath)
        if skipRoboresources:
            continue
        booleanBunt = tocMimer(key)
        if not booleanBunt:
            continue
        skipRoboresources = re.search("roboresources", urlpath)
        if not skipRoboresources:
            ##print(urlpath)
            f.write(urlpath + "\n")
            pageNum = pageNum + 1
    f.close()


def doFile(indent, filename):
    global page, page2numDictionary

    page2numDictionary[filename] = page
    page = page + 1


def tocMimer(path):
    ret = False
    if os.path.isdir(path) == True:
        ##deal with roboresources???
        ret = True

    types = [
        ".jpg", ".JPG", ".JPEG", ".png", ".gif", ".htm", ".html", ".tgz",
        ".zip", ".pdf", ".smil", ".xml", "xhtml"
    ]
    filename, suffix = os.path.splitext(path)
    if suffix in types:
        ret = True

    return (ret)


def doDir(path):
    if path[0] == '.':
        return ''

    indent = ''
    slashes = path.count('/') - 2
    for x in range(slashes):
        indent = indent + '\t'
    typeFs = []
    dirs = []
    for name in os.listdir(path):
        if name[0] == '.':
            continue
        checkThis = os.path.join(path, name)
        #print ("checkThis: ", checkThis)
        if os.path.isdir(checkThis):
            dirs.append(name)
            #print("dir: ", checkThis)
        else:
            typeFs.append(name)
            #print("typeF: ", checkThis)

    for file in typeFs:
        if file[0] == '.':
            continue
        joined = os.path.join(path, file)
        skipRoboresources = re.search("roboresources", joined)
        if skipRoboresources:
            continue
        doFile(indent, joined)

    for directory in dirs:
        if directory[0] == '.':
            continue
        newpath = os.path.join(path, directory)
        skipRoboresources = re.search("roboresources", newpath)
        if skipRoboresources:
            continue
        doFile(indent, os.path.join(path, directory))

        doDir(newpath)


readExistingP2NFile(rootPath + '/p2n')
processdirPath2ListDictionary()

#doDir(rootPath)
#writeNewP2N()
