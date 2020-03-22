#!/usr/bin/perl

use Cwd;

$here=getcwd();
$filename = $here  .  '/roboresources/galleryMode/chapterImages';
open(FILE, '<', $filename) or die $!;
while(<FILE>)
{
  chomp;
  ## /Uploads/test.jpg|wetflies.htm
  ($file = $_) =~ s/\|.*$//;
  print $file;
}
