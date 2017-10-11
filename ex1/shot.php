<?php

// read files
$contentNaive = file_get_contents("Naive.txt");
$contentImproved = file_get_contents("Improved.txt");

// split lines
$contentNaive = explode("\n", str_replace("\r\n", "\n", $contentNaive));
$contentImproved = explode("\n", str_replace("\r\n", "\n", $contentImproved));

$width = 800;
$height = 600;

$image = imagecreatetruecolor($width, $height);
$background = imagecolorallocate($image, 255, 255, 255);
imagefill($image, 0, 0, $background);
$blue = imagecolorallocate($image, 0, 0, 255);
$green = imagecolorallocate($image, 0, 255, 0);
$red = imagecolorallocate($image, 255, 0, 0);

// iterate threshold from 0.1 to 0.9
$cc = 0;
$best = array(0, 0);
$bestAcc = array(0, 0);
for ($threshold = 0.1; $threshold <= 0.901; $threshold += 0.05, $cc++) {
  echo "Threshold set to $threshold\n";
  
  $result = calculatePositives($contentNaive, $threshold, sizeof($contentNaive));
  $limitCount = $result[0];
  $tpr = $limitCount[0] / $result[1];
  $fpr = $limitCount[3] / $result[2];
  $acc = ($limitCount[0] + $limitCount[2]) / ($result[1] + $result[2]);
  echo "Accuracy Naive: " . $acc . "\n";
  if ($acc > $bestAcc[0]) {
    $best[0] = $threshold;
    $bestAcc[0] = $acc;
  }
  drawCircle($fpr, $tpr, $blue);
  
  $result = calculatePositives($contentImproved, $threshold, sizeof($contentImproved));
  $limitCount = $result[0];
  $tpr = $limitCount[0] / $result[1];
  $fpr = $limitCount[3] / $result[2];
  $acc = ($limitCount[0] + $limitCount[2]) / ($result[1] + $result[2]);
  echo "Accuracy Improved: " . $acc . "\n";
  if ($acc > $bestAcc[1]) {
    $best[1] = $threshold;
    $bestAcc[1] = $acc;
  }
  drawCircle($fpr, $tpr, $green);
}

imageline($image, $width, 0, 0, $height, $red);
imagepng($image, "plot.png");

echo "Best Threshold for Naive: " . $best[0] . " has accuracy " . $bestAcc[0] . "\n";
echo "Best Threshold for Improved: " . $best[1] . " has accuracy " . $bestAcc[1] . "\n";

function drawCircle($fpr, $tpr, $color) {
  global $image, $width, $height;
  
  imagefilledellipse($image, (int)($fpr * $width), (int)($height - $tpr * $height), 10, 10, $color);
}

function calculatePositives($content, $threshold, $limit) {
  $count = array(0, 0, 0, 0); // tp, fn, tn, fp
  $retCount = null;
  $P = 0;
  $N = 0;
  
  $i = 0;
  foreach ($content as $line) {
    if (strlen($line) == 0) {
      continue; // skip empty lines
    }
    $line = explode("\t", $line);
    if (sizeof($line) != 2 || !is_numeric($line[0])) {
      continue; // skip wrong lines
    }
    $i++;
    
    $similarity = floatval($line[0]);
    $isShot = ($line[1] === "shot") ? true : false;
    if ($isShot) {
      $P++;
    }
    else {
      $N++;
    }
    
    if ($similarity < $threshold && $isShot) {
      $count[0]++; // true positive
    }
    else if ($similarity >= $threshold && $isShot) {
      $count[1]++; // false positive
    }
    else if ($similarity < $threshold) {
      $count[3]++; // false negative
    }
    else {
      $count[2]++; // true negative
    }
    
    if ($i < $limit) {
      $retCount = $count;
    }
  }
  return array($retCount, $P, $N);
}



