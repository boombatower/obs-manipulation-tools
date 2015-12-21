<?php

// detect and clean .spec files basic run

const PROJECT = 'server:php:applications';
const PROJECT_DEVEL = 'home:boombatower:branches:' . PROJECT;
// const PROJECT_DEVEL = 'home:boombatower:branches:' . PROJECT . '2';
const CHANGE_MESSAGE = 'Utilize php version agnostic packages to be compatible with php7.';
// const MAX = 41;
// const MAX = 3;
// const MAX = 150;
const MAX = 1000;

const CHANGE_MESSAGE2 = 'Provide version agnostic virtual package.';
// const CHANGE_MESSAGE3 = 'Handle missing .depdb and .depdblock files in Factory builds.';
const CHANGE_MESSAGE3 = 'Merge peardir cleanup into single non-failing rm command.';
const CHANGE_MESSAGE4 = 'Cleanup: remove PreReq and left over sles_version condition.';

exec('grep -nR php5 --include *.spec --exclude-dir *.osc* .', $lines, $ret);
// exec('grep -nR "/\.depdb" --include *.spec --exclude-dir *.osc* .', $lines, $ret);

if ($ret !== 0) {
  die("failed to grep...\n");
}

// $count = 0;
$packages = [];
$channel = [ // prioritize since nothing will build without these
'php5-pear-channel-doctrine',
'php5-pear-channel-domain51',
'php5-pear-channel-ezno',
'php5-pear-channel-horde',
'php5-pear-channel-htmlpurifier',
'php5-pear-channel-netpirates',
'php5-pear-channel-nrk',
'php5-pear-channel-pdepend',
'php5-pear-channel-pearhub',
'php5-pear-channel-pearplex',
'php5-pear-channel-phing',
'php5-pear-channel-phpontrax',
'php5-pear-channel-phpunit',
'php5-pear-channel-swift',
'php5-pear-channel-symfony',
'php5-pear-channel-symfony2',
'php5-pear-channel-xinc',
];
foreach ($channel as $package) {
  $packages[$package] = true;
}

foreach ($lines as $line) {
  list($path, ) = explode(':', $line, 2);
  $package = trim(dirname($path), './');
  $packages[$package] = true;
//   break; // TODO For testing.

//   if (++$count >= MAX) {
  if (count($packages) >= MAX) {
    echo "Stopping since MAX reached...\n";
    break;
  }
}

$packages = array_keys($packages);
// print_r($packages);
echo count($packages) . " package(s) to process...\n";

foreach ($packages as $package) {
  echo "Branching $package...\n";
//   continue; // TODO
  if (is_dir('../' . PROJECT_DEVEL . '/' . $package)) continue; // already branched and checked out
  passthru('osc branch ' . implode(' ', [
    PROJECT,
    escapeshellarg($package),
    PROJECT_DEVEL,
    escapeshellarg($package),
  ]), $return_var);
//   if ($return_var === 0) {
    passthru('cd .. && osc co ' . PROJECT_DEVEL . ' ' . escapeshellarg($package));
//   }
}

// chdir('../' . PROJECT_DEVEL);
// passthru('cd ../' . PROJECT_DEVEL . ' && osc up');

// Make the changes.

$submitted = 0;
foreach ($packages as $package) {
  echo "Processing $package...\n";

// true: process, false: submit
if (false) {
// if (true) {
  do_replace($package, CHANGE_MESSAGE, function($package, &$contents) {
    $contents = preg_replace_callback('/^(\w*Requires: \s+)php5([^\s\d]*)(\s)/m', function(array $match) {
      return $match[1] . 'php' . strtolower($match[2]) . $match[3];
    }, $contents);
  });

  do_replace($package, CHANGE_MESSAGE2, function($package, &$contents) {
    // php5-* package then check to see it provides php-* version of itself
    if (!preg_match('/^php5[^\d]/', $package)) {
      echo "- package does not start with php5...\n";
      return;
    }

    $agnostic = str_replace('php5-', 'php-', $package);
    if (preg_match('/Provides:\s+' . $agnostic . '/', $contents)) {
      echo "- already provides agnostic virtual package for $package...\n";
      return;
    }

    $contents = preg_replace_callback('/\s+%description/m', function(array $match) use ($agnostic) {
      return "\nProvides:       " . $agnostic . $match[0];
    }, $contents, 1);
  });

  do_replace($package, CHANGE_MESSAGE4, function($package, &$contents) {
    // perhaps make cleanup change for prereq and php53.
    // Remove any prereq which mess up SLE builds (ex php5-pear-channel-phpontrax)
    $contents = preg_replace_callback('/^(PreReq:\s+)php5.*$\n/m', function(array $match) {
      return '';
    }, $contents);

    $contents = preg_replace_callback('/%if\s+0%{\?sles_version}\s*[><=]*\s*\d+(\s+[^:]+:\s*php53[^\n]*)+\s+%else(\s+[^%]+\s+)%endif/m', function(array $match) {
      return $match[2];
    }, $contents);
  });

// if (false) {
// if (true) {
//   do_replace($package, CHANGE_MESSAGE3, function($package, &$contents) {
// //     $once = false;
// //     $contents = preg_replace_callback('/^(rm|%{__rm})\s+(-[rf]+\s+)?(%{buildroot}\/?%{(php_)?peardir}\/\.(filemap|lock|registry|channels|depdb|depdblock)$\n)/m', function(array $match) {
//     $contents = preg_replace_callback('/^(rm|%{__rm})\s+(-[rf]+\s+)?(%{buildroot}\/?%{(php_)?peardir}\/\.(filemap|lock|registry|channels|depdb|depdblock)$\n)/m', function(array $match) {
//     static $once = false;
//       if ($once) return '';
//       $once = true;
//       return "%{__rm} -rf %{buildroot}%{{$match[4]}peardir}/.{filemap,lock,registry,channels,depdb,depdblock}\n";
// //       return $match[1] . ' -f ' . $match[2];
//     }, $contents);
//   });
  }
  else {
    $devel_package = '../' . PROJECT_DEVEL . '/' . $package;
    $message = CHANGE_MESSAGE . '\|' . CHANGE_MESSAGE2 . '\|' . CHANGE_MESSAGE4;
//     if (trim(shell_exec('grep ' . escapeshellarg(CHANGE_MESSAGE3) . ' ' . escapeshellarg($devel_package . '/' . $package . '.changes')))) {
    if (trim(shell_exec('grep ' . escapeshellarg($message) . ' ' . escapeshellarg($devel_package . '/' . $package . '.changes')))) {
      if (trim($diff = shell_exec('cd ../' . PROJECT_DEVEL . '/' . escapeshellarg($package) . ' && ' .
        'osc diff --link')) &&
        strpos(trim(shell_exec('cd ../' . PROJECT_DEVEL . '/' . escapeshellarg($package) . ' && ' .
        'osc request list')), 'No results for ') !== false) {
        file_put_contents('changes.diff', $diff, FILE_APPEND);
//         echo "$diff";
    // if osc diff --link (hasn't been submitted)
    // if request doesn't already exist (osc request list -> is empty).
      echo "Submitting changed package $package...\n";
//       passthru('cd ../' . PROJECT_DEVEL . '/' . escapeshellarg($package) . ' && ' .
//         'osc sr --cleanup --yes -m ' . escapeshellarg(CHANGE_MESSAGE3));
        $submitted++;
        continue;
      }
    }
    echo "NOT submitting package $package...\n";
  }
}
if ($submitted)
  var_dump($submitted);

function do_replace($package, $message, $callback) {
  // revert anything not committed which could be due to failed commit
  passthru('cd ../' . PROJECT_DEVEL . '/' . escapeshellarg($package) . ' && ' .
    'osc revert .');

  $devel_package = '../' . PROJECT_DEVEL . '/' . $package;
  if (trim(shell_exec('grep ' . escapeshellarg($message) . ' ' . escapeshellarg($devel_package . '/' . $package . '.changes')))) {
    echo "Skipping since already completed $package...\n";
    return;
  }

  $spec_file = "$package/$package.spec";
  $contents = file_get_contents('../' . PROJECT_DEVEL . '/' . $spec_file);

  $callback($package, $contents);

  file_put_contents('../' . PROJECT_DEVEL . '/' . $spec_file, $contents);

  if (!trim(shell_exec('cd ../' . PROJECT_DEVEL . '/' . escapeshellarg($package) . ' && ' .
    'osc diff'))) {
    echo "Skipping since no changes for $package...\n";
    return;; // no changes then move on
  }

  $changes = file_exists('../' . PROJECT_DEVEL . '/' . "$package/$package.changes");
  passthru('cd ../' . PROJECT_DEVEL . '/' . escapeshellarg($package) . ' && ' .
//     'osc diff | wc -l && ' . // check for diff before vc
    'osc vc -m ' . escapeshellarg($message) . ' && ' .
    ($changes ? '' : 'osc add ' . escapeshellarg("$package.changes") . ' && ') .
    'osc diff && ' .
    'osc commit --noservice -m ' . escapeshellarg($message));
}
