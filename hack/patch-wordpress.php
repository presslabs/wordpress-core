#!/usr/bin/env php
<?php
$wp_dir = dirname( __DIR__ );
require_once $wp_dir . "/wp-includes/version.php";

define( 'PATCHES_DIR', __DIR__ . '/patches' );

function log_msg( $msg, $dest = STDERR ) {
    fwrite($dest, "$msg\n");
}

function run( $args, $dryRun = false, $passthru = true, &$lines = array() ) {
    $cmd = join(' ', array_map(escapeshellarg, $args));
    log_msg("+ $cmd");
    if ( !$dryRun ) {
        $last_line = "";
        if ( $passthru ) {
            passthru( $cmd, $return );
        } else {
            $last_line = exec( $cmd, $lines, $return );
        }
        if ( $return > 0 ) {
            if ( strlen($last_line) > 0 ) {
                log_msg($last_line);
            }
            exit( $return );
        }
        return $last_line;
    }
}

$patches = array(
    new Patch('oem_preload_5.0.diff', '5.0'),
    new Patch('stream_wrappers_5.0.diff', '5.0'),
);

class Patch {
    private $file;
    private $patchfile;
    private $min_version;
    private $max_version;

    public function __construct($file, $min_version = "", $max_version = "") {
        $this->file = $file;
        $this->patchfile = PATCHES_DIR . '/' . $this->file;
        $this->min_version = $min_version;
        $this->max_version = $max_version;
    }

    public function validate() {
        if ( !file_exists($this->patchfile) ) {
            log_msg("Patch {$this->file} doesn't exist!");
            return false;
        }
        return true;
    }

    public function shouldApply() {
        global $wp_version;
        if ( $this->min_version && version_compare( $wp_version, $this->min_version, '<' ) ) {
            return false;
        }
        if ( $this->max_version && version_compare( $wp_version, $this->max_version, '>=' ) ) {
            return false;
        }
        return true;
    }

    public function isApplied() {
        global $wp_dir;
        $cur_dir = getcwd();

        if ( chdir( $wp_dir ) === false ) {
            log_msg("Cannot change dir to $wp_dir: ". error_get_last());
            exit(1);
        }

        exec("patch -R -p1 -s -f --dry-run -i{$this->patchfile}", $out, $return);

        if ( chdir( $cur_dir ) === false ) {
            log_msg("Cannot change dir to $wp_dir: ". error_get_last());
            exit(1);
        }

        return ( $return == 0 );
    }

    public function apply() {
        global $wp_dir;
        if ( ! $this->shouldApply() ) {
            log_msg("+ Skipping {$this->file}...");
            return true;
        }
        if ( $this->isApplied() ) {
            log_msg("+ {$this->file} is already applied...");
            return true;
        }
        log_msg("+ Applying {$this->file}...");
        $cur_dir = getcwd();

        if ( chdir( $wp_dir ) === false ) {
            log_msg("Cannot change dir to $wp_dir: ". error_get_last());
            exit(1);
        }

        $cmd = "patch -p1 -i{$this->patchfile}";
        exec($cmd, $out, $return);
        if ( $return > 0 ) {
            log_msg("++ $cmd");
            foreach( $out as $line ) {
                log_msg("+++ $line");
            }
        }

        if ( chdir( $cur_dir ) === false ) {
            log_msg("Cannot change dir to $wp_dir: ". error_get_last());
            exit(1);
        }

        return ( $return == 0 );
    }
}

foreach( $patches as $patch ) {
    if ( ! $patch->validate() ) {
        exit(1);
    }
}

log_msg("Patching WordPress $wp_version");

foreach( $patches as $patch ) {
    if ( ! $patch->apply() ) {
        exit(1);
    }
}
