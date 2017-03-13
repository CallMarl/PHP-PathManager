<?php

namespace Core\File;

class Path{

    private $location;
    private $root;

    public static function sanitalize(&$path){
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
    }

    public static function get_dirPath($filePath){
        $filePath = explode(DIRECTORY_SEPARATOR, $filePath);
                    array_pop($filePath);
        return implode(DIRECTORY_SEPARATOR, $filePath) . DIRECTORY_SEPARATOR;
    }

    private static function root(){
        $path = getcwd();
        $path = explode(DIRECTORY_SEPARATOR, $path);
        (isset($path[1]) && $path[1] === '') ? $tmp = array_splice($path, 0 , 1) : $tmp[] = $path[0];
        return $tmp;
    }

    private static function get_relativePath($from, $destination){
        self::sanitalize($destination);

        $from = explode(DIRECTORY_SEPARATOR, $from);
        $destination = explode(DIRECTORY_SEPARATOR, $destination);

        foreach ($from as $key => $value) {
            if(isset($destination[$key])){
                if(strcmp($destination[$key], $value) !== 0){
                    $compteur = $key;
                    break;
                }
                unset($destination[$key]);
            }
        }
        if(isset($compteur)){
            for($i = 1 ; $i < (sizeof($from) - $compteur + 1) ; $i++ ){
                array_unshift($destination, '..');
            }
        }
        return implode(DIRECTORY_SEPARATOR, $destination);
    }


    public static function is_absolut($path){
        self::sanitalize($path);
        $path = explode(DIRECTORY_SEPARATOR, $path);
        $absolut = self::root();

        if(strcmp($absolut[0], $path[0]) === 0){
            return TRUE;
        }
        return FALSE;
    }

    public static function path_linker($path1 , $path2, $subtil = FALSE){
        self::sanitalize($path1);
        self::sanitalize($path2);

        if($subtil){
            $path1 = explode(DIRECTORY_SEPARATOR, $path1);
            $path2 = explode(DIRECTORY_SEPARATOR, $path2);

            foreach ($path2 as $key => $value) {
                if(isset($tmp)){
                    if(strcmp($path1[0], $value) === 0){
                        unset($path2[$key]);
                        $tmp[] = $path1[0];
                    }
                    else{
                        break;
                    }
                }
                else if(in_array($value, $path1)){
                    unset($path2[$key]);
                    $index = array_search($value, $path1);
                    for($i = 0; $i <= $index; $i++){
                        $tmp[] = $path1[$i];
                    }
                    $path1 = array_splice($path1, $index+1, count($path1));
                }
                else{
                    if(self::is_absolut(implode(DIRECTORY_SEPARATOR, $path1))){
                        $tmp = self::root();
                    }
                    else{
                        $tmp = $path1;
                    }
                    break;
                }
            }

            $path1 = implode(DIRECTORY_SEPARATOR, $tmp);
            $path2 = implode(DIRECTORY_SEPARATOR, $path2);
        }

        if(substr($path1, -1) !== DIRECTORY_SEPARATOR && substr($path2, 0) !== DIRECTORY_SEPARATOR) $path1 .= DIRECTORY_SEPARATOR;
        else if(substr($path1, -1) === DIRECTORY_SEPARATOR &&substr($path1, 0) === DIRECTORY_SEPARATOR) $path1 = substr($path1, 0, -1);

        return $path1 . $path2;
    }

    private static function get_absolutePath($location, $relativePath){
        self::sanitalize($relativePath);

        if(!self::is_absolut($location)) throw new \Exception("var {$location} must be absolut at line :" . __LINE__);

        (self::is_absolut($relativePath)) ?
            $path = self::path_linker($location,  self::get_relativePath($location, $relativePath)) :
            $path = self::path_linker($location,  $relativePath);

        $path = explode(DIRECTORY_SEPARATOR, $path);
        $absolutes = array();

        foreach($path as $value){
            if('.' == $value) continue;
            if('..' == $value){
                array_pop($absolutes);
            }else{
                $absolutes[] = $value;
            }
        }

        $absolutes = implode(DIRECTORY_SEPARATOR, $absolutes);
        if(!self::is_absolut($absolutes)) $absolutes = self::path_linker(implode(DIRECTORY_SEPARATOR, self::root()), $absolutes);
        return $absolutes;
    }

    public function set_location($location){
        self::sanitalize($location);
        if(is_file($location)){
            $location = self::get_dirPath($location);
        }
        if(self::is_absolut($location)){
            $this->location = $location;
        }
        else{
            $location = self::get_absolutePath(getcwd(), $location);
            $this->location = $location;
        }
    }

    public static function is_pathlen($absolutPath){
        if(strlen($absolutPath) > 258){
            return FALSE;
        }
        return TRUE;
    }

    /*La class Path permet de se positionner dans un contexte ($location) et d'inter-agir à partir avec
      le reste l'arboressance à partir de celui-ci.*/
    public function __construct($location){
        $this->set_location($location);
        $this->root = implode(DIRECTORY_SEPARATOR, self::root());
    }

    /*Retourn le chemin du context*/
    public function get_location(){
        return $this->location;
    }

    /*Retourne le dossier Racine en fonction de la variable getcwd();*/
    public function get_root(){
        return $this->root;
    }

    /*Facilite la jonction entre le context et un autre chemin.*/
    public function link($path){
        return self::path_linker($this->location, $path, FALSE);
    }

    /*Retourne d'un chemin absolut un chemin relatif en fonction du contexte ($location).*/
    public function relative($destination){
        return self::get_relativePath($this->location, $destination);
    }

    /*Retourne d'un chemin relatif, un chemin absolut en fonction du contexte ($location).*/
    public function absolut($relativePath){
        return self::get_absolutePath($this->location, $relativePath);
    }
}

?>
