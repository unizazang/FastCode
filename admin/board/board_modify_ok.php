<?php 
    session_start();
    
    // Include database connection
    include __DIR__ . '/../../inc/db.php';

    // Improved authentication check
    if (!isset($_SESSION['AUID'])) {
        echo "<script>
                alert('접근 권한이 없습니다');
                location.href = '../login.php';
            </script>";
        exit;
    }

    // Validate and sanitize input
    $bno = filter_input(INPUT_POST, 'idx', FILTER_VALIDATE_INT);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (!$bno || empty($title) || empty($content)) {
        echo "<script>
                alert('필수 입력값이 누락되었습니다');
                history.back();
            </script>";
        exit;
    }

    // Fetch existing file information
    try {
        $existing_file_query = "SELECT file, is_img FROM board WHERE idx = ?";
        $stmt = $mysqli->prepare($existing_file_query);
        $stmt->bind_param("i", $bno);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing_file_data = $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error fetching existing file: " . $e->getMessage());
        $existing_file_data = ['file' => '', 'is_img' => 0];
    }

    // File upload handling
    $file_orgname = $existing_file_data['file'];
    $is_img = $existing_file_data['is_img'];

    if (!empty($_FILES['board_file']['name'])) {
        $file_tmppath = $_FILES['board_file']['tmp_name'];
        $file_type = $_FILES['board_file']['type'];
        $file_size = $_FILES['board_file']['size'];
        
        // File validation
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_type, $allowed_types)) {
            echo "<script>
                    alert('허용되지 않는 파일 형식입니다');
                    history.back();
                </script>";
            exit;
        }

        if ($file_size > $max_file_size) {
            echo "<script>
                    alert('파일 크기가 너무 큽니다 (최대 5MB)');
                    history.back();
                </script>";
            exit;
        }

        // Generate unique filename
        $file_ext = pathinfo($_FILES['board_file']['name'], PATHINFO_EXTENSION);
        $file_orgname = 'board_' . uniqid() . '.' . $file_ext;
        $upload_path = __DIR__ . '/board_files/' . $file_orgname;

        // Ensure board_files directory exists
        if (!is_dir(__DIR__ . '/board_files')) {
            mkdir(__DIR__ . '/board_files', 0755, true);
        }

        // Move uploaded file
        if (move_uploaded_file($file_tmppath, $upload_path)) {
            $is_img = 1;
        } else {
            echo "<script>
                    alert('파일 업로드에 실패했습니다');
                    history.back();
                </script>";
            exit;
        }
    }

    try {
        // Use prepared statement for update
        $sql = "UPDATE board SET title = ?, content = ?, date = ?, file = ?, is_img = ? WHERE idx = ?";
        $stmt = $mysqli->prepare($sql);
        $current_date = date('Y-m-d');
        $stmt->bind_param("ssssii", $title, $content, $current_date, $file_orgname, $is_img, $bno);
        
        if (!$stmt->execute()) {
            throw new Exception("데이터베이스 업데이트 실패: " . $stmt->error);
        }

        echo "<script>
                alert('글 수정이 완료되었습니다.');
                location.href = './board_read.php?idx={$bno}';
            </script>";
    } catch (Exception $e) {
        error_log("Board Modify Error: " . $e->getMessage());
        echo "<script>
                alert('글 수정에 실패했습니다. 관리자에게 문의해주세요.');
                location.href = './board_index.php';
            </script>";
        exit;
    }
?>