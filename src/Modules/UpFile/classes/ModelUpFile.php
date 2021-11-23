<?php
namespace WN\UpFile;

use WN\DB\Pattern\Model;
use WN\Core\Helper\{Upload, Dir, File};

class ModelUpFile extends Model
{
    public $table_name = 'files';
    public static $dir = '/public/uploads';

    // public static $auto_create = true;

    public static $table_options = [
        'columns'   => [
            'id integer(11) pk_ai',
            'mime varchar(16)',
            'dir varchar(128)',
            'filename varchar(64) unique',
            'orig_name varchar(128)',
            'purpose varchar(32)',
            'upload_time integer(11)',
            'user integer(11)',
            'alt varchar(255)',
    ]];

    public function upload(array $file, $dir)
    {
        if(Upload::valid($file) && is_uploaded_file($file['tmp_name']))
        {
            if(!$dir) $dir = static::$dir;
            elseif($dir[0] !== '/')
                $dir = (static::$dir) ? static::$dir.'/'.$dir : $dir;

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = static::unique_filename($dir, $ext);

            if(($f = Upload::save($file, $filename, $dir)))
            {
                $data['mime'] = File::mime_by_ext($ext)[0];
                $data['filename'] = $f;
                $data['orig_name'] = $file['name'];
                $data['upload_time'] = time();
            }
        }

        return $data ?? [];
    }

    /**
     * Searches for files that have no entry in db
     * and return its,
     * Removes entries pointing to non-existent files
     *
     * @params mixed (list of directories)
     * @return array
     */
    public function orphan()
    {
        $paths = func_get_args();
        $files_dir = [];

        if(!$paths)
        {
            $files_db = $this->table->select('filename')
                ->setFetchMode(\PDO::FETCH_COLUMN, 0)
                ->getAll();

                // var_dump($this->table->db->driver);

            foreach($files_db as $file)
                $paths[] = pathinfo($file, PATHINFO_DIRNAME);

            $paths = array_unique($paths);
        }

        // var_dump($paths);

        foreach($paths as $dir)
                $files_dir = array_merge($files_dir, Dir::get($dir, null, null, true, true));

        $files_dir = array_unique($files_dir);

        $to_delete = array_diff($files_db, $files_dir);

        if($to_delete) $this->table->delete('filename', 'in', $to_delete);

        return array_diff($files_dir, $files_db);
    }

    public function clean_db()
    {
        $files = $this->table->getAll();
        foreach($files AS $f)
            if(!is_file($f['filename'])) $to_delete[] = $f['id'];

        return (isset($to_delete)) ? $this->table->delete('id', 'in', $to_delete) : 0;
    }

    public function delete()
    {
        $this->table->setFetchMode(\PDO::FETCH_COLUMN, 2);

        $files = call_user_func_array([$this->table, 'getAll'], func_get_args());
        $count = call_user_func_array([$this->table, 'delete'], func_get_args());

        // var_dump($files, static::$db);

        foreach($files AS $file)
            if(is_file($file)) unlink($file);

        return $count;
    }

    protected static function unique_filename($dir, $ext)
    {
        $uid = uniqid();
        if(!is_file(ltrim($dir, '/').'/'.$uid.$ext))
            return "$uid.$ext";
        else return static::unique_filename($dir, $ext);
    }
}