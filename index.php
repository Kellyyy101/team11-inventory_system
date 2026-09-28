# Upstream Contract Review Questions

### 1. Endpoint: `GET /found-items` – Missing Enum Constraints & Date Format
* **Issue:** The query parameters `category` and `updatedSince` are defined simply as generic strings. 
* **Question:** What are the exact allowed values for `category`? Could you add an explicit `enum` list (e.g., `[electronics, personal-items, documents, clothing, keys, other]`)? Additionally, should `updatedSince` strictly adhere to an ISO 8601 date-time format (e.g., `format: date-time` with `example: "2026-09-07T10:00:00Z"`)?

### 2. Endpoint: `POST /flagged-checks` – Missing Error Responses & Enum Specifications
* **Issue:** The path currently only documents a `201 Created` success response. There are no error schemas defined for bad requests or missing resources.
* **Question:** What specific payload and HTTP status codes are returned when a request fails? For instance, will a `400 Bad Request` be returned if the `outcome` field is invalid, and a `404 Not Found` if the provided `foundItemId` does not exist? Could you also specify the allowed `enum` values for the `outcome` field (e.g., `match`, `no_match`, `needs_review`)?

### 3. Endpoint: `POST /found-items/batch-lookup` – Ambiguous Field Naming & Array Validation
* **Issue:** The request body contains an array field named `inventoryItemIds`, but it lacks validation rules and clarity regarding missing matches.
* **Question:** What is the maximum number of IDs allowed in the `inventoryItemIds` array per batch request? Additionally, in the `200 OK` response payload, if an ID from the request does not match any existing item, is it completely omitted from the response array or returned with a `matched: false` boolean flag?<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login – Inventory System</title>
  <link rel="stylesheet" href="/inventory_system/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <h1 style="font-size: 45px;">LiteStock</h1>
    <p class="login-sub">Sign in to your account</p>

    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-error">Invalid username or password.</div>
    <?php endif; ?>
    <?php if (isset($_GET['logout'])): ?>
      <div class="alert alert-success">You have been logged out.</div>
    <?php endif; ?>

    <form action="actions/auth.php" method="POST">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="Enter username" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.75rem;">Sign In</button>
    </form>
  </div>
</div>
</body>
</html>