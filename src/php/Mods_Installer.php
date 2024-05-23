<?php 

namespace WPSEEDE;

use WPSEEDE\Utils\Base as Utils_Base;

class Mods_Installer 
{
    const MODS_ARCHIVE_URL = 'https://github.com/oboyda/wpseed-mods/archive/refs/heads/master.zip';
    const MODS_ARCHIVE_EXTR_DIR = 'wpseed-mods-master';

    protected $args;
    protected $context_name;
    protected $namespace;
    protected $base_dir;
    protected $load_modules;

    protected $mods_dir;
    protected $mods_dir_a;
    protected $mods_dir_e;
    protected $mods_path_a;
    protected $mods_path_index_js;

    protected $filesys;

    protected $log;

    public function __construct($args)
    {
        $this->args = wp_parse_args($args, [
            'context_name' => 'wpseede',
            'namespace' => 'WPSEEDE',
            'base_dir' => __DIR__,
            'load_modules' => []
        ]);
        $this->context_name = $this->args['context_name'];
        $this->namespace = $this->args['namespace'];
        $this->base_dir = $this->args['base_dir'];
        $this->load_modules = $this->args['load_modules'];

        $this->mods_dir = $this->base_dir . '/mods';
        $this->mods_dir_a = $this->mods_dir . '/__mods-archive';
        $this->mods_dir_e = $this->mods_dir . '/' . self::MODS_ARCHIVE_EXTR_DIR;
        $this->mods_path_a = $this->mods_dir . '/__mods-archive.zip';
        $this->mods_path_index_js = $this->mods_dir . '/index.js';

        $this->log = [
            'installed' => [],
            'updated' => [],
            'deleted' => []
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
        $this->writeIndexJs();
        $this->printLogs();
    }

    protected function setFilesystem()
    {
        global $wp_filesystem;

        Utils_Base::setWpFileSystem();

        if(isset($wp_filesystem))
        {
            $this->filesys = $wp_filesystem;
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
        $mods_list = $this->getDirMods($this->mods_dir, false);
        $mods_list_a = $this->getDirMods($this->mods_dir_a, false);

        if(empty($mods_list_a))
        {
            return;
        }

        foreach($mods_list_a as $mod_name_a)
        {
            $mod_path_a = $this->mods_dir_a . '/' . $mod_name_a;
            $mod_path = $this->mods_dir . '/' . $mod_name_a;

            $mod_config_path_a = $mod_path_a . '/mod.json';
            $mod_config_path = $mod_path . '/mod.json';

            $mod_config_a = $this->filesys->exists($mod_config_path_a) ? (array)json_decode($this->filesys->get_contents($mod_config_path_a), true) : [];
            $mod_config = $this->filesys->exists($mod_config_path) ? (array)json_decode($this->filesys->get_contents($mod_config_path), true) : [];

            $mod_config_a = wp_parse_args($mod_config_a, [
                'version' => false,
                'update' => false
            ]);
            $mod_config = wp_parse_args($mod_config, [
                'version' => false,
                'update' => false
            ]);

            if(empty($mod_config_a['version']))
            {
                continue;
            }

            if(!(
                (is_array($this->load_modules) && in_array($mod_name_a, $this->load_modules))
                || $this->load_modules === 'all'
            )){
                // Maybe delete module
                if(in_array($mod_name_a, $mods_list) && $mod_config['update'])
                {
                    $this->rmDir($mod_path);
                    $this->addLog('deleted', $mod_name_a, $mod_config['version']);
                }

                continue;
            }

            // Maybe update module
            if($is_update && in_array($mod_name_a, $mods_list))
            {
                if(!($mod_config['update'] && !empty($mod_config['version'])))
                {
                    continue;
                }

                if(!version_compare($mod_config_a['version'], $mod_config['version'], '>'))
                {
                    continue;
                }

                $copied = $this->copyMod($mod_path_a, $mod_path);

                if($copied)
                {
                    $this->addLog('updated', $mod_name_a, $mod_config['version'], $mod_config_a['version']);
                }
            }
            // Install module
            elseif(!in_array($mod_name_a, $mods_list))
            {
                $copied = $this->copyMod($mod_path_a, $mod_path);

                if($copied)
                {
                    $this->addLog('installed', $mod_name_a, $mod_config_a['version']);
                }
            }
        }

        // Delete mods archive dir
        $this->rmDir($this->mods_dir_a);
    }

    protected function writeIndexJs()
    {
        if($this->filesys->exists($this->mods_dir))
        {
            if(empty($this->log['installed']) && empty($this->log['updated']))
            {
                return;
            }

            $index_lines = [];

            $mods_list = $this->getDirMods($this->mods_dir);

            foreach($mods_list as $mod_name)
            {
                $index_lines[$mod_name] = [
                    'js' => '',
                    'scss' => ''
                ];

                $mod_path = $this->mods_dir . '/' . $mod_name;

                $mod_path_index_js = $mod_path . '/index.js'; 
                $mod_path_index_scss = $mod_path . '/index.scss'; 

                if($this->filesys->exists($mod_path_index_js))
                {
                    $index_lines[$mod_name]['js'] = "import './{$mod_name}/index.js';";
                }
                if($this->filesys->exists($mod_path_index_scss))
                {
                    $index_lines[$mod_name]['scss'] = "import './{$mod_name}/index.scss';";
                }
            }

            $index_lines_str = '';

            foreach($index_lines as $mod => $mod_lines)
            {
                $index_lines_str .= implode("\r\n", $mod_lines);
                $index_lines_str .= "\r\n";
                $index_lines_str .= "\r\n";
            }

            $this->filesys->put_contents($this->mods_path_index_js, $index_lines_str);
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

        $log_lines[] = "\r\n";

        //Print deleted logs
        $log_lines[] = '> Deleted modules: (' . count($this->log['deleted']) . ')';
        $log_lines[] = '> -------------------------';

        if(!empty($this->log['deleted']))
        {
            foreach($this->log['deleted'] as $log)
            {
                $log_lines[] = '> ' . $log;
            }
        }
        else
        {
            $log_lines[] = '> No modules deleted.';
        }

        echo "\r\n";
        echo "\r\n";
        echo implode("\r\n", $log_lines);
        echo "\r\n";
        echo "\r\n";
    }

    /*
    Helpers
    -------------------------
    */

    protected function updateContext($mod_path)
    {
        $context_name_a = 'wpseedm';
        $namespace_a = 'WPSEEDM';

        if(
            $this->context_name == $context_name_a && 
            $this->namespace == $namespace_a
        ){
            return;
        }

        $mod_files_tree = $this->getDirTree($mod_path);
        
        if(!empty($mod_files_tree))
        {
            foreach($mod_files_tree as $file)
            {
                $file_content = trim($this->filesys->get_contents($file));

                if(empty($file_content))
                {
                    continue;
                }

                $file_content = str_replace($context_name_a, $this->context_name, $file_content, $count1);
                $file_content = str_replace(ucfirst($context_name_a), ucfirst($this->context_name), $file_content, $count2);
                $file_content = str_replace($namespace_a, $this->namespace, $file_content, $count3);

                if($count1 || $count2 || $count3)
                {
                    $this->filesys->put_contents($file, $file_content);
                }
            }
        }
    }

    protected function copyMod($mod_path_a, $mod_path)
    {
        $this->rmDir($mod_path);

        // $copied = copy_dir($mod_path_a, $mod_path);
        $copied = $this->filesys->move($mod_path_a, $mod_path);

        $copied = is_wp_error($copied) ? false : $copied;

        if($copied)
        {
            $this->updateContext($mod_path);
        }

        return $copied;
    }

    protected function rmDir($dir)
    {
        if($this->filesys->exists($dir))
        {
            return $this->filesys->rmdir($dir, true);
        }
        return false;
    }

    protected function addLog($type, $mod_name, $version, $version_new=null)
    {
        $log_line = $mod_name . ' ' . $version;

        if(isset($version_new))
        {
            $log_line .= ' > ' . $version_new;
        }

        if(!in_array($log_line, $this->log[$type]))
        {
            $this->log[$type][] = $log_line;
        }

        // $this->log[$type][$mod_name] = $log_line;
    }

    protected function getDirMods($dir_path, $full_path=false)
    {
        $dir_mods = [];

        $dir_files = wpseed_get_dir_files($dir_path, false, false);

        if(!empty($dir_files))
        {
            foreach($dir_files as $file)
            {
                $file_path = $dir_path . '/' . $file;
                if(is_dir($file_path))
                {
                    $dir_mods[] = $full_path ? $file_path : $file;
                }
            }
        }

        return $dir_mods;
    }

    protected function getDirTree($dir_path, $tree_files=[])
    {
        $dir_files = wpseed_get_dir_files($dir_path, true, false);

        foreach($dir_files as $file)
        {
            if(is_dir($file))
            {
                $tree_files = $this->getDirTree($file, $tree_files);
            }
            else
            {
                $tree_files[] = $file;
            }
        }

        return $tree_files;
    }
}
