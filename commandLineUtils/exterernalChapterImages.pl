#!/usr/bin/perl

open (FILE, '<roboresources/galleryMode/chapterImages') or die "sumbitch\n";
while(<FILE>)
{
  chomp;
  next if /roboresources/;

  ## /Uploads/test.jpg|wetflies.htm
  ($file = $_) =~ s/\|.*$//;
  print $file;
}
