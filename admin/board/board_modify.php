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
    $bno = filter_input(INPUT_GET, 'idx', FILTER_VALIDATE_INT);
    
    if (!$bno) {
        echo "<script>
                alert('잘못된 접근입니다');
                history.back();
            </script>";
        exit;
    }

    try {
        // Use prepared statement
        $sql = "SELECT * FROM board WHERE idx = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $bno);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result) {
            throw new Exception("데이터베이스 쿼리 실행 실패");
        }
        
        $row = $result->fetch_assoc();
        
        if (!$row) {
            echo "<script>
                    alert('해당 게시물을 찾을 수 없습니다');
                    history.back();
                </script>";
            exit;
        }
    } catch (Exception $e) {
        error_log("Board Modify Error: " . $e->getMessage());
        echo "<script>
                alert('오류가 발생했습니다. 관리자에게 문의해주세요.');
                history.back();
            </script>";
        exit;
    }

    // Include head with relative path
    include __DIR__ . '/../../inc/head.php';
?>

<link rel="stylesheet" href="../css/board_write.css">

<?php     
    include __DIR__ . '/../../inc/common.php'; 
?>

</div>
<!-- 로고 및 북마크 위치 끝 -->

<!-- 본문시작 -->
<h2 class="page-title">글 수정</h2>

<form action="./board_modify_ok.php" method="POST" enctype="multipart/form-data">
  <input type="hidden" name="idx" value="<?= htmlspecialchars($bno) ?>">
  <div class="pd-54">
    <div class="subject">
      <label for="subject">제목</label>
      <input
        type="text"
        id="subject"
        name="title"
        required
        placeholder="제목을 입력하세요"
        value="<?= htmlspecialchars($row['title']); ?>"
      >
    </div>
    <div class="content">
      <label for="usermsg">내용</label>
      <textarea
        name="content"
        id="usermsg"
        cols="30"
        rows="10"
        placeholder="내용을 입력하세요"
        required
      ><?= htmlspecialchars($row['content']); ?></textarea>
    </div>

    <!-- 기존 파일 처리 로직 -->
    <?php if (!empty($row['file']) && $row['is_img'] == 1): ?>
    <div class="existing-file">
        <label>현재 이미지</label>
        <img src="./board_files/<?= htmlspecialchars($row['file']); ?>" alt="현재 이미지">
    </div>
    <?php endif; ?>

    <div class="file-upload">
      <label for="board_file">첨부 파일</label>
      <input 
        type="file" 
        id="board_file" 
        name="board_file" 
        accept="image/jpeg,image/png,image/gif"
      >
    </div>

    <div class="btns">
      <button type="submit" class="y-btn big-btn btn-navy">등록완료</button>
      <a href="./board_read.php?idx=<?= $bno ?>" class="y-btn big-btn btn-sky">등록취소</a>
    </div>
  </div>
</form>

<?php
    include __DIR__ . '/../../inc/footer.php';
    include __DIR__ . '/../../inc/foot.php';
?>