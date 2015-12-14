<?php

$contents = file_get_contents('php7.spec');
$contents = preg_replace_callback('/\s+%description (.*)$/m', function(array $match) {
print_r($match);
  return "\nObsoletes:      php5-" . $match[1] . $match[0];
}, $contents);
file_put_contents('php7.spec', $contents);
