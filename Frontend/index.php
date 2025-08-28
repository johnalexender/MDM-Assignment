<?php
session_start();
include("../Backend/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $pass  = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if (password_verify($pass, $row['password'])) {
            $_SESSION['user'] = $row['name'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "No user found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - MDM</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card shadow p-4">
          <h3 class="text-center">Login</h3>
          <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
          <form method="POST">
            <div class="mb-3">
              <label>Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label>Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <div class="d-grid mb-3">
              <button class="btn btn-primary" type="submit">Login</button>
            </div>
            <p class="text-center small">Don't have an account? <a href="register.php">Register here</a></p>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
