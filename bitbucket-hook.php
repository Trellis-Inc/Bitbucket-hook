<?php
$repo_dir       = '/full/path/to/bare/repo.git';
$web_root_dir   = '/full/path/to/web/root';
$branch         = 'branch_name';

// Full path to git binary is required if git is not in your PHP user's path. Otherwise just use 'git'.
$git_bin_path = '/usr/bin/git';

function checkout($commit_hash)
{
    global $repo_dir, $web_root_dir, $git_bin_path, $branch;

    // Do a git checkout to the web root
    shell_exec('cd ' . $repo_dir . ' && ' . $git_bin_path  . ' fetch');
    shell_exec('cd ' . $repo_dir . ' && GIT_WORK_TREE=' . $web_root_dir . ' ' . $git_bin_path  . ' checkout -f');
    shell_exec('cd ' . $repo_dir . ' && GIT_WORK_TREE=' . $web_root_dir . ' GIT_DIR=' .$repo_dir . ' ' . $git_bin_path  . ' pull origin ' . $branch);

    file_put_contents('deploy.log', date('m/d/Y h:i:s a') . " Deployed branch: " .  $branch . " Commit: " . $commit_hash . "\n", FILE_APPEND);
}


$update = false;

// Parse data from Bitbucket hook payload
$payload = json_decode(file_get_contents("php://input"));

$push = $payload->push;
$pullrequest = $payload->pullrequest;

if (!empty($push) && !empty($push->changes[0])) {
    $new = $push->changes[0]->new;
    if (!empty($new)) {
        if ($new->name === $branch) {
            checkout($new->target->hash);
        }
    }
} elseif (!empty($pullrequest) && strtoupper($pullrequest->state) == 'MERGED') {
    $destination = $pullrequest->destination;
    if (!empty($destination)) {
        if ($destination->branch->name === $branch) {
            checkout($destination->commit->hash);
        }
    }
}
