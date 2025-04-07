<?php
class Database {
    private $host = "localhost";
    private $db_name = "nci_database";
    private $username = "root";
    private $password = "";
    public $conn;

    // الحصول على اتصال بقاعدة البيانات
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }

        return $this->conn;
    }

    // التحقق من صحة بيانات المستخدم
    public function validateUser($username, $password) {
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            if(password_verify($password, $row['password'])) {
                return $row;
            }
        }
        return false;
    }

    // الحصول على جميع التخصصات
    public function getSpecializations($lang = 'ar') {
        $query = "SELECT * FROM specializations";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $specializations = $stmt->fetchAll();

        foreach($specializations as &$specialization) {
            // إضافة المميزات
            $query = "SELECT feature_" . $lang . " as feature FROM specialization_features WHERE specialization_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $specialization['id']);
            $stmt->execute();
            $specialization['features'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // إضافة فرص العمل
            $query = "SELECT opportunity_" . $lang . " as opportunity FROM job_opportunities WHERE specialization_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $specialization['id']);
            $stmt->execute();
            $specialization['opportunities'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        return $specializations;
    }

    // الحصول على الأخبار
    public function getNews($lang = 'ar', $limit = 3) {
        $query = "SELECT id, title_" . $lang . " as title, content_" . $lang . " as content, image_url, created_at 
                 FROM news ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // إضافة مستخدم جديد
    public function addUser($username, $password, $email, $role = 'user') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, password, email, role) VALUES (:username, :password, :email, :role)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password", $hashedPassword);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":role", $role);

        return $stmt->execute();
    }

    // تحديث بيانات المستخدم
    public function updateUser($id, $data) {
        $allowedFields = ['username', 'email', 'role'];
        $updates = [];
        $params = [':id' => $id];

        foreach($data as $key => $value) {
            if(in_array($key, $allowedFields)) {
                $updates[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if(empty($updates)) return false;

        $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    // حذف مستخدم
    public function deleteUser($id) {
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}
?> 