<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>MDM — Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">MDM</a>
      <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link" href="brands.php">Brands</a></li>
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
    <div class="row">
      <div class="col-md-8">
        <div class="card">
          <div class="card-body">
            <h4>Welcome to Master Data Management</h4>
            <p>Use the navigation to manage brands, categories, and items. Pagination is set to 5 per page. Admins can manage all users.</p>

            <div class="row gy-2">
              <div class="col-sm-6">
                <a class="btn btn-outline-primary w-100" href="brands.php">Manage Brands</a>
              </div>
              <div class="col-sm-6">
                <a class="btn btn-outline-primary w-100" href="categories.php">Manage Categories</a>
              </div>
              <div class="col-sm-6">
                <a class="btn btn-outline-primary w-100" href="items.php">Manage Items</a>
              </div>
            </div>

          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <h6>User Info</h6>
            <div id="userInfo">
              <p>Name: <?php echo htmlspecialchars($_SESSION['user']); ?></p>
              <!-- You can add more user info here, e.g., email -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
