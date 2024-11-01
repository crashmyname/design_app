<?php

namespace Support;

use PDO;
use PDOException;
use Config\Database;

class DB
{
    private static $conn = null;

    public static function getConnection()
    {
        // Cek apakah koneksi sudah ada, jika sudah ada maka gunakan yang ada
        if (self::$conn !== null) {
            return self::$conn;
        }

        // Nilai default untuk variabel lingkungan
        $defaultEnv = [
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'defaultdb', // Gunakan nama database default
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
        ];

        // Coba membaca file .env
        $envFilePath = __DIR__ . '/../../.env';
        if (file_exists($envFilePath)) {
            $env = parse_ini_file($envFilePath);
            if ($env !== false) {
                foreach ($defaultEnv as $key => $value) {
                    $_ENV[$key] = isset($env[$key]) && $env[$key] !== '' ? $env[$key] : $value;
                }
            } else {
                $_ENV = $defaultEnv;
            }
        } else {
            $_ENV = $defaultEnv;
        }

        try {
            $dsn = "{$_ENV['DB_CONNECTION']}:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']}";
            self::$conn = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
            self::$conn->exec("set names utf8");
            self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Log kesalahan ke file log, buat file log jika tidak ada
            $logDir = __DIR__ . '/../logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }
            error_log('Connection failed: ' . $e->getMessage(), 3, $logDir . '/error.log');
            self::renderError($e);
        }

        return self::$conn;
    }

    public static function beginTransaction()
    {
        return self::getConnection()->beginTransaction();
    }

    public static function commit()
    {
        return self::getConnection()->commit();
    }

    public static function rollback()
    {
        return self::getConnection()->rollback();
    }

    public static function renderError($exception)
    {
        static $errorDisplayed = false;

        if (!$errorDisplayed) {
            $errorDisplayed = true;

            // Set response code menjadi 500
            if (!headers_sent()) { 
                http_response_code(500);
            }

            // Tampilkan halaman error
            $exceptionData = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
            extract($exceptionData);
            include __DIR__ . '/../src/View/errors/page_error.php';
        }
        exit();
    }

    // Eksekusi query
    public static function query($sql, $params = [])
    {
        $stmt = self::getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt;
    }

    // Fungsi untuk mengambil semua hasil query
    public static function fetchAll($sql, $params = [])
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fungsi untuk mengambil satu baris hasil query
    public static function fetch($sql, $params = [])
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Fungsi untuk menghitung jumlah baris hasil query
    public static function count($sql, $params = [])
    {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }
}
