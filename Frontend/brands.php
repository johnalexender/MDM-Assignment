<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
include("../Backend/db.php");

// Store user ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Handle form submission (Create/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['brandId'] ?? null;
    $code = $_POST['brandCode'];
    $name = $_POST['brandName'];
    $status = isset($_POST['brandActive']) ? 'Active' : 'Inactive';

    if ($id) {
        // Update brand only if it belongs to this user
        $stmt = $conn->prepare("UPDATE master_brand SET code=?, name=?, status=?, updated_at=NOW() WHERE id=? AND user_id=?");
        $stmt->bind_param("sssii", $code, $name, $status, $id, $user_id);
        $stmt->execute();
    } else {
        // Insert new brand
        $stmt = $conn->prepare("INSERT INTO master_brand (code, name, status, user_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $code, $name, $status, $user_id);
        $stmt->execute();
    }
    header("Location: brands.php");
    exit();
}

// Handle Delete (only if brand belongs to this user)
if (isset($_GET['delete'])) {
    $del_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM master_brand WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $del_id, $user_id);
    $stmt->execute();
    header("Location: brands.php");
    exit();
}

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total brands for this user
$totalResult = $conn->query("SELECT COUNT(*) as total FROM master_brand WHERE user_id=$user_id");
$totalRow = $totalResult->fetch_assoc();
$totalPages = ceil($totalRow['total'] / $limit);

// Fetch brands for current page
$brands = $conn->query("SELECT * FROM master_brand WHERE user_id=$user_id ORDER BY id DESC LIMIT $limit OFFSET $offset");
if (!$brands) {
    die("SQL Error: " . $conn->error);
}

?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>MDM — Brands</title>
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
          <li class="nav-item"><a class="nav-link active" href="brands.php">Brands</a></li>
          <li class="nav-item"><a class="nav-link" href="categories.php">Categories</a></li>
          <li class="nav-item"><a class="nav-link" href="items.php">Items</a></li>
          <li class="nav-item"><a class="nav-link" href="admin.php" id="adminLink" style="display:none">Admin</a></li>
        </ul>
        <div class="d-flex align-items-center">
          <span class="text-white me-3" id="welcomeUser">Hello, <?php echo htmlspecialchars($_SESSION['user']); ?></span>
          <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
      </div>
    </div>
  </nav>
<main class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Brands</h4>
    <div>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#brandModal">New Brand</button>
    </div>
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
      <?php while($row = $brands->fetch_assoc()): ?>
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
              data-bs-toggle="modal" data-bs-target="#brandModal">
              Edit
            </button>
            <a href="brands.php?delete=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this brand?')">Delete</a>
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
          <a class="page-link" href="brands.php?page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>

  <!-- Brand Modal -->
  <div class="modal fade" id="brandModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="post" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">New Brand</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="brandId" id="brandId">
          <div class="mb-2">
            <label class="form-label">Code</label>
            <input type="text" name="brandCode" id="brandCode" class="form-control" maxlength="20" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Name</label>
            <input type="text" name="brandName" id="brandName" class="form-control" maxlength="60" required>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="brandActive" id="brandActive" checked>
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
// Fill modal with brand info when editing
document.querySelectorAll('.editBtn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('brandId').value = btn.dataset.id;
    document.getElementById('brandCode').value = btn.dataset.code;
    document.getElementById('brandName').value = btn.dataset.name;
    document.getElementById('brandActive').checked = (btn.dataset.status === 'Active');
    document.querySelector('.modal-title').textContent = 'Edit Brand';
  });
});

// Reset modal when closed
var brandModal = document.getElementById('brandModal');
brandModal.addEventListener('hidden.bs.modal', () => {
  document.getElementById('brandId').value = '';
  document.getElementById('brandCode').value = '';
  document.getElementById('brandName').value = '';
  document.getElementById('brandActive').checked = true;
  document.querySelector('.modal-title').textContent = 'New Brand';
});
</script>
</body>
</html>
