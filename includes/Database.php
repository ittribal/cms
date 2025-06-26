<?php
// includes/Database.php - 数据库连接类

class Database {
    private static $instance = null; // 用于存储 Database 类的唯一实例
    private $connection;             // 用于存储 PDO 数据库连接对象
    
    // 私有构造函数，防止直接 new Database()
    private function __construct() {
        try {
            // 检查必要的数据库配置常量是否已在 config.php 中定义
            if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
                throw new Exception("数据库配置常量未定义。请检查 includes/config.php 文件。");
            }
            
            // 构建数据源名称 (DSN) 字符串
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            
            // PDO 连接选项
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // 错误模式：抛出 PDOException 异常
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // 默认抓取模式：关联数组
                PDO::ATTR_EMULATE_PREPARES => false,               // 禁用预处理语句模拟，强制使用真正的预处理，增强安全性
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4" // 连接时设置字符集
            ];
            
            // 创建 PDO 数据库连接
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            // 捕获 PDO 相关的数据库连接错误
            error_log("数据库连接失败: " . $e->getMessage()); // 记录错误到日志文件
            // 在生产环境下不直接显示详细错误给用户，但这里为了调试可以显示
            die("数据库连接失败：" . $e->getMessage() . " 请检查 includes/config.php 中的数据库配置。");
        } catch (Exception $e) {
            // 捕获其他可能的异常（例如常量未定义）
            error_log("数据库配置错误: " . $e->getMessage());
            die("数据库配置错误：" . $e->getMessage());
        }
    }
    
    // 获取 Database 类的唯一实例（单例模式的入口）
    public static function getInstance() {
        if (self::$instance === null) { // 如果实例不存在，则创建一个
            self::$instance = new self();
        }
        return self::$instance; // 返回现有实例
    }
    
    // 执行查询并返回所有结果（多行）
    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql); // 预处理 SQL 语句
            $stmt->execute($params);                   // 执行语句并绑定参数
            $result = $stmt->fetchAll();               // 获取所有结果
            return $result ?: []; // 如果没有结果，返回空数组而不是 false，提高健壮性
        } catch (PDOException $e) {
            error_log("数据库查询错误 (fetchAll): " . $e->getMessage() . " SQL: " . $sql);
            return []; // 错误时返回空数组
        }
    }
    
    // 执行查询并返回单行结果
    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql); // 预处理 SQL 语句
            $stmt->execute($params);                   // 执行语句并绑定参数
            $result = $stmt->fetch();                  // 获取单行结果
            return $result ?: null; // 如果没有结果，返回 null 而不是 false，提高健壮性
        } catch (PDOException $e) {
            error_log("数据库查询错误 (fetchOne): " . $e->getMessage() . " SQL: " . $sql);
            return null; // 错误时返回 null
        }
    }
    
    // 执行更新、插入、删除操作（不返回结果集，只返回成功/失败）
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql); // 预处理 SQL 语句
            return $stmt->execute($params);          // 执行语句并绑定参数，返回 true/false
        } catch (PDOException | Exception $e) { // 捕获 PDOException 和其他 Exception
            error_log("数据库执行错误 (execute): " . $e->getMessage() . " SQL: " . $sql);
            return false; // 错误时返回 false
        }
    }
    
    // 获取最后插入的ID
    public function getLastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    // 开始事务
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    // 提交事务
    public function commit() {
        return $this->connection->commit();
    }
    
    // 回滚事务
    public function rollback() {
        return $this->connection->rollback();
    }
    
    // 获取底层的 PDO 连接对象
    public function getConnection() {
        return $this->connection;
    }
    
    // 防止克隆实例（单例模式的核心）
    private function __clone() {}
    
    // 防止通过反序列化创建新实例（单例模式的核心）
    public function __wakeup() {
        throw new Exception("无法反序列化单例对象");
    }
}