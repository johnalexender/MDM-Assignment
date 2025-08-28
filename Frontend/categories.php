<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

include("../Backend/db.php"); // Database connection

// Store user ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Handle form submission (Create/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['categoryId'] ?? null;
    $code = trim($_POST['categoryCode']);
    $name = trim($_POST['categoryName']);
    $status = isset($_POST['categoryActive']) ? 'Active' : 'Inactive';

    if ($id) {
        // Update existing category
        $stmt = $conn->prepare("UPDATE master_category SET code=?, name=?, status=?, updated_at=NOW() WHERE id=? AND user_id=?");
        $stmt->bind_param("sssii", $code, $name, $status, $id, $user_id);
        if (!$stmt->execute()) {
            die("Update Error: " . $stmt->error);
        }
    } else {
        // Insert new category
        $stmt = $conn->prepare("INSERT INTO master_category (code, name, status, user_id, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("sssi", $code, $name, $status, $user_id);
        if (!$stmt->execute()) {
            die("Insert Error: " . $stmt->error);
        }
    }
    header("Location: categories.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $del_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM master_category WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $del_id, $user_id);
    $stmt->execute();
    header("Location: categories.php");
    exit();
}

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total categories for this user
$totalResult = $conn->query("SELECT COUNT(*) as total FROM master_category WHERE user_id=$user_id");
$totalRow = $totalResult->fetch_assoc();
$totalPages = ceil($totalRow['total'] / $limit);

// Fetch categories for current page
$categories = $conn->query("SELECT * FROM master_category WHERE user_id=$user_id ORDER BY id DESC LIMIT $limit OFFSET $offset");
if (!$categories) {
    die("SQL Error: " . $conn->error);
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MDM — Categories</title>
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
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link" href="brands.php">Brands</a></li>
          <li class="nav-item"><a class="nav-link active" href="categories.php">Categories</a></li>
          <li class="nav-item"><a class="nav-link" href="items.php">Items</a></li>
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
    <h4>Categories</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#categoryModal">New Category</button>
  </div>

  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Code</th>
        <th>Name</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $categories->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['code']); ?></td>
          <td><?= htmlspecialchars($row['name']); ?></td>
          <td><?= $row['status']; ?></td>
          <td>
            <button 
              class="btn btn-primary btn-sm editBtn" 
              data-id="<?= $row['id']; ?>" 
              data-code="<?= htmlspecialchars($row['code']); ?>" 
              data-name="<?= htmlspecialchars($row['name']); ?>" 
              data-status="<?= $row['status']; ?>" 
              data-bs-toggle="modal" data-bs-target="#categoryModal">
              Edit
            </button>
            <a href="categories.php?delete=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this category?')">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- Pagination Links -->
  <nav>
    <ul class="pagination">
      <?php for($i=1; $i<=$totalPages; $i++): ?>
        <li class="page-item <?= ($i==$page)?'active':'' ?>">
          <a class="page-link" href="categories.php?page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>

  <!-- Category Modal -->
  <div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="post" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">New Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="categoryId" id="categoryId">
          <div class="mb-2">
            <label class="form-label">Code</label>
            <input type="text" name="categoryCode" id="categoryCode" class="form-control" maxlength="20" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Name</label>
            <input type="text" name="categoryName" id="categoryName" class="form-control" maxlength="60" required>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="categoryActive" id="categoryActive" checked>
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
// Fill modal with category info when editing
document.querySelectorAll('.editBtn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('categoryId').value = btn.dataset.id;
    document.getElementById('categoryCode').value = btn.dataset.code;
    document.getElementById('categoryName').value = btn.dataset.name;
    document.getElementById('categoryActive').checked = (btn.dataset.status === 'Active');
    document.querySelector('.modal-title').textContent = 'Edit Category';
  });
});

// Reset modal when closed
var categoryModal = document.getElementById('categoryModal');
categoryModal.addEventListener('hidden.bs.modal', () => {
  document.getElementById('categoryId').value = '';
  document.getElementById('categoryCode').value = '';
  document.getElementById('categoryName').value = '';
  document.getElementById('categoryActive').checked = true;
  document.querySelector('.modal-title').textContent = 'New Category';
});
</script>
</body>
</html>
