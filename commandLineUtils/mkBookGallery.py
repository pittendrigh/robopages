#!/usr/bin/python

import sys
import argparse
import os
import re
import mimetypes
import fnmatch
import lxml.html
from pathlib import Path
from bs4 import BeautifulSoup
from lxml import etree

def doHtmFile(imgFilename, htmFile):
    global imgName2HtmDictionary
    imgName2HtmDictionary[imgFilename] = htmFile

def collectHtmNames(path):
    typeFs = []
    for name in os.listdir(path):
        checkThis = os.path.join(path, name)
        
        if os.path.isdir(checkThis):
            continue
        else:
            typeFs.append(name)

    pattern = '*.htm'
    for file in typeFs:
        if fnmatch.fnmatch(file,pattern):
          htmNames.append(file )

rootPath = os.getcwd() + "/"
imgName2HtmDictionary = {}
htmNames = []

parser = argparse.ArgumentParser(description='Create a recursive p2n file')
parser.add_argument("--prepath", default="Library/Flies/")
args = parser.parse_args()

collectHtmNames(rootPath)

os.makedirs("roboresources/galleryMode", exist_ok=True)

fp = open ('roboresources/galleryMode/chapterImages', "w")
fp.close()
for htmFile in htmNames:
  with open(htmFile) as fp:
    soup = BeautifulSoup(fp, features="lxml")
  images = soup.findAll('img')
  fp = open ('roboresources/galleryMode/chapterImages', "a")
  for image in images:
      print (image['src'] + '|' + htmFile)
      fp.write(image['src'] + '|' + htmFile + "\n")



