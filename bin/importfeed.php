#!/usr/bin/env php
<?php

$force = in_array("-f", $argv, true);

function strip_and_clean($string)
{
    $string = strip_tags($string);
    $string = str_ireplace('&nbsp;', ' ', $string);

    return $string;
}

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
title: %TITLE%
subtitle: %SUBTITLE%
number: %NUMBER%
date: %PUB_DATE%
guid: %GUID%
embed_url: %EMBED_URL%
rss_url: %RSS_URL%
download_url: %DOWNLOAD_URL%
duration: %DURATION%
file_size: %FILE_SIZE%
explicit: %EXPLICIT%
#bg_image: /assets/...
#image_credit:
#    url:
#    description:
#    by:

---
{% block content %}
%SUMMARY%
{% endblock %}
{% block itunes_summary %}
%SUMMARY_STRIPPED%
{% endblock %}

EOS;

$targetDir = __DIR__."/../source/_episodes";
$feedUrl = "http://simplecast.fm/podcasts/1301/rss";
$feed = simplexml_load_file($feedUrl);
$feed->registerXPathNamespace("itunes", "http://www.itunes.com/dtds/podcast-1.0.dtd");
$feed->registerXPathNamespace("atom", "http://www.w3.org/2005/Atom");

# these were published on the site later than the media hosting
$skip = [
    "https://www.signalleaf.com/podcasts/That-Podcast/552bde5cddc93203005716dd",
    "https://www.signalleaf.com/podcasts/That-Podcast/55bfe01315405a030032d8cd",
];

foreach ($feed->channel->item as $item) {

    if (in_array((string) $item->guid, $skip)) {
        continue;
    }

    $itunes = $item->children('itunes', true);

    $pubDate = new \DateTime($item->pubDate);
    $filename = sprintf("%s/%s-%s.html", $targetDir, $pubDate->format("Y-m-d"), slugify($item->title));

    if ($force || !file_exists($filename)) {

        preg_match('/^Episode\s+(\d+):\s*(.*?)$/', (string) $item->title, $matches);

        if (count($matches) < 3) {
            print "Title '".(string) $item->title."' does not match expected pattern\n";
            continue;
        }

        list ($dummy, $number, $title) = $matches;

        $guid = (string) $item->guid;

        $id = substr($guid, strrpos($guid, '/')+1);

        $rssUrl = str_replace('https://', 'http://', (string) $item->enclosure['url']);

        $urlFilename = substr($rssUrl, strrpos($rssUrl, '/')+1);


        $templateData = [
            "%TITLE%" => $title,
            "%NUMBER%" => $number,
            "%SUBTITLE%" => (string) $itunes->subtitle,
            "%PUB_DATE%" => $pubDate->format("r"),
            "%SUMMARY%" => (string) $itunes->summary,
            "%SUMMARY_STRIPPED%" => strip_and_clean((string) $itunes->summary),
            "%GUID%" => $guid,
            "%EMBED_URL%" => $rssUrl,
            "%RSS_URL%" => $rssUrl,
            "%DOWNLOAD_URL%" => $rssUrl,
            "%DURATION%" => (string) $itunes->duration,
            "%FILE_SIZE%" => (string) $item->enclosure['length'],
            '%EXPLICIT%' => (string) $itunes->explicit,
        ];

        $output = strtr($template, $templateData);

        file_put_contents($filename, $output);

        echo "Wrote to $filename\n";
    }
}
