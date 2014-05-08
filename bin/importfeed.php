#!/usr/bin/env php
<?php

$force = in_array("-f", $argv, true);

function slugify($string) {
    $slug = preg_replace('~[^\\pL\d]+~u', '-', $string);
    $slug = trim($slug, '-');
    $slug = iconv('utf-8', 'us-ascii//TRANSLIT', $slug);
    $slug = strtolower($slug);
    $slug = preg_replace('~[^-\w]+~', '', $slug);

    if ("" === $slug) {
        return 'n-a';
    }

    return $slug;
};

$template = <<<EOS
---
layout: episode
title: %TITLE%
description: %SUBTITLE%
tags:
date: %PUB_DATE%
---

%SUBTITLE%

%SHOW_NOTES%

<iframe 
src="%GUID_URL%" 
width="500" height="70" frameborder="0"></iframe>

EOS;

$targetDir = __DIR__."/../source/_episodes";
$feedUrl = "http://media.signalleaf.com/That-Podcast/rss";
$feed = simplexml_load_file($feedUrl);
$feed->registerXPathNamespace("itunes", "http://www.itunes.com/dtds/podcast-1.0.dtd");
$feed->registerXPathNamespace("atom", "http://www.w3.org/2005/Atom");

foreach ($feed->channel->item as $item) {
    $itunes = $item->children('itunes', true);

    $pubDate = new \DateTime($item->pubDate);
    $filename = sprintf("%s/%s-%s.md", $targetDir, $pubDate->format("Y-m-d"), slugify($item->title));

    if ($force || !file_exists($filename)) {
        
        $templateData = [
            "%TITLE%" => (string) $item->title,
            "%SUBTITLE%" => (string) $itunes->subtitle,
            "%PUB_DATE%" => $pubDate->format("r"),
            "%SHOW_NOTES%" => (string) $itunes->summary,
            "%GUID_URL%" => (string) $item->guid,
        ];

        $output = strtr($template, $templateData);

        file_put_contents($filename, $output);

        echo "Wrote to $filename\n";
    }
}
