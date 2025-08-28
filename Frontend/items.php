<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

include("../Backend/db.php");

// Store user ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Handle form submission (Create/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['itemId'] ?? null;
    $brand_id = $_POST['itemBrand'];
    $category_id = $_POST['itemCategory'];
    $code = $_POST['itemCode'];
    $name = $_POST['itemName'];
    $status = isset($_POST['itemActive']) ? 'Active' : 'Inactive';

    // Handle file upload
    $attachment = null;
    if (isset($_FILES['itemAttachment']) && $_FILES['itemAttachment']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "../uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $attachment = time() . "_" . basename($_FILES['itemAttachment']['name']);
        move_uploaded_file($_FILES['itemAttachment']['tmp_name'], $targetDir . $attachment);
    }

    if ($id) {
        // Update existing item
        $sql = "UPDATE master_item 
                SET brand_id=?, category_id=?, code=?, name=?, attachment=?, status=?, updated_at=NOW() 
                WHERE id=? AND user_id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) die("SQL Error (Update): " . $conn->error);
        $stmt->bind_param("iissssii", $brand_id, $category_id, $code, $name, $attachment, $status, $id, $user_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert new item
        $sql = "INSERT INTO master_item (user_id, brand_id, category_id, code, name, attachment, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        if (!$stmt) die("SQL Error (Insert): " . $conn->error);
        $stmt->bind_param("iiissss", $user_id, $brand_id, $category_id, $code, $name, $attachment, $status);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: items.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $del_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM master_item WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $del_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: items.php");
    exit();
}

// Pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch brands and categories as arrays
$brandsArr = [];
$brands = $conn->query("SELECT id, name FROM master_brand WHERE user_id=$user_id");
while($b = $brands->fetch_assoc()) $brandsArr[] = $b;

$categoriesArr = [];
$categories = $conn->query("SELECT id, name FROM master_category WHERE user_id=$user_id");
while($c = $categories->fetch_assoc()) $categoriesArr[] = $c;

// Count items for pagination
$totalResult = $conn->query("SELECT COUNT(*) AS total FROM master_item WHERE user_id=$user_id");
$totalRow = $totalResult->fetch_assoc();
$totalPages = ceil($totalRow['total'] / $limit);

// Fetch items
$items = $conn->query("SELECT i.*, b.name AS brand_name, c.name AS category_name 
                       FROM master_item i 
                       LEFT JOIN master_brand b ON i.brand_id=b.id 
                       LEFT JOIN master_category c ON i.category_id=c.id 
                       WHERE i.user_id=$user_id 
                       ORDER BY i.id DESC 
                       LIMIT $limit OFFSET $offset") or die("Items query error: ".$conn->error);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MDM — Items</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">MDM</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="brands.php">Brands</a></li>
        <li class="nav-item"><a class="nav-link" href="categories.php">Categories</a></li>
        <li class="nav-item"><a class="nav-link active" href="items.php">Items</a></li>
      </ul>
      <div class="d-flex align-items-center">
        <span class="text-white me-3">Hello, <?= htmlspecialchars($_SESSION['user']); ?></span>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
      </div>
    </div>
  </div>
</nav>

<main class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Items</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#itemModal">New Item</button>
  </div>

  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Brand</th>
        <th>Category</th>
        <th>Code</th>
        <th>Name</th>
        <th>Status</th>
        <th>File</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $items->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['brand_name'] ?? ''); ?></td>
          <td><?= htmlspecialchars($row['category_name'] ?? ''); ?></td>
          <td><?= htmlspecialchars($row['code']); ?></td>
          <td><?= htmlspecialchars($row['name']); ?></td>
          <td><?= $row['status']; ?></td>
          <td><?= $row['attachment'] ? htmlspecialchars($row['attachment']) : '-'; ?></td>
          <td>
            <button class="btn btn-primary btn-sm editBtn"
              data-id="<?= $row['id']; ?>"
              data-brand="<?= $row['brand_id']; ?>"
              data-category="<?= $row['category_id']; ?>"
              data-code="<?= htmlspecialchars($row['code']); ?>"
              data-name="<?= htmlspecialchars($row['name']); ?>"
              data-status="<?= $row['status']; ?>"
              data-bs-toggle="modal" data-bs-target="#itemModal">Edit</button>
            <a href="items.php?delete=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this item?')">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <nav>
    <ul class="pagination">
      <?php for($i=1; $i<=$totalPages; $i++): ?>
        <li class="page-item <?= ($i==$page)?'active':'' ?>">
          <a class="page-link" href="items.php?page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>

  <!-- Item Modal -->
  <div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="post" enctype="multipart/form-data" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">New Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="itemId" id="itemId">

          <div class="mb-2">
            <label class="form-label">Brand</label>
            <select name="itemBrand" id="itemBrand" class="form-select" required>
              <option value="">Select Brand</option>
              <?php foreach($brandsArr as $b): ?>
                <option value="<?= $b['id']; ?>"><?= htmlspecialchars($b['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-2">
            <label class="form-label">Category</label>
            <select name="itemCategory" id="itemCategory" class="form-select" required>
              <option value="">Select Category</option>
              <?php foreach($categoriesArr as $c): ?>
                <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-2">
            <label class="form-label">Code</label>
            <input type="text" name="itemCode" id="itemCode" class="form-control" maxlength="30" required>
          </div>

          <div class="mb-2">
            <label class="form-label">Name</label>
            <input type="text" name="itemName" id="itemName" class="form-control" maxlength="100" required>
          </div>

          <div class="mb-2">
            <label class="form-label">Attach File</label>
            <input type="file" name="itemAttachment" id="itemFile" class="form-control">
          </div>

          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="itemActive" id="itemActive" checked>
            <label class="form-check-label">Active</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Fill modal on Edit
document.querySelectorAll('.editBtn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('itemId').value = btn.dataset.id;
    document.getElementById('itemBrand').value = btn.dataset.brand;
    document.getElementById('itemCategory').value = btn.dataset.category;
    document.getElementById('itemCode').value = btn.dataset.code;
    document.getElementById('itemName').value = btn.dataset.name;
    document.getElementById('itemActive').checked = (btn.dataset.status === 'Active');
    document.querySelector('.modal-title').textContent = 'Edit Item';
  });
});

// Reset modal
var itemModal = document.getElementById('itemModal');
itemModal.addEventListener('hidden.bs.modal', () => {
  document.getElementById('itemId').value = '';
  document.getElementById('itemBrand').value = '';
  document.getElementById('itemCategory').value = '';
  document.getElementById('itemCode').value = '';
  document.getElementById('itemName').value = '';
  document.getElementById('itemActive').checked = true;
  document.querySelector('.modal-title').textContent = 'New Item';
});
</script>
</body>
</html>
