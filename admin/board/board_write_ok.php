<?php 
    session_start();
    
    // Include database connection
    include __DIR__ . '/../../inc/db.php';
    
    // Check user authentication
    if (!isset($_SESSION['AUID'])) {
        echo "<script>
                alert('접근 권한이 없습니다');
                history.back();
            </script>";
        exit;
    }

    $username = '관리자';
    $title = $_POST['title'] ?? '';
    $authority = $_POST['authority'] ?? 0; 
    $content = $_POST['content'] ?? '';
    $date = date('Y-m-d');
 
    $file_orgname = $_FILES['file']['name'] ?? '';
    $tmpfile_path = $_FILES['file']['tmp_name'] ?? '';

    // 파일 업로드할 경로, 이미지 판단 
    $upload_path = "./board_files/" . $file_orgname;
    $file_type = $_FILES['file']['type'] ?? '';
    $is_img = (strpos($file_type, 'image') !== false) ? 1 : 0;
    
    // Safely move uploaded file
    if (!empty($tmpfile_path) && !empty($file_orgname)) {
        move_uploaded_file($tmpfile_path, $upload_path);
    }

    // Use prepared statement to prevent SQL injection
    $sql = "INSERT INTO board 
            (name, title, content, date, authority, file, is_img) 
            VALUES (?, ?, ?, ?, ?, ?, ?)"; 

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ssssssi", 
        $username, 
        $title, 
        $content, 
        $date, 
        $authority, 
        $file_orgname, 
        $is_img
    );

    if ($stmt->execute()) {
        echo "<script> 
                alert('글쓰기가 완료되었습니다.');
                location.href = './board_index.php';
            </script>";
    } else {
        echo "<script> 
                alert('글쓰기에 실패했습니다: " . $stmt->error . "');
                history.back();
            </script>";
    }

    $stmt->close();
    $mysqli->close();
?>
