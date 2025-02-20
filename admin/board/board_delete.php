<?php
    session_start();
    
    // Include database connection
    include __DIR__ . '/../../inc/db.php';

    // Improved authentication check
    if (!isset($_SESSION['AUID'])) {
        $data = ['result' => false, 'message' => '접근 권한이 없습니다'];
        echo json_encode($data);
        exit;
    }

    // Validate input
    $idx = filter_input(INPUT_POST, 'idx', FILTER_VALIDATE_INT);
    
    if (!$idx) {
        $data = ['result' => false, 'message' => '잘못된 게시물 번호입니다'];
        echo json_encode($data);
        exit;
    }

    try {
        // Use prepared statement for deletion
        $sql = "DELETE FROM board WHERE idx = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $idx);
        
        // Execute deletion
        if ($stmt->execute()) {
            // Optional: Delete associated file if it exists
            $file_path = __DIR__ . '/board_files/' . $stmt->affected_rows > 0 ? $stmt->affected_rows : '';
            if (!empty($file_path) && file_exists($file_path)) {
                unlink($file_path);
            }

            $data = ['result' => true, 'message' => '게시물이 삭제되었습니다'];
        } else {
            $data = ['result' => false, 'message' => '삭제 중 오류가 발생했습니다'];
        }
    } catch (Exception $e) {
        error_log("Board Delete Error: " . $e->getMessage());
        $data = ['result' => false, 'message' => '삭제 중 오류가 발생했습니다'];
    }

    // Return JSON response
    echo json_encode($data);
?>