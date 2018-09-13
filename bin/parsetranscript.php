#!/usr/bin/env php
<?php

while($f = fgets(STDIN)){
    if (preg_match('/^(.+?):\s+([\d]{2}:[\d]{2})\s+([\S].*)$/', $f, $matches)) {
        list ($junk, $speaker, $time, $content) = $matches;
        echo <<<CHUNK
<p class="transcript">
    <span class="transcript__speaker">$speaker</span>
    <span class="transcript__time">$time</span>
    <span class="transcript__content">$content</span>
</p>

CHUNK;
    }
}
