<?php
require_once 'admin_auth_check.php';
require_once 'db.php';

$admin = $_SESSION['admin_username'];

$target_dir = "assets/gallery/";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_photo') {
        if(isset($_FILES["photo"]) && $_FILES["photo"]["error"] == 0){
            $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png", "webp" => "image/webp");
            $filename = $_FILES["photo"]["name"];
            $filetype = $_FILES["photo"]["type"];
            $filesize = $_FILES["photo"]["size"];

            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if(!array_key_exists(strtolower($ext), $allowed)) {
                $_SESSION['gal_error'] = "Error: Please select a valid file format.";
            } elseif ($filesize > 5 * 1024 * 1024) {
                $_SESSION['gal_error'] = "Error: File size is larger than the allowed limit.";
            } elseif(in_array($filetype, $allowed)){
                $new_filename = uniqid() . "." . $ext;
                if(move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $new_filename)){
                    $stmt = $conn->prepare("INSERT INTO gallery (file_name) VALUES (?)");
                    $stmt->bind_param("s", $new_filename);
                    $stmt->execute();
                    $stmt->close();
                    log_activity($conn, $admin, 'Gallery Photo Added', "Added: $filename");
                    $_SESSION['gal_success'] = "Photo uploaded successfully.";
                } else {
                    $_SESSION['gal_error'] = "File upload failed, please try again.";
                }
            } else {
                $_SESSION['gal_error'] = "Error: There was a problem uploading your file.";
            }
        } else {
            $_SESSION['gal_error'] = "Error: " . ($_FILES["photo"]["error"] ?? 'No file selected.');
        }
    }

    if ($action === 'delete_photo') {
        $photo_id = (int)$_POST['photo_id'];
        $row = $conn->query("SELECT file_name FROM gallery WHERE id=$photo_id")->fetch_assoc();
        if ($row) {
            $file_path = $target_dir . $row['file_name'];
            if(file_exists($file_path)) unlink($file_path);
            $conn->query("DELETE FROM gallery WHERE id=$photo_id");
            log_activity($conn, $admin, 'Gallery Photo Deleted', "Removed: {$row['file_name']}");
            $_SESSION['gal_success'] = "Photo deleted.";
        }
    }

    header('Location: admin_gallery.php');
    exit;
}

$success = $_SESSION['gal_success'] ?? '';
$error   = $_SESSION['gal_error']   ?? '';
unset($_SESSION['gal_success'], $_SESSION['gal_error']);

$photos = $conn->query("SELECT * FROM gallery ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery — Santa Fe Beach Club</title>
    <link rel="stylesheet" href="admin.css?v=2">
    <link rel="stylesheet" href="dashboard.css?v=2">
</head>
<body>
    <?php $active_page = 'gallery'; include '_sidebar.php'; ?>

    <main class="main-content">
        <?php
        $page_title = 'Gallery';
        $page_subtitle = 'Manage photos showcased on the public gallery page.';
        $header_extra_html = '
            <button class="btn-primary" onclick="document.getElementById(\'addModal\').classList.add(\'open\')" style="padding: 8px 16px; display: flex; align-items: center; gap: 8px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Photo
            </button>
        ';
        include '_page_header.php';
        ?>


        <?php if ($success): ?><div class="alert alert-success"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <?php if ($photos->num_rows === 0): ?>
        <div class="admin-card"><div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            <p>No photos yet. Click <strong>Add Photo</strong> to upload one.</p>
        </div></div>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:20px;">
        <?php while ($p = $photos->fetch_assoc()): ?>
        <div class="admin-card" style="padding:0;overflow:hidden;">
            <div style="height:200px;background:#f3f4f6;">
                <img src="<?php echo htmlspecialchars($target_dir . $p['file_name']); ?>" style="width:100%;height:100%;object-fit:cover;" alt="Gallery Photo">
            </div>
            <div style="padding:15px;display:flex;justify-content:space-between;align-items:center;">
                <div style="font-size:12px;color:var(--text-muted);">
                    Added <?php echo date('M j, Y', strtotime($p['created_at'])); ?>
                </div>
                <form method="POST" onsubmit="return confirm('Delete this photo?')">
                    <input type="hidden" name="action" value="delete_photo">
                    <input type="hidden" name="photo_id" value="<?php echo $p['id']; ?>">
                    <button type="submit" class="btn-danger" style="padding:4px 8px;font-size:12px;">Delete</button>
                </form>
            </div>
        </div>
        <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </main>

<!-- Add Photo Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('open')">×</button>
        <h3>Add Photo</h3>
        <p class="modal-sub">Upload a new image to the gallery.</p>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_photo">
            <div class="admin-form-group">
                <label>Select Image</label>
                <input type="file" name="photo" required accept="image/*" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;">
            </div>
            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Upload Photo</button>
        </form>
    </div>
</div>
<script src="sidebar-toggle.js"></script>
</body>
</html>
