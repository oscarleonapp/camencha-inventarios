<?php
/**
 * SimpleXLSX - Librería simple para leer archivos Excel
 * Versión simplificada para el sistema de inventario
 */

class SimpleXLSX {
    private static $error = '';
    private $data = [];
    
    public static function parse($file_path) {
        $xlsx = new self();
        
        if (!file_exists($file_path)) {
            self::$error = "Archivo no encontrado: $file_path";
            return false;
        }
        
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        if ($extension === 'csv') {
            return $xlsx->parseCSV($file_path);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            // Para archivos Excel reales, sugerir conversión a CSV
            self::$error = "Para archivos Excel (.xlsx/.xls), conviértalos a CSV primero";
            return false;
        } else {
            self::$error = "Formato de archivo no soportado: $extension";
            return false;
        }
    }
    
    private function parseCSV($file_path) {
        $this->data = [];
        
        // Detectar delimitador
        $delimiters = [',', ';', '\t'];
        $delimiter = $this->detectDelimiter($file_path, $delimiters);
        
        if (($handle = fopen($file_path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                // Convertir encoding si es necesario
                $data = array_map(function($item) {
                    return mb_convert_encoding($item, 'UTF-8', 'auto');
                }, $data);
                
                $this->data[] = $data;
            }
            fclose($handle);
        }
        
        return $this;
    }
    
    private function detectDelimiter($file_path, $delimiters) {
        $handle = fopen($file_path, 'r');
        $first_line = fgets($handle);
        fclose($handle);
        
        $delimiter_count = [];
        
        foreach ($delimiters as $delimiter) {
            $delimiter_count[$delimiter] = substr_count($first_line, $delimiter);
        }
        
        return array_search(max($delimiter_count), $delimiter_count) ?: ',';
    }
    
    public function rows() {
        return $this->data;
    }
    
    public static function parseError() {
        return self::$error;
    }
}
?>