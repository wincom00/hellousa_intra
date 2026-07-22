<?php
// config.php - 데이터베이스 설정
class Database {
    private $host = 'localhost';
    private $db_name = 'erp_system';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// estimate_model.php - 견적서 모델
class EstimateModel {
    private $conn;
    private $table_name = "estimates";

    public function __construct($db) {
        $this->conn = $db;
    }

    // 견적서 저장
    public function save($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET company_name=:company_name, estimate_date=:estimate_date, 
                      valid_date=:valid_date, customer_name=:customer_name,
                      customer_phone=:customer_phone, customer_email=:customer_email,
                      items=:items, total_amount=:total_amount, vat=:vat, 
                      final_amount=:final_amount, memo=:memo, status=:status";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":company_name", $data['company_name']);
        $stmt->bindParam(":estimate_date", $data['estimate_date']);
        $stmt->bindParam(":valid_date", $data['valid_date']);
        $stmt->bindParam(":customer_name", $data['customer_name']);
        $stmt->bindParam(":customer_phone", $data['customer_phone']);
        $stmt->bindParam(":customer_email", $data['customer_email']);
        $stmt->bindParam(":items", $data['items']);
        $stmt->bindParam(":total_amount", $data['total_amount']);
        $stmt->bindParam(":vat", $data['vat']);
        $stmt->bindParam(":final_amount", $data['final_amount']);
        $stmt->bindParam(":memo", $data['memo']);
        $stmt->bindParam(":status", $data['status']);

        return $stmt->execute();
    }

    // 견적서 목록 조회
    public function getList($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $query = "SELECT * FROM " . $this->table_name . " 
                  ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 견적서 상세 조회
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// estimate_controller.php - 견적서 컨트롤러
class EstimateController {
    private $model;

    public function __construct($model) {
        $this->model = $model;
    }

    // 견적서 생성 페이지
    public function create() {
        include 'views/estimate_form.php';
    }

    // 견적서 저장
    public function store() {
        if ($_POST) {
            $items = json_encode($_POST['items']);
            
            $data = [
                'company_name' => $_POST['company_name'],
                'estimate_date' => $_POST['estimate_date'],
                'valid_date' => $_POST['valid_date'],
                'customer_name' => $_POST['customer_name'],
                'customer_phone' => $_POST['customer_phone'],
                'customer_email' => $_POST['customer_email'],
                'items' => $items,
                'total_amount' => $_POST['total_amount'],
                'vat' => $_POST['vat'],
                'final_amount' => $_POST['final_amount'],
                'memo' => $_POST['memo'],
                'status' => 'draft'
            ];

            if ($this->model->save($data)) {
                header('Location: index.php?action=list');
            } else {
                echo "저장 실패";
            }
        }
    }

    // 견적서 목록
    public function index() {
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        $estimates = $this->model->getList($page);
        include 'views/estimate_list.php';
    }

    // 견적서 상세
    public function show($id) {
        $estimate = $this->model->getById($id);
        include 'views/estimate_detail.php';
    }
}

// index.php - 메인 라우터
require_once 'config.php';
require_once 'estimate_model.php';
require_once 'estimate_controller.php';

$database = new Database();
$db = $database->getConnection();
$model = new EstimateModel($db);
$controller = new EstimateController($model);

$action = isset($_GET['action']) ? $_GET['action'] : 'index';

switch($action) {
    case 'create':
        $controller->create();
        break;
    case 'store':
        $controller->store();
        break;
    case 'show':
        $id = $_GET['id'];
        $controller->show($id);
        break;
    case 'list':
    default:
        $controller->index();
        break;
}
?>