<?php 

namespace WPSEEDE;

class Mods_Installer 
{
    const MODS_ARCHIVE_URL = 'https://github.com/oboyda/wpseed-mods/archive/refs/heads/master.zip';
    const MODS_ARCHIVE_EXTR_DIR = 'wpseed-mods-master';

    protected $args;
    protected $base_dir;
    protected $load_modules;

    protected $mods_dir;
    protected $mods_dir_a;
    protected $mods_path_a;

    protected $filesys;

    protected $log;

    public function __construct($args)
    {
        $this->args = wp_parse_args($args, [
            'base_dir' => __DIR__,
            'load_modules' => []
        ]);
        $this->base_dir = $this->args['base_dir'];
        $this->load_modules = $this->args['load_modules'];

        $this->mods_dir = $this->base_dir . '/mods';
        $this->mods_dir_a = $this->mods_dir . '/__mods-archive';
        $this->mods_dir_e = $this->mods_dir . '/' . self::MODS_ARCHIVE_EXTR_DIR;
        $this->mods_path_a = $this->mods_dir . '/__mods-archive.zip';

        $this->log = [
            'installed' => [],
            'updated' => []
        ];

        add_action('init', [$this, 'doConsole']);
    }

    public function doConsole()
    {
        // if(!current_user_can('manage_options'))
        // {
        //     echo 'Permission denied';
        //     exit;
        // }

        $opts = getopt("a:");

        if(isset($opts['a']))
        {
            switch($opts['a'])
            {
                case 'install_mods':
                    $this->initInstallMods(false);
                break;
                case 'update_mods':
                    $this->initInstallMods(true);
                break;
            }
        }
    }

    public function initInstallMods($is_update=false)
    {
        $this->setFilesystem();

        if(!isset($this->filesys))
        {
            return;
        }

        $this->downloadModsArchive();
        $this->extractModsArchive();
        $this->installMods($is_update);
        $this->printLogs();
    }

    protected function setFilesystem()
    {
        global $wp_filesystem;

        if(!isset($wp_filesystem))
        {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            if(function_exists('WP_Filesystem'))
            {
                WP_Filesystem();
                $this->filesys = $wp_filesystem;
            }
        }
    }

    protected function downloadModsArchive()
    {
        if(!$this->filesys->exists($this->mods_dir))
        {
            $this->filesys->mkdir($this->mods_dir);
        }

        $resp = wp_remote_get(self::MODS_ARCHIVE_URL);
        $resp_body = wp_remote_retrieve_body($resp);
        $resp_code = wp_remote_retrieve_response_code($resp);

        if(
            $resp_code == 200 && 
            !empty($resp_body) && 
            $this->filesys->exists($this->mods_dir)
        ){
            return $this->filesys->put_contents($this->mods_path_a, $resp_body);
        }

        return false;
    }

    protected function extractModsArchive()
    {
        if(!($this->filesys->exists($this->mods_path_a) && class_exists('ZipArchive')))
        {
            return false;
        }

        $zip_archive = new \ZipArchive();

        if($zip_archive->open($this->mods_path_a) === true)
        {
            $zip_archive->extractTo($this->mods_dir);
            $zip_archive->close();

            if($this->filesys->exists($this->mods_dir_e))
            {
                // Delete mods archive dir if exists
                $this->rmDir($this->mods_dir_a);

                $this->filesys->move($this->mods_dir_e, $this->mods_dir_a);
            }

            // Delete mods archive file
            $this->filesys->delete($this->mods_path_a);
        }

        return false;
    }

    protected function installMods($is_update=false)
    {
        $mods_list = wpseed_get_dir_files($this->mods_dir, false, false);
        $mods_list_a = wpseed_get_dir_files($this->mods_dir_a, false, false);

        if(empty($mods_list_a))
        {
            return;
        }

        foreach($mods_list_a as $mod_name_a)
        {
            $mod_path_a = $this->mods_dir_a . '/' . $mod_name_a;
            $mod_path = $this->mods_dir . '/' . $mod_name_a;

            if(!(
                (is_array($this->load_modules) && in_array($mod_name_a, $this->load_modules))
                || $this->load_modules === 'all'
            )){
                continue;
            }

            $mod_config_path_a = $mod_path_a . '/mod.json';
            $mod_config_path = $mod_path . '/mod.json';

            $mod_config_a = $this->filesys->exists($mod_config_path_a) ? json_decode($this->filesys->get_contents($mod_config_path_a), true) : [];
            $mod_config = $this->filesys->exists($mod_config_path) ? json_decode($this->filesys->get_contents($mod_config_path), true) : [];

            if(empty($mod_config_a))
            {
                continue;
            }

            // Maybe update module
            if($is_update && in_array($mod_name_a, $mods_list))
            {
                if(empty($mod_config))
                {
                    continue;
                }

                if(!(isset($mod_config['update']) && $mod_config['update']))
                {
                    continue;
                }

                if(!(
                    (isset($mod_config_a['version']) && isset($mod_config['version'])) && 
                    version_compare($mod_config_a['version'], $mod_config['version'], '>')
                )){
                    continue;
                }

                $copied = $this->copyMod($mod_path_a, $mod_path);

                if($copied)
                {
                    $this->logAddUpdated($mod_name_a, $mod_config['version'], $mod_config_a['version']);
                }
            }
            // Install module
            elseif(!in_array($mod_name_a, $mods_list))
            {
                if(empty($mod_config_a))
                {
                    continue;
                }

                $copied = $this->copyMod($mod_path_a, $mod_path);

                if($copied)
                {
                    $this->logAddInstalled($mod_name_a, $mod_config_a['version']);
                }
            }
        }

        // Delete mods archive dir
        $this->rmDir($this->mods_dir_a);
    }

    protected function copyMod($mod_path_a, $mod_path)
    {
        $this->rmDir($mod_path);

        // $copied = copy_dir($mod_path_a, $mod_path);
        $copied = $this->filesys->move($mod_path_a, $mod_path);

        return is_wp_error($copied) ? false : $copied;
    }

    protected function rmDir($dir)
    {
        if($this->filesys->exists($dir))
        {
            return $this->filesys->rmdir($dir, true);
        }
        return false;
    }

    protected function logAddInstalled($mod_name, $version)
    {
        // $log = 'Installed ' . $mod_name . ' ' . $version;
        $log = $mod_name . ' ' . $version;

        if(!in_array($log, $this->log['installed']))
        {
            $this->log['installed'][] = $log;
        }
    }

    protected function logAddUpdated($mod_name, $version_old, $version_new)
    {
        // $log = 'Updated ' . $mod_name . ' ' . $version_old . ' > ' . $version_new;
        $log = $mod_name . ' ' . $version_old . ' > ' . $version_new;

        if(!in_array($log, $this->log['updated']))
        {
            $this->log['updated'][] = $log;
        }
    }

    protected function printLogs()
    {
        $log_lines = [];

        //Print installed logs
        $log_lines[] = '> Installed modules: (' . count($this->log['installed']) . ')';
        $log_lines[] = '> -------------------------';

        if(!empty($this->log['installed']))
        {
            foreach($this->log['installed'] as $log)
            {
                $log_lines[] = '> ' . $log;
            }
        }
        else
        {
            $log_lines[] = '> No modules installed.';
        }

        $log_lines[] = "\r\n";

        //Print updated logs
        $log_lines[] = '> Updated modules: (' . count($this->log['updated']) . ')';
        $log_lines[] = '> -------------------------';

        if(!empty($this->log['updated']))
        {
            foreach($this->log['updated'] as $log)
            {
                $log_lines[] = '> ' . $log;
            }
        }
        else
        {
            $log_lines[] = '> No modules updated.';
        }

        echo "\r\n";
        echo "\r\n";
        echo implode("\r\n", $log_lines);
        echo "\r\n";
        echo "\r\n";
    }
}
