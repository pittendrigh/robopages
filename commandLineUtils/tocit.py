#!/usr/bin/python
### Library/Flies at beginning of each
### first dir Library/Flies not appearing although subsequents do
###
import sys
import argparse
import os
import re
import mimetypes

page = 1
rootPath = os.getcwd() + "/"
page2numHash = {}
parser = argparse.ArgumentParser(description='uga buga')
parser.add_argument("--prepath",default="")
args = parser.parse_args()

def processP2N():
    pageNum = 1
    f = open("p2n", "w")
    for key in page2numHash:
        prepath = args.prepath
        urlpath =  prepath + key.replace(rootPath, '')

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
            print(urlpath)
            f.write(urlpath + "\n")
            pageNum = pageNum + 1
    f.close()


def doFile(indent, filename):
    global page, page2numHash

    page2numHash[filename] = page
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




doDir(rootPath)
processP2N()
