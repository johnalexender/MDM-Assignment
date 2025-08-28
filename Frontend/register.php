<?php
include("../Backend/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name  = $_POST['name'];
    $email = $_POST['email'];
    $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $email, $pass);

    if ($stmt->execute()) {
        header("Location: index.php?registered=1");
        exit();
    } else {
        $error = "Email already exists!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - MDM</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card shadow p-4">
          <h3 class="text-center">Register</h3>
          <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
          <form method="POST">
            <div class="mb-3">
              <label>Name</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label>Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label>Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <div class="d-grid mb-3">
              <button class="btn btn-success" type="submit">Register</button>
            </div>
            <p class="text-center small">Already have an account? <a href="index.php">Login here</a></p>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
