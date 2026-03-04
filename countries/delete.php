<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../common/header.php";

$code = strtoupper(trim($_GET["code"] ?? ""));

if ($code === "" || strlen($code) != 2) {
    echo "<p>Invalid country code.</p>";
    require_once __DIR__ . "/../common/footer.php";
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM COUNTRY WHERE country_code = ?");
mysqli_stmt_bind_param($stmt, "s", $code);
$ok = mysqli_stmt_execute($stmt);

if (!$ok) {
    echo "<p>Cannot delete this country because it is referenced by other tables.</p>";
    echo "<p>Error: " . htmlspecialchars(mysqli_stmt_error($stmt)) . "</p>";
    echo '<p><a href="list.php">Back</a></p>';
    mysqli_stmt_close($stmt);
    require_once __DIR__ . "/../common/footer.php";
    exit;
}

mysqli_stmt_close($stmt);
header("Location: list.php");
exit;
