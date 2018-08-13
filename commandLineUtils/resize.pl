#!/usr/bin/perl

use Image::Size;

$pic = shift;
$max = shift;
$max = 400 if !$max;
#print "pic: ", $pic, "\n";
#print "max: ", $max, "\n\n";

my ($x, $y) = imgsize($pic);

$factor=$bumer=10000;
if ($x > $max)
{
 $factor = int(($max/$x)*100);
 $bumper = $factor."%";
}
#elsif ($y>$max){
# $factor = int(($max/$y)*100);
# $bumper = $factor."%";
#}


if($factor < 100)
{
  #print "pic $pic\n";
  #print "x==$x\n"; 
  #print "y==$y\n"; 
  #print "factor: $factor\n";
  #print $bumper, "\n";
  $ppic = "/tmp/" . $pic;
  print "convert -geometry ". $bumper."x"."$bumper $pic $ppic\n";
  ##`convert -geometry ". $bumper."x". "$bumper $pic $ppic`;
  `convert $pic -resize $bumper $pic`;
  ##system("cp $ppic $pic");
}
